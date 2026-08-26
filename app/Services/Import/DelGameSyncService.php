<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\Season;
use App\Models\Team;
use App\Services\Import\Sources\EisbaerenResultsParser;
use App\Services\Import\Sources\EisbaerenScheduleParser;
use App\Services\Import\Sources\PennyParser;
use App\Services\Import\Sources\SportschauSource;
use App\Support\Season\CurrentSeason;
use Carbon\CarbonImmutable;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

final class DelGameSyncService
{
    private const TZ = 'Europe/Berlin';

    public function __construct(
        private readonly Client $http,
        private readonly GameImportService $importer,
        private readonly TeamResolver $teamResolver,
        private readonly GameDraftMerger $merger,
        private readonly PennyParser $penny,
        private readonly EisbaerenScheduleParser $ebbSchedule,
        private readonly EisbaerenResultsParser $ebbResults,
        private readonly ?SportschauSource $sportschau = null,
    ) {}

    /**
     * @return array{season:Season, imported:int, needs_review:int, skipped_resolve:int, skipped_outside_season:int, total_merged:int}
     */
    public function syncCurrentSeason(): array
    {
        $season = CurrentSeason::resolveOrCreateCurrentSeason();
        [$seasonStart, $seasonEnd] = CurrentSeason::seasonWindowForDate(
            CarbonImmutable::now(self::TZ)
        );

        $ebb = $this->teamResolver->findByName('Eisbären Berlin');

        if (!$ebb) {
            throw new \RuntimeException('Team "Eisbären Berlin" not found in DB.');
        }

        $allTeamNames = Team::query()
            ->pluck('name')
            ->filter(fn($value) => is_string($value) && trim($value) !== '')
            ->values()
            ->all();

        $drafts = [];

        // Source 1: PENNY
        $pennyUrl = 'https://www.penny-del.org/teams/eisbaeren-berlin/spielplan';

        if ($pennyHtml = $this->fetchHtml($pennyUrl)) {
            $parsed = $this->penny->parse($pennyHtml, $pennyUrl, $allTeamNames);
            $this->logParserResult('PENNY', $parsed);
            $drafts = array_merge($drafts, $parsed);
        }

        // Source 2: Eisbären-Spielplan
        $ebbScheduleUrl = 'https://www.eisbaeren.de/spielplan-tabelle/spielplan';

        if ($html = $this->fetchHtml($ebbScheduleUrl)) {
            $parsed = $this->ebbSchedule->parse($html, $ebbScheduleUrl);
            $this->logParserResult('Eisbären schedule', $parsed);
            $drafts = array_merge($drafts, $parsed);
        }

        // Source 3: Eisbären-Ergebnisse. Kann historische Saisons enthalten.
        $ebbResultsUrl = 'https://www.eisbaeren.de/spielplan-tabelle/ergebnisse';

        if ($html = $this->fetchHtml($ebbResultsUrl)) {
            $parsed = $this->ebbResults->parse($html, $ebbResultsUrl);
            $this->logParserResult('Eisbären results', $parsed);
            $drafts = array_merge($drafts, $parsed);
        }

        // Source 4: Sportschau, optional
        if ($this->sportschau) {
            $sportschauUrl = 'https://www.sportschau.de/live-und-ergebnisse/verein/te2927/eisbaeren-berlin/spielplan-team';

            if ($html = $this->fetchHtml($sportschauUrl)) {
                $parsed = $this->sportschau->parse($html, $sportschauUrl);
                $this->logParserResult('Sportschau', $parsed);
                $drafts = array_merge($drafts, $parsed);
            }
        }

        Log::info('Game sync parser total', [
            'draft_count' => count($drafts),
        ]);

        foreach ($drafts as &$draft) {
            $draft['home'] = $this->normalizeTeamName((string) ($draft['home'] ?? ''));
            $draft['away'] = $this->normalizeTeamName((string) ($draft['away'] ?? ''));
        }

        unset($draft);

        $merged = $this->merger->merge($drafts);

        Log::info('Game sync merge result', [
            'merged_count' => count($merged),
        ]);

        $imported = 0;
        $needsReview = 0;
        $skippedResolve = 0;
        $skippedOutside = 0;

        foreach ($merged as $game) {
            $homeName = (string) ($game['home'] ?? '');
            $awayName = (string) ($game['away'] ?? '');

            if ($homeName === '' || $awayName === '') {
                Log::warning('Game skipped because team name is empty', [
                    'game' => $game,
                ]);
                continue;
            }

            $kickoff = $game['kickoff_at'] ?? null;

            if (!$kickoff instanceof CarbonImmutable) {
                $kickoff = $this->fallbackKickoffFromDate($game['date'] ?? null);
                $game['needs_review'] = true;
            }

            if (!CurrentSeason::isInWindow($kickoff, $seasonStart, $seasonEnd)) {
                Log::warning('Game skipped outside season', [
                    'source' => $game['source'] ?? null,
                    'date' => $game['date'] ?? null,
                    'kickoff_at' => $kickoff->toIso8601String(),
                    'home' => $homeName,
                    'away' => $awayName,
                ]);

                $skippedOutside++;
                continue;
            }

            $homeTeam = $this->teamResolver->findByName($homeName);
            $awayTeam = $this->teamResolver->findByName($awayName);

            if (!$homeTeam || !$awayTeam) {
                Log::warning('Game skipped because team could not be resolved', [
                    'home' => $homeName,
                    'away' => $awayName,
                    'home_resolved' => (bool) $homeTeam,
                    'away_resolved' => (bool) $awayTeam,
                    'date' => $game['date'] ?? null,
                    'kickoff_at' => $kickoff->toIso8601String(),
                ]);

                $skippedResolve++;
                continue;
            }

            if ((int) $homeTeam->id === (int) $ebb->id) {
                $isHome = true;
                $opponentId = (int) $awayTeam->id;
            } elseif ((int) $awayTeam->id === (int) $ebb->id) {
                $isHome = false;
                $opponentId = (int) $homeTeam->id;
            } else {
                continue;
            }

            $ebbGoals = $game['ebb_goals'] ?? null;
            $oppGoals = $game['opp_goals'] ?? null;
            $status = (string) ($game['status'] ?? 'scheduled');

            if ($ebbGoals !== null && $oppGoals !== null) {
                $status = 'finished';
            }

            $needs = (bool) ($game['needs_review'] ?? false);

            if ($needs) {
                $needsReview++;
            }

            $this->importer->upsert([
                'season_id' => (int) $season->id,
                'matchday' => isset($game['matchday']) ? (int) $game['matchday'] : null,
                'opponent_id' => $opponentId,
                'is_home' => $isHome,
                'kickoff_at' => $kickoff,
                'needs_review' => $needs,
                'status' => $status,
                'source' => (string) ($game['source'] ?? 'multi'),
                'external_url' => $game['external_url'] ?? null,
                'eisbaeren_goals' => $ebbGoals,
                'opponent_goals' => $oppGoals,
            ]);

            $imported++;
        }

        return [
            'season' => $season,
            'imported' => $imported,
            'needs_review' => $needsReview,
            'skipped_resolve' => $skippedResolve,
            'skipped_outside_season' => $skippedOutside,
            'total_merged' => count($merged),
        ];
    }

