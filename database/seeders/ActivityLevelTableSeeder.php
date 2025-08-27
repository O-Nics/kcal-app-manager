<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivityLevelTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activities = [
            [
                'id' => 1,
                'name' => 'Convalescence / alité',
                'value' => 'bedrest',
                'description' => 'Convalescence, alité, repos prolongé – PAL ≈ 1.10',
                'code' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Sédentaire',
                'value' => 'sedentary',
                'description' => 'Peu actif (bureau), < 5k pas/jour – PAL ≈ 1.20',
                'code' => 2,
            ],
            [
                'id' => 3,
                'name' => 'Léger (marche quotidienne)',
                'value' => 'light_walk',
                'description' => 'Marche quotidienne 5–7k pas/jour – PAL ≈ 1.35',
                'code' => 3,
            ],
            [
                'id' => 4,
                'name' => 'Léger + sport 1–2x/sem',
                'value' => 'light_sport',
                'description' => 'Yoga, vélo tranquille 1–2x/semaine – PAL ≈ 1.45',
                'code' => 4,
            ],
            [
                'id' => 5,
                'name' => 'Modéré',
                'value' => 'moderate',
                'description' => '7–10k pas/jour – PAL ≈ 1.55',
                'code' => 5,
            ],
            [
                'id' => 6,
                'name' => 'Modéré + sport 3–4x/sem',
                'value' => 'moderate_sport',
                'description' => 'Muscu/cardio modéré 3–4x/semaine – PAL ≈ 1.60',
                'code' => 6,
            ],
            [
                'id' => 7,
                'name' => 'Actif',
                'value' => 'active',
                'description' => '> 10k pas/jour – PAL ≈ 1.70',
                'code' => 7,
            ],
            [
                'id' => 8,
                'name' => 'Très actif',
                'value' => 'very_active',
                'description' => 'Entraînement quasi quotidien, sportifs réguliers – PAL ≈ 1.85',
                'code' => 8,
            ],
        ];

        $countCreated = 0;

        foreach ($activities as $activity) {
            dump("Processing activity : {$activity['name']}");

            $existing = DB::table('activity_levels')
                ->where('code', $activity['code'])
                ->first();

            if ($existing) {
                DB::table('activity_levels')
                    ->where('code', $activity['code'])
                    ->update([
                        'name' => $activity['name'],
                        'value' => $activity['value'],
                        'description' => $activity['description'],
                        'updated_at' => now(),
                    ]);
                dump("Activity '{$activity['name']}' with code {$activity['code']} updated.");
                continue;
            }

            try {
                DB::table('activity_levels')->insert([
                    'id' => $activity['id'],
                    'name' => $activity['name'],
                    'code' => $activity['code'],
                    'description' => $activity['description'],
                    'value' => $activity['value'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $countCreated++;
                dump("Activity '{$activity['name']}' successfully created.");
            } catch (\Throwable $e) {
                dump("Error creating Activity '{$activity['name']}': {$e->getMessage()}");
            }
        }

        dump("{$countCreated} activities successfully created.");
    }
}
