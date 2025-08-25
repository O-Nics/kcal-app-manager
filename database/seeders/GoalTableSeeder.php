<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GoalTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $goals = [
            [
                'id' => 1,
                'name' => 'Perte de poids',
                'value' => 'weight_loss',
                'description' => 'Objectif de perte de poids',
                'code' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Prise de poids',
                'value' => 'weight_gain',
                'description' => 'Objectif de prise de poids',
                'code' => 2,
            ],
            [
                'id' => 3,
                'name' => 'Maintien du poids',
                'value' => 'maintain',
                'description' => 'Objectif de maintien du poids',
                'code' => 3,
            ],
            [
                'id' => 4,
                'name' => 'Prise de muscle',
                'value' => 'gain_muscles',
                'description' => 'Objectif de prise de muscle',
                'code' => 4,
            ],
            [
                'id' => 5,
                'name' => 'Perte de poids & Prise de muscle',
                'value' => 'recomp',
                'description' => 'Objectif de perte de poids et prise de muscle',
                'code' => 5,
            ],
        ];

        $countCreated = 0;

        foreach ($goals as $goal) {
            dump("Processing meal type: {$goal['name']}");

            $existingGoals = DB::table('goals')
                ->where('code', $goal['code'])
                ->first();

            if ($existingGoals) {
                dump("Meal type '{$goal['name']}' with code {$goal['code']} already exists.");
                continue;
            }

            try {
                DB::table('goals')->insert([
                    'id' => $goal['id'],
                    'name' => $goal['name'],
                    'code' => $goal['code'],
                    'description' => $goal['description'],
                    'value' => $goal['value'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $countCreated++;
                dump("Meal type '{$goal['name']}' successfully created.");
            } catch (\Throwable $e) {
                dump("Error creating meal type '{$goal['name']}': {$e->getMessage()}");
            }
        }

        dump("{$countCreated} meal types successfully created.");
    }
}
