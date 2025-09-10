<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IngredientTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            1 => 'Fruits',
            2 => 'Légumes',
            3 => 'Céréales',
            4 => 'Légumineuses',
            5 => 'Viandes',
            6 => 'Poissons et fruits de mer',
            7 => 'Œufs',
            8 => 'Produits laitiers',
            9 => 'Huiles et matières grasses',
            10 => 'Noix et graines',
            11 => 'Herbes et épices',
            12 => 'Boissons',
            13 => 'Pâtisserie et boulangerie',
            14 => 'Sauces et condiments',
            15 => 'Produits sucrés',
            16 => 'Plats préparés',
            17 => 'Fruits secs',
            18 => 'Conserves et bocaux',
            19 => 'Épicerie salée',
            20 => 'Substituts végétaux',
            21 => 'Divers',
        ];

        $now = now();
        $rows = array_map(fn ($code, $name) => [
            'code' => $code,
            'name' => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ], array_keys($types), $types);

        DB::table('ingredient_types')->upsert($rows, ['code', 'name'], ['updated_at']);

        dump(count($types) . ' ingredient types processed (inserted/updated).');
    }
}
