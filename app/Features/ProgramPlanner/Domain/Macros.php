<?php

namespace App\Features\ProgramPlanner\Domain;

use App\Features\Customer\Services\ProgramPlanning;

final readonly class Macros implements \JsonSerializable
{
    public function __construct(
        public \App\Features\ProgramPlanner\Domain\MacroItem $protein,
        public \App\Features\ProgramPlanner\Domain\MacroItem $fat,
        public \App\Features\ProgramPlanner\Domain\MacroItem $carbs,
    ){}

    public static function fromArray(array $a): self
    {
        return new self(
            protein: \App\Features\ProgramPlanner\Domain\MacroItem::fromArray($a['protein'] ?? []),
            fat: \App\Features\ProgramPlanner\Domain\MacroItem::fromArray($a['fat'] ?? []),
            carbs: \App\Features\ProgramPlanner\Domain\MacroItem::fromArray($a['carbs'] ?? []),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'protein' => $this->protein->jsonSerialize(),
            'fat' => $this->fat->jsonSerialize(),
            'carbs' => $this->carbs->jsonSerialize(),
        ];
    }
}
