<?php

namespace App\Features\ProgramPlanner\Nutrition\Domain;

final class MetaData implements \JsonSerializable
{
    private array $extra = [];

    public function __construct(
        public readonly ?int    $bmr = null,
        public readonly ?float  $pal = null,
        public readonly ?int    $tdee = null,
        public readonly ?int    $goal_adjust_kcal = null,
        public readonly ?string $goal_code = null,
        public readonly ?float  $weekly_pct = null,
        public readonly ?float  $weekly_kg = null,
        public readonly ?float  $max_weekly_kg = null,
        public readonly ?float  $cap_pct = null,
        public readonly ?string $capped_by = null,
        public readonly ?int    $train_kcal = null,
        public readonly ?int    $rest_kcal = null,
    ){}

    public static function fromArray(array $a): self
    {
        $obj = new self(
            bmr: $a['bmr'] ?? null,
            pal: isset($a['pal']) ? (float)$a['pal'] : null,
            tdee: $a['tdee'] ?? null,
            goal_adjust_kcal: $a['goal_adjust_kcal'] ?? null,
            goal_code: $a['goal_code'] ?? null,
            weekly_pct: isset($a['weekly_pct']) ? (float)$a['weekly_pct'] : null,
            weekly_kg: isset($a['weekly_kg']) ? (float)$a['weekly_kg'] : null,
            max_weekly_kg: isset($a['max_weekly_kg']) ? (float)$a['max_weekly_kg'] : null,
            cap_pct: isset($a['cap_pct']) ? (float)$a['cap_pct'] : null,
            capped_by: $a['capped_by'] ?? null,
            train_kcal: $a['train_kcal'] ?? null,
            rest_kcal: $a['rest_kcal'] ?? null,
        );
        $obj->extra = array_diff_key($a, array_flip([
            'bmr', 'pal', 'tdee', 'goal_adjust_kcal', 'goal_code', 'weekly_pct', 'weekly_kg', 'cap_pct', 'capped_by', 'train_kcal', 'rest_kcal',
        ]));
        return $obj;
    }

    public function __get(string $name)
    {
        return $this->extra[$name] ?? null;
    }

    public function jsonSerialize(): array
    {
        return array_filter([
                'bmr' => $this->bmr,
                'pal' => $this->pal,
                'tdee' => $this->tdee,
                'goal_adjust_kcal' => $this->goal_adjust_kcal,
                'goal_code' => $this->goal_code,
                'weekly_pct' => $this->weekly_pct,
                'weekly_kg' => $this->weekly_kg,
                'cap_pct' => $this->cap_pct,
                'capped_by' => $this->capped_by,
                'train_kcal' => $this->train_kcal,
                'rest_kcal' => $this->rest_kcal,
            ]) + $this->extra;
    }
}