    private function logParserResult(string $source, array $games): void
    {
        $lastKey = array_key_last($games);

        Log::info("{$source} parser result", [
            'count' => count($games),
            'first_date' => $games[0]['date'] ?? null,
            'last_date' => $lastKey !== null ? ($games[$lastKey]['date'] ?? null) : null,
        ]);
    }

    private function fetchHtml(string $url): ?string
    {
        try {
            $response = $this->http->request('GET', $url, [
                'headers' => [
                    'User-Agent' => 'BLTS2 Game Sync (+https://example.invalid)',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ],
                'timeout' => 25,
            ]);

            return (string) $response->getBody();
        } catch (\Throwable $exception) {
            Log::warning('Could not fetch game source', [
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function normalizeTeamName(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
        $name = preg_replace('/\bTickets?\b/iu', '', $name) ?? $name;

        return trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
    }

    private function fallbackKickoffFromDate(mixed $date): CarbonImmutable
    {
        if (is_string($date) && preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $date)) {
            try {
                return CarbonImmutable::createFromFormat(
                    'd.m.Y H:i',
                    $date . ' 19:30',
                    self::TZ
                );
            } catch (\Throwable) {
                // Fallback below.
            }
        }

        return CarbonImmutable::now(self::TZ)
            ->addDay()
            ->setTime(19, 30);
    }
}
