<?php

declare(strict_types=1);

namespace App\Services\Import\Sources;

use Carbon\CarbonImmutable;
use Symfony\Component\DomCrawler\Crawler;

final class EisbaerenResultsParser
{
    private const TZ = 'Europe/Berlin';

    /**
     * @return array<int,array<string,mixed>>
     */
    public function parse(string $html, string $url): array
    {
        $c = new Crawler($html);
        $text = trim(preg_replace('/\s+/', ' ', $c->filter('body')->text('')));

        $out = [];

        // "Fr. 19.04.2024 19:30 Uhr Eisbären Berlin Fischtown Pinguins 5 : 3"
        $pattern = '/\b(\d{2}\.\d{2}\.\d{4})\b\s+(\d{2}:\d{2})\s+Uhr\s+(.+?)\s+(\d+)\s*:\s*(\d+)(?:\s+(n\.V\.|n\.P\.))?/u';

        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $date = $m[1];
                $time = $m[2];
                $teamsPart = trim($m[3]);
                $homeGoals = (int)$m[4];
                $awayGoals = (int)$m[5];

                [$home, $away] = $this->splitTeamsAroundEbb($teamsPart);
                if ($home === '' || $away === '') {
                    continue;
                }

                $kickoffAt = CarbonImmutable::createFromFormat('d.m.Y H:i', "{$date} {$time}", self::TZ);

                $out[] = [
                    'source' => 'eisbaeren_ergebnisse',
                    'external_url' => $url,
                    'date' => $date,
                    'kickoff_at' => $kickoffAt,
                    'matchday' => null,
                    'home' => $home,
                    'away' => $away,
                    'status' => 'finished',
                    'home_goals' => $homeGoals,
                    'away_goals' => $awayGoals,
                    'needs_review' => false,
                ];
            }
        }

        return $out;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitTeamsAroundEbb(string $teamsPart): array
    {
        $teamsPart = trim(preg_replace('/\s+/u', ' ', $teamsPart) ?? $teamsPart);
        $ebb = 'Eisbären Berlin';

        $pos = mb_stripos($teamsPart, $ebb);
        if ($pos === false) {
            return ['', ''];
        }

        $left  = trim(mb_substr($teamsPart, 0, $pos));
        $right = trim(mb_substr($teamsPart, $pos + mb_strlen($ebb)));

        $left = trim(preg_replace('/^[\-\–\·\|]+/u', '', $left) ?? $left);
        $right = trim(preg_replace('/^[\-\–\·\|]+/u', '', $right) ?? $right);

        if ($left !== '' && $right === '') {
            return [$left, $ebb];
        }
        if ($left === '' && $right !== '') {
            return [$ebb, $right];
        }
        if ($left !== '' && $right !== '') {
            return [$left, $ebb];
        }

        return ['', ''];
    }
}
