<?php

declare(strict_types=1);

namespace App\Services\Import\Sources;

use Carbon\CarbonImmutable;
use Symfony\Component\DomCrawler\Crawler;

final class EisbaerenScheduleParser
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

        // "Mi. 25.02.2026 19:30 Uhr Eisbären Berlin Straubing Tigers Tickets"
        $pattern = '/\b(\d{2}\.\d{2}\.\d{4})\b\s+(\d{2}:\d{2})\s+Uhr\s+(.+?)(?:\s+Tickets\b|$)/u';

        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $date = $m[1];
                $time = $m[2];
                $teamsPart = trim($m[3]);

                [$home, $away] = $this->splitTeamsAroundEbb($teamsPart);
                if ($home === '' || $away === '') {
                    continue;
                }

                $kickoffAt = CarbonImmutable::createFromFormat('d.m.Y H:i', "{$date} {$time}", self::TZ);

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
