<?php

namespace App\Features\ProgramPlanner\Nutrition\Domain;

final readonly class MacroRatios implements \JsonSerializable
{
    public function __construct(
        public int $protein,
        public int $fat,
        public int $carbs,
    ){}

    public static function fromArray(array $a): self
    {
        return new self(
            protein: (int)($a['protein'] ?? 0),
            fat: (int)($a['fat'] ?? 0),
            carbs: (int)($a['carbs'] ?? 0),
        );
    }

    public function jsonSerialize(): array
    {
        return ['protein' => $this->protein, 'fat' => $this->fat, 'carbs' => $this->carbs];
    }
}
