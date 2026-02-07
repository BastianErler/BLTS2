<?php

declare(strict_types=1);

namespace App\Services\Import;

use Carbon\CarbonImmutable;

final class GameDraftMerger
{
    private const TZ = 'Europe/Berlin';

    /**
     * @param array<int,array<string,mixed>> $drafts
     * @return array<int,array<string,mixed>>
     */
    public function merge(array $drafts): array
    {
        $bucket = [];

        foreach ($drafts as $d) {
            $date = $this->bestDateKey($d);
            $home = trim((string) ($d['home'] ?? ''));
            $away = trim((string) ($d['away'] ?? ''));

            if ($date === null || $home === '' || $away === '') {
                continue;
            }

            $key = mb_strtolower($date . '|' . $home . '|' . $away);

            if (!isset($bucket[$key])) {
                $bucket[$key] = [
                    'date' => $date,
                    'home' => $home,
                    'away' => $away,
                    'kickoff_at' => null,
                    'matchday' => null,
                    'status' => 'scheduled',
                    'needs_review' => true,
                    'external_url' => null,
                    'source' => 'multi',
                    // EBB-centric score
                    'ebb_goals' => null,
                    'opp_goals' => null,
                ];
            }

            $bucket[$key] = $this->mergeOne($bucket[$key], $d);
        }

        return array_values($bucket);
    }

    private function bestDateKey(array $d): ?string
    {
        $kick = $d['kickoff_at'] ?? null;
        if ($kick instanceof CarbonImmutable) {
            return $kick->setTimezone(self::TZ)->format('d.m.Y');
        }

        $date = $d['date'] ?? null;
        if (is_string($date) && preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $date)) {
            return $date;
        }

        return null;
    }

    private function mergeOne(array $base, array $d): array
    {
        $src = (string) ($d['source'] ?? '');

        // kickoff
        $kick = $d['kickoff_at'] ?? null;
        if ($kick instanceof CarbonImmutable) {
            if (!$base['kickoff_at']) {
                $base['kickoff_at'] = $kick;
                $base['needs_review'] = (bool) ($d['needs_review'] ?? false);
            } elseif (($base['needs_review'] ?? false) && !($d['needs_review'] ?? true)) {
                $base['kickoff_at'] = $kick;
                $base['needs_review'] = false;
            }
        }

        // matchday (prefer penny)
        if (($base['matchday'] === null) && array_key_exists('matchday', $d) && $d['matchday'] !== null) {
            $base['matchday'] = (int) $d['matchday'];
        }

        // status + score -> EBB centric
        $homeGoals = $d['home_goals'] ?? null;
        $awayGoals = $d['away_goals'] ?? null;

        if ($homeGoals !== null && $awayGoals !== null) {
            $base['status'] = 'finished';

            $home = (string) ($d['home'] ?? $base['home']);
            $away = (string) ($d['away'] ?? $base['away']);

            if ($home === 'Eisbären Berlin') {
                $base['ebb_goals'] = (int) $homeGoals;
                $base['opp_goals'] = (int) $awayGoals;
            } elseif ($away === 'Eisbären Berlin') {
                $base['ebb_goals'] = (int) $awayGoals;
                $base['opp_goals'] = (int) $homeGoals;
            }
        } else {
            if (($base['status'] ?? 'scheduled') !== 'finished' && (($d['status'] ?? null) === 'finished')) {
                $base['status'] = 'finished';
            }
        }

        // external_url preference (prefer penny)
        $ext = $d['external_url'] ?? null;
        if (is_string($ext) && $ext !== '') {
            if (!$base['external_url'] || $src === 'penny') {
                $base['external_url'] = $ext;
            }
        }

        return $base;
    }
}
