<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\Team;

final class TeamResolver
{
    /**
     * Strict DB lookup by name, with a few normalization tricks.
     * No hardcoded team lists.
     */
    public function findByName(string $name): ?Team
    {
        $name = $this->normalize($name);
        if ($name === '') {
            return null;
        }

        // 1) Exact match
        $team = Team::query()->where('name', $name)->first();
        if ($team) {
            return $team;
        }

        // 2) Case-insensitive exact
        $team = Team::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($team) {
            return $team;
        }

        // 3) “Loose” match: collapse whitespace and compare
        $team = Team::query()
            ->get(['id', 'name'])
            ->first(function (Team $t) use ($name) {
                return $this->normalize((string) $t->name) === $name;
            });

        return $team ?: null;
    }

    private function normalize(string $s): string
    {
        $s = trim($s);
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;

        // Normalize common dash variants
        $s = str_replace(["\u{2013}", "\u{2014}"], '-', $s);

        // Strip common noise suffixes that appear in scraped text
        $s = preg_replace('/\bTickets?\b/iu', '', $s) ?? $s;
        $s = trim(preg_replace('/\s+/u', ' ', $s) ?? $s);

        return $s;
    }
}
