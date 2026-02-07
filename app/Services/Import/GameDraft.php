<?php

declare(strict_types=1);

namespace App\Services\Import;

use Carbon\CarbonImmutable;

final class GameDraft
{
    public function __construct(
        public readonly string $source,
        public readonly ?string $externalUrl,
        public readonly ?string $date, // dd.mm.YYYY
        public readonly ?CarbonImmutable $kickoffAt, // may be null from some sources
        public readonly ?int $matchday,
        public readonly string $home,
        public readonly string $away,
        public readonly string $status, // scheduled|finished
        public readonly ?int $homeGoals,
        public readonly ?int $awayGoals,
        public readonly bool $needsReview,
    ) {}

    public function withNormalizedTeams(TeamResolver $resolver): self
    {
        return new self(
            source: $this->source,
            externalUrl: $this->externalUrl,
            date: $this->date,
            kickoffAt: $this->kickoffAt,
            matchday: $this->matchday,
            home: $resolver->normalize($this->home),
            away: $resolver->normalize($this->away),
            status: $this->status,
            homeGoals: $this->homeGoals,
            awayGoals: $this->awayGoals,
            needsReview: $this->needsReview,
        );
    }
}
