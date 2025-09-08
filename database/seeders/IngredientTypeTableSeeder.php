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
            'Fruits',
            'Légumes',
            'Céréales',
            'Légumineuses',
            'Viandes',
            'Poissons et fruits de mer',
            'Œufs',
            'Produits laitiers',
            'Huiles et matières grasses',
            'Noix et graines',
            'Herbes et épices',
            'Boissons',
            'Pâtisserie et boulangerie',
            'Sauces et condiments',
            'Produits sucrés',
            'Plats préparés',
            'Fruits secs',
            'Conserves et bocaux',
            'Épicerie salée',
            'Substituts végétaux',
        ];

        $now = now();
        $rows = array_map(fn ($name) => [
            'name' => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ], $types);

        DB::table('ingredient_types')->upsert($rows, ['name'], ['updated_at']);

        dump(count($types) . ' ingredient types processed (inserted/updated).');
    }
}
