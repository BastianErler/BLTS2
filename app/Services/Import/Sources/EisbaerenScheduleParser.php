<?php

declare(strict_types=1);

namespace App\Services\Import\Sources;

use Carbon\CarbonImmutable;
use Symfony\Component\DomCrawler\Crawler;

final class EisbaerenScheduleParser
{
    private const TZ = 'Europe/Berlin';

    /**
     * Liest den Spielplan der Eisbären-Seite aus.
     * DEL2-, CHL- und internationale Spiele werden später beim Import verworfen.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $html, string $url): array
    {
        $crawler = new Crawler($html);
        $body = $crawler->filter('body');

        if ($body->count() === 0) {
            return [];
        }

        $text = trim(preg_replace('/\s+/u', ' ', $body->text('')) ?? '');
        $out = [];

        /*
         * Wichtig: Nicht bei "Tickets" beenden.
         * Auswärtsspiele haben keinen Ticket-Link. Stattdessen endet ein
         * Datensatz unmittelbar vor dem nächsten Datum/Uhrzeit-Block.
         */
        $pattern = '/\b(\d{2}\.\d{2}\.\d{4})\b\s+'
            . '(\d{2}:\d{2})\s+Uhr\s+(.+?)'
            . '(?=\s+\d{2}\.\d{2}\.\d{4}\s+\d{2}:\d{2}\s+Uhr\b|$)/u';

        if (!preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            return [];
        }

        foreach ($matches as $match) {
            $date = $match[1];
            $time = $match[2];
            $teamsPart = trim($match[3]);

            // Ticket-Text gehört nicht zum Teamnamen.
            $teamsPart = preg_replace('/\s+Tickets?\b.*$/iu', '', $teamsPart) ?? $teamsPart;

            [$home, $away] = $this->splitTeamsAroundEbb($teamsPart);

            if ($home === '' || $away === '') {
                continue;
            }

            try {
                $kickoffAt = CarbonImmutable::createFromFormat(
                    'd.m.Y H:i',
                    "{$date} {$time}",
                    self::TZ
                );
            } catch (\Throwable) {
                continue;
            }

            $out[] = [
                'source' => 'eisbaeren_spielplan',
                'external_url' => $url,
                'date' => $date,
                'kickoff_at' => $kickoffAt,
                'matchday' => null,
                'home' => $home,
                'away' => $away,
                'status' => 'scheduled',
                'needs_review' => false,
            ];
        }

        return $out;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitTeamsAroundEbb(string $teamsPart): array
    {
        $teamsPart = trim(preg_replace('/\s+/u', ' ', $teamsPart) ?? $teamsPart);
        $ebb = 'Eisbären Berlin';
        $position = mb_stripos($teamsPart, $ebb);

        if ($position === false) {
            return ['', ''];
        }

        $left = trim(mb_substr($teamsPart, 0, $position));
        $right = trim(mb_substr($teamsPart, $position + mb_strlen($ebb)));

        $left = $this->cleanTeamPart($left);
        $right = $this->cleanTeamPart($right);

        if ($left !== '' && $right === '') {
            return [$left, $ebb];
        }

        if ($left === '' && $right !== '') {
            return [$ebb, $right];
        }

        // Sollte zwischen den Teamnamen zusätzlicher Seitentext stehen,
        // bleibt Eisbären Berlin der sichere Anker.
        if ($left !== '' && $right !== '') {
            return [$left, $ebb];
        }

        return ['', ''];
    }

    private function cleanTeamPart(string $team): string
    {
        $team = trim($team);
        $team = preg_replace('/\bvs\b/iu', '', $team) ?? $team;
        $team = preg_replace('/\bTickets?\b.*$/iu', '', $team) ?? $team;
        $team = trim(preg_replace('/^[\-–·|:]+|[\-–·|:]+$/u', '', $team) ?? $team);

        return trim(preg_replace('/\s+/u', ' ', $team) ?? $team);
    }
}
