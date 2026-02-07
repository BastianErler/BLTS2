<?php

declare(strict_types=1);

namespace App\Services\Import\Sources;

use Symfony\Component\DomCrawler\Crawler;

final class PennyParser
{
    /**
     * @param array<int,string> $allTeamNames
     * @return array<int,array<string,mixed>>
     */
    public function parse(string $html, string $url, array $allTeamNames): array
    {
        $c = new Crawler($html);
        $rows = $c->filter('table tr');

        $out = [];

        foreach ($rows as $tr) {
            $row = new Crawler($tr);
            $raw = trim(preg_replace('/\s+/', ' ', $row->text('')));

            if ($raw === '' || !preg_match('/\b\d{2}\.\d{2}\.\d{4}\b/', $raw)) {
                continue;
            }

            preg_match('/(\d{2}\.\d{2}\.\d{4})/', $raw, $d);
            $date = $d[1] ?? null;
            if (!is_string($date) || $date === '') {
                continue;
            }

            $matchday = null;
            if (preg_match('/^\s*\d{2}\.\d{2}\.\d{4}\s+(\d{1,2})\b/u', $raw, $m)) {
                $matchday = (int) $m[1];
            }

            $score = $this->extractScoreFromText($raw);
            [$home, $away] = $this->extractTeamsFromRowGeneric($row, $raw, $allTeamNames);

            if ($home === '' || $away === '') {
                continue;
            }

            $out[] = [
                'source' => 'penny',
                'external_url' => $url,
                'date' => $date,
                'kickoff_at' => null,
                'matchday' => $matchday,
                'home' => $home,
                'away' => $away,
                'status' => ($score ? 'finished' : 'scheduled'),
                'home_goals' => $score['home'] ?? null,
                'away_goals' => $score['away'] ?? null,
                'needs_review' => true,
            ];
        }

        return $out;
    }

    private function extractScoreFromText(string $raw): ?array
    {
        if (preg_match('/\b(\d{1,2})\s*[:]\s*(\d{1,2})\b(?!\s*Uhr)/u', $raw, $m)) {
            $a = (int) $m[1];
            $b = (int) $m[2];
            if ($a <= 23 && $b <= 59) {
                return null;
            }
            return ['home' => $a, 'away' => $b];
        }

        if (preg_match('/\b(\d{1,2})\s*-\s*(\d{1,2})\b/u', $raw, $m2)) {
            $a = (int) $m2[1];
            $b = (int) $m2[2];
            return ['home' => $a, 'away' => $b];
        }

        return null;
    }


    /**
     * @return array{0:string,1:string}
     */
    private function extractTeamsFromRowGeneric(Crawler $row, string $raw, array $allTeamNames): array
    {
        // anchors
        $names = [];
        try {
            foreach ($row->filter('a') as $a) {
                $t = trim(preg_replace('/\s+/', ' ', $a->textContent ?? ''));
                if ($t === '') continue;

                $lower = mb_strtolower($t);
                if (in_array($lower, ['details', 'spielbericht', 'highlight', 'highlights', 'tickets'], true)) continue;
                if (preg_match('/^(w|l)(\s*\((ot|so)\))?$/i', $t)) continue;

                $names[] = $t;
                if (count($names) >= 2) {
                    return [$names[0], $names[1]];
                }
            }
        } catch (\Throwable) {
        }

        // positional fallback
        $clean = $this->stripNoise($raw);
        $hits = [];

        foreach ($allTeamNames as $teamName) {
            $pos = mb_stripos($clean, $teamName);
            if ($pos !== false) {
                $hits[] = ['name' => $teamName, 'pos' => (int) $pos];
            }
        }

        if (count($hits) < 2) {
            return ['', ''];
        }

        usort($hits, fn($a, $b) => $a['pos'] <=> $b['pos']);

        $first = $hits[0]['name'];
        $second = null;
        foreach ($hits as $h) {
            if ($h['name'] !== $first) {
                $second = $h['name'];
                break;
            }
        }

        return $second ? [$first, $second] : ['', ''];
    }

    private function stripNoise(string $raw): string
    {
        $s = $raw;
        $s = preg_replace('/\b\d+\s*-\s*\d+\b/u', ' ', $s) ?? $s;
        $s = preg_replace('/\b\d+\s*:\s*\d+\b/u', ' ', $s) ?? $s;
        $s = preg_replace('/\b[WL]\b(?:\s*\((?:OT|SO)\))?/iu', ' ', $s) ?? $s;
        $s = preg_replace('/\b(n\.V\.|n\.P\.)\b/iu', ' ', $s) ?? $s;
        $s = preg_replace('/\bDetails\b/iu', ' ', $s) ?? $s;
        $s = preg_replace('/\bTickets\b/iu', ' ', $s) ?? $s;
        return trim(preg_replace('/\s+/u', ' ', $s) ?? $s);
    }
}
