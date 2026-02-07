<?php

declare(strict_types=1);

namespace App\Services\Import\Sources;

use Carbon\CarbonImmutable;
use Symfony\Component\DomCrawler\Crawler;

final class SportschauSource
{
    private const TZ = 'Europe/Berlin';

    /**
     * @return array<int,array<string,mixed>>
     */
    public function parse(string $html, string $url): array
    {
        $c = new Crawler($html);
        $out = [];

        try {
            $c->filter('script[type="application/ld+json"]')->each(function (Crawler $s) use (&$out, $url) {
                $json = trim((string)$s->text(''));
                if ($json === '') {
                    return;
                }

                $decoded = json_decode($json, true);
                if (!is_array($decoded)) {
                    return;
                }

                foreach ($this->extractSportsEventsFromJsonLd($decoded) as $ev) {
                    $out[] = [
                        'source' => 'sportschau',
                        'external_url' => $url,
                        'date' => $ev['date'] ?? null,
                        'kickoff_at' => $ev['kickoff_at'] ?? null,
                        'matchday' => null,
                        'home' => $ev['home'] ?? '',
                        'away' => $ev['away'] ?? '',
                        'status' => $ev['status'] ?? 'scheduled',
                        'home_goals' => $ev['home_goals'] ?? null,
                        'away_goals' => $ev['away_goals'] ?? null,
                        'needs_review' => (bool)(($ev['kickoff_at'] ?? null) === null),
                    ];
                }
            });
        } catch (\Throwable) {
            // best-effort
        }

        return $out;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function extractSportsEventsFromJsonLd(array $decoded): array
    {
        $events = [];

        $nodes = [];
        if (isset($decoded['@graph']) && is_array($decoded['@graph'])) {
            $nodes = $decoded['@graph'];
        } elseif (isset($decoded[0]) && is_array($decoded[0])) {
            $nodes = $decoded;
        } else {
            $nodes = [$decoded];
        }

        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }

            if (isset($node['itemListElement']) && is_array($node['itemListElement'])) {
                foreach ($node['itemListElement'] as $el) {
                    if (is_array($el)) {
                        $events = array_merge($events, $this->extractSportsEventsFromJsonLd($el));
                    }
                }
                continue;
            }

            $type = $node['@type'] ?? null;
            if ($type !== 'SportsEvent' && $type !== ['SportsEvent']) {
                if (isset($node['item']) && is_array($node['item'])) {
                    $events = array_merge($events, $this->extractSportsEventsFromJsonLd($node['item']));
                }
                continue;
            }

            $startDate = $node['startDate'] ?? null;
            $kickoffAt = null;
            $date = null;

            if (is_string($startDate) && $startDate !== '') {
                try {
                    $kickoffAt = CarbonImmutable::parse($startDate)->setTimezone(self::TZ);
                    $date = $kickoffAt->format('d.m.Y');
                } catch (\Throwable) {
                }
            }

            $home = $node['homeTeam']['name'] ?? '';
            $away = $node['awayTeam']['name'] ?? '';

            $events[] = [
                'date' => $date,
                'kickoff_at' => $kickoffAt,
                'home' => is_string($home) ? $home : '',
                'away' => is_string($away) ? $away : '',
                'status' => 'scheduled',
                'home_goals' => null,
                'away_goals' => null,
            ];
        }

        return $events;
    }
}
