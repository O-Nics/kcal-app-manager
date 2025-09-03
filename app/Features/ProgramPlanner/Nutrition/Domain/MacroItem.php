<?php

namespace App\Features\ProgramPlanner\Nutrition\Domain;

final readonly class MacroItem implements \JsonSerializable
{
    public function __construct(
        public int $g,
        public int $kcal,
    ){}

    public static function fromArray(array $a): self
    {
        return new self(g: (int)($a['g'] ?? 0), kcal: (int)($a['kcal'] ?? 0));
    }

    public function jsonSerialize(): array
    {
        return ['g' => $this->g, 'kcal' => $this->kcal];
    }
}
