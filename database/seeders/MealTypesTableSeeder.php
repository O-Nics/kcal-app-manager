<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MealTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mealTypes = [
            [
                'id' => 1,
                'name' => 'Petit-déjeuner',
                'code' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Collation matinale',
                'code' => 2,
            ],
            [
                'id' => 3,
                'name' => 'Déjeuner',
                'code' => 3,
            ],
            [
                'id' => 4,
                'name' => 'Collation de l’après-midi',
                'code' => 4,
            ],
            [
                'id' => 5,
                'name' => 'Dîner',
                'code' => 5,
            ],
        ];

        $countCreated = 0;

        foreach ($mealTypes as $mealType) {
            dump("Processing meal type: {$mealType['name']}");

            $existingMealType = DB::table('meal_types')
                ->where('code', $mealType['code'])
                ->first();

            if ($existingMealType) {
                dump("Meal type '{$mealType['name']}' with code {$mealType['code']} already exists.");
                continue;
            }

            try {
                DB::table('meal_types')->insert([
                    'id' => $mealType['id'],
                    'name' => $mealType['name'],
                    'code' => $mealType['code'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $countCreated++;
                dump("Meal type '{$mealType['name']}' successfully created.");
            } catch (\Throwable $e) {
                dump("Error creating meal type '{$mealType['name']}': {$e->getMessage()}");
            }
        }

        dump("{$countCreated} meal types successfully created.");
    }
}
