<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\Season;
use App\Models\Team;
use App\Support\Season\CurrentSeason;
use Carbon\CarbonImmutable;
use GuzzleHttp\Client;

use App\Services\Import\Sources\PennyParser;
use App\Services\Import\Sources\EisbaerenScheduleParser;
use App\Services\Import\Sources\EisbaerenResultsParser;
use App\Services\Import\Sources\SportschauSource;

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
        private readonly ?SportschauSource $sportschau = null, // optional
    ) {}

    /**
     * @return array{season:Season, imported:int, needs_review:int, skipped_resolve:int, skipped_outside_season:int, total_merged:int}
     */
    public function syncCurrentSeason(): array
    {
        // Saisonfenster: 01.08.YYYY .. 31.07.YYYY+1 (CurrentSeason)
        $season = CurrentSeason::resolveOrCreateCurrentSeason();
        [$seasonStart, $seasonEnd] = CurrentSeason::seasonWindowForDate(CarbonImmutable::now(self::TZ));

        $ebb = $this->teamResolver->findByName('Eisbären Berlin');
        if (!$ebb) {
            throw new \RuntimeException('Team "Eisbären Berlin" not found in DB.');
        }

        // Fallback matching: alle Team-Namen aus der DB
        $allTeamNames = Team::query()
            ->pluck('name')
            ->filter(fn($v) => is_string($v) && trim($v) !== '')
            ->values()
            ->all();

        $drafts = [];

        // ------------------------------------------------------------
        // Source 1: PENNY (Matchday + W/L + evtl. Score; meist keine Uhrzeit)
        // ------------------------------------------------------------
        $pennyUrl = 'https://www.penny-del.org/teams/eisbaeren-berlin/spielplan';
        if ($pennyHtml = $this->fetchHtml($pennyUrl)) {
            $drafts = array_merge($drafts, $this->penny->parse($pennyHtml, $pennyUrl, $allTeamNames));
        }

        // ------------------------------------------------------------
        // Source 2: Eisbaeren schedule (Future with kickoff time)
        // ------------------------------------------------------------
        $ebbScheduleUrl = 'https://www.eisbaeren.de/spielplan-tabelle/spielplan';
        if ($html = $this->fetchHtml($ebbScheduleUrl)) {
            $drafts = array_merge($drafts, $this->ebbSchedule->parse($html, $ebbScheduleUrl));
        }

        // ------------------------------------------------------------
        // Source 3: Eisbaeren results (History; filtered later by season window)
        // ------------------------------------------------------------
        $ebbResultsUrl = 'https://www.eisbaeren.de/spielplan-tabelle/ergebnisse';
        if ($html = $this->fetchHtml($ebbResultsUrl)) {
            $drafts = array_merge($drafts, $this->ebbResults->parse($html, $ebbResultsUrl));
        }

        // ------------------------------------------------------------
        // Source 4: sportschau best-effort (optional)
        // ------------------------------------------------------------
        if ($this->sportschau) {
            $sportschauUrl = 'https://www.sportschau.de/live-und-ergebnisse/verein/te2927/eisbaeren-berlin/spielplan-team';
            if ($html = $this->fetchHtml($sportschauUrl)) {
                $drafts = array_merge($drafts, $this->sportschau->parse($html, $sportschauUrl));
            }
        }

        // Normalize names lightly (keine Teamliste; nur harmlose Text-Reinigung)
        foreach ($drafts as &$d) {
            $d['home'] = $this->normalizeTeamName((string) ($d['home'] ?? ''));
            $d['away'] = $this->normalizeTeamName((string) ($d['away'] ?? ''));
        }
        unset($d);

        // Merge drafts (date+home+away)
        $merged = $this->merger->merge($drafts);

        $imported = 0;
        $needsReview = 0;
        $skippedResolve = 0;
        $skippedOutside = 0;

        foreach ($merged as $g) {
            $homeName = (string) ($g['home'] ?? '');
            $awayName = (string) ($g['away'] ?? '');
            if ($homeName === '' || $awayName === '') {
                continue;
            }

            // Ensure kickoff exists (DB NOT NULL) – and use date-based fallback.
            $kickoff = $g['kickoff_at'] ?? null;
            if (!$kickoff instanceof CarbonImmutable) {
                $kickoff = $this->fallbackKickoffFromDate($g['date'] ?? null);
                $g['needs_review'] = true;
            }

            // HARD RULE: only current season window
            if (!CurrentSeason::isInWindow($kickoff, $seasonStart, $seasonEnd)) {
                $skippedOutside++;
                continue;
            }

            $homeTeam = $this->teamResolver->findByName($homeName);
            $awayTeam = $this->teamResolver->findByName($awayName);

            if (!$homeTeam || !$awayTeam) {
                $skippedResolve++;
                continue;
            }

            // EBB-centric mapping (should always be true for these sources)
            if ((int) $homeTeam->id === (int) $ebb->id) {
                $isHome = true;
                $opponentId = (int) $awayTeam->id;
            } elseif ((int) $awayTeam->id === (int) $ebb->id) {
                $isHome = false;
                $opponentId = (int) $homeTeam->id;
            } else {
                // Not an EBB match -> ignore
                continue;
            }

            $status = (string) ($g['status'] ?? 'scheduled');
            $matchday = isset($g['matchday']) ? (int) $g['matchday'] : null;

            $ebbGoals = $g['ebb_goals'] ?? null;
            $oppGoals = $g['opp_goals'] ?? null;
            if ($ebbGoals !== null && $oppGoals !== null) {
                $status = 'finished';
            }

            $needs = (bool) ($g['needs_review'] ?? false);
            if ($needs) {
                $needsReview++;
            }

            $this->importer->upsert([
                'season_id' => (int) $season->id,
                'matchday' => $matchday,
                'opponent_id' => $opponentId,
                'is_home' => $isHome,
                'kickoff_at' => $kickoff,
                'needs_review' => $needs,
                'status' => $status,
                'source' => (string) ($g['source'] ?? 'multi'),
                'external_url' => $g['external_url'] ?? null,
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

    private function fetchHtml(string $url): ?string
    {
        try {
            $res = $this->http->request('GET', $url, [
                'headers' => [
                    'User-Agent' => 'BLTS2 Game Sync (+https://example.invalid)',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ],
                'timeout' => 25,
            ]);
            return (string) $res->getBody();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeTeamName(string $name): string
    {
        $n = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);

        // minimal cleanup: Sportschau/PENNY noise
        $n = preg_replace('/\bTickets?\b/iu', '', $n) ?? $n;

        return trim(preg_replace('/\s+/u', ' ', $n) ?? $n);
    }

    private function fallbackKickoffFromDate(mixed $date): CarbonImmutable
    {
        // If we only have a date: default to 19:30 local and mark needs_review.
        if (is_string($date) && preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $date)) {
            try {
                return CarbonImmutable::createFromFormat('d.m.Y H:i', $date . ' 19:30', self::TZ);
            } catch (\Throwable) {
                // ignore
            }
        }

        // Absolute fallback: now + 1 day 19:30
        $now = CarbonImmutable::now(self::TZ)->addDay();
        return $now->setTime(19, 30);
    }
}
