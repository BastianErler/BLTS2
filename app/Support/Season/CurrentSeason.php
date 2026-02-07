<?php

declare(strict_types=1);

namespace App\Support\Season;

use App\Models\Season;
use Carbon\CarbonImmutable;

final class CurrentSeason
{
    public const TZ = 'Europe/Berlin';

    /**
     * Saison: 01.08.(startYear) 00:00:00 -> 31.07.(startYear+1) 23:59:59
     *
     * @return array{0:CarbonImmutable,1:CarbonImmutable}
     */
    public static function seasonWindowForDate(CarbonImmutable $date): array
    {
        $d = $date->setTimezone(self::TZ);

        $startYear = ($d->month >= 8) ? $d->year : ($d->year - 1);
        $endYear = $startYear + 1;

        $start = CarbonImmutable::create($startYear, 8, 1, 0, 0, 0, self::TZ);
        $end   = CarbonImmutable::create($endYear, 7, 31, 23, 59, 59, self::TZ);

        return [$start, $end];
    }

    public static function seasonNameForWindow(CarbonImmutable $start, CarbonImmutable $end): string
    {
        return sprintf('%02d/%02d', (int) $start->format('y'), (int) $end->format('y'));
    }

    public static function resolveOrCreateCurrentSeason(?CarbonImmutable $now = null): Season
    {
        $now = ($now ?? CarbonImmutable::now(self::TZ))->setTimezone(self::TZ);
        [$start, $end] = self::seasonWindowForDate($now);
        $name = self::seasonNameForWindow($start, $end);

        /** @var Season $season */
        $season = Season::query()->firstOrCreate(
            ['name' => $name],
            [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'is_active' => true,
                'phase_1_multiplier' => 1.0,
                'phase_2_multiplier' => 1.5,
                'phase_3_multiplier' => 2.0,
                'playoff_multiplier' => 3.0,
            ],
        );

        Season::query()->whereKeyNot($season->getKey())->update(['is_active' => false]);
        $season->update(['is_active' => true]);

        return $season;
    }

    public static function isInWindow(CarbonImmutable $kickoffAt, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        $t = $kickoffAt->setTimezone(self::TZ);
        return $t->greaterThanOrEqualTo($start) && $t->lessThanOrEqualTo($end);
    }
}
