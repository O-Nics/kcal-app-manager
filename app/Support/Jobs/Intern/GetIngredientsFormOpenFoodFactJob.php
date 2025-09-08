<?php

namespace App\Support\Jobs\Intern;

use App\Models\Brand;
use App\Models\Ingredient;
use App\Models\IngredientType;
use App\Support\Repositories\Document\DocumentRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GetIngredientsFormOpenFoodFactJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct() {
        $this->queue = 'importIngredientDump';
    }
    private const BATCH_SIZE = 100; // Taille des lots pour l'importation
    private const IGNORE = 0; // Taille des lots pour l'importation

    /**
     * Execute the console command.
     */
    public function handle(DocumentRepository $documentRepository): void
    {
        $filePath = 'openfoodfacts-products.jsonl';
//        $filePath = 'dump_open_food_fact/part_aa.jsonl';

        if (!Storage::exists($filePath)) {
            dump("Le fichier {$filePath} n'existe pas.");
            return;
        }

        $handle = Storage::readStream($filePath);

        if (!$handle) {
            dump("Impossible d'ouvrir le fichier {$filePath}.");
            return;
        }

        dump("Début de l'import des ingrédients...");
        $count = 0;
        $batch = [];

        while (($line = fgets($handle)) !== false) {
            $product = json_decode($line, true);

            if (!$product) {
                dump("Impossible de décoder la ligne : {$line}");
                continue;
            }

            $batch[] = $product;

            // Traitez le lot si la taille atteint BATCH_SIZE
            if (count($batch) >= self::BATCH_SIZE) {
                if ($count >= self::IGNORE) {
                    $this->processBatch($batch, $documentRepository);
                    dump("Importation ingredient n°${count}...");
                } else {
                    dump("Ignoré jusqu'au ". self::IGNORE .", actuel : ${count}...");
                }
                $count += count($batch);
                $batch = []; // Réinitialisation du lot
                gc_collect_cycles();
            }
        }

        // Traitez les lignes restantes
        if (!empty($batch) && $count >= self::IGNORE) {
            $this->processBatch($batch, $documentRepository);
            $count += count($batch);
        }

        fclose($handle);
        dump("{$count} ingrédients importés et indexés.");
    }

    /**
     * Traite un lot de produits pour l'importation et l'indexation.
     */
    private function processBatch(array $batch, DocumentRepository $documentRepository): void
    {
        $brandsToSync = [];

        foreach ($batch as $product) {
            $productCode = $product['code'] ?? $product['_id'] ?? null;

            if ($productCode === null || (!isset($product['product_name_fr']) && !isset($product['product_name']))) {
                continue;
            }

            $nutriments = $product['nutriments'] ?? [];
            $nutrimentsEstimated = $product['nutriments_estimated'] ?? [];
            if (!(
                    (
                        (isset($product['product_name_fr']) && trim($product['product_name_fr']) !== '') || // Nom du produit en français
                        (isset($product['product_name']) && trim($product['product_name']) !== '') // Nom générique du produit
                    ) && (
                        isset($nutriments['energy-kcal_100g']) && $nutriments['energy-kcal_100g'] > 0 // Énergie en kcal/100g valide
                    )
                ) || !(
                    // Vérifications des éléments français spécifiques
                    (isset($product['product_name_fr']) && trim($product['product_name_fr']) !== '') ||
                    (isset($product['ingredients_text_fr']) && trim($product['ingredients_text_fr']) !== '') ||
                    (isset($product['languages_tags']) && in_array('en:french', $product['languages_tags'])) ||
                    (isset($product['lang']) && $product['lang'] === 'fr') ||
                    (isset($product['lc']) && $product['lc'] === 'fr')
                )) {
                continue; // Passe à l'itération suivante si les conditions ne sont pas remplies
            }

//            if (!(
//                isset($nutriments['energy-kcal_100g']) &&
//                $nutriments['energy-kcal_100g'] > 0
//            )) {
//                continue;
//            }

            $ingredientType = IngredientType::firstOrCreate(['name' => 'Divers']);
            $name = $product['product_name_fr'] ?? $product['product_name'];
            $prdouctQuantity = $product['product_quantity'] ?? null;
            $productQuantityUnit = $product['product_quantity_unit'] ?? null;
            if (strlen($name) > 256) {
                $name = substr($name, 0, 252) . '...'; // Tronque à 253 caractères et ajoute "..."
            }

            $nutr = [
                'fat'            => $this->pick($nutriments, $nutrimentsEstimated, 'fat_100g'),
                'saturated_fat'  => $this->pick($nutriments, $nutrimentsEstimated, 'saturated-fat_100g'),
                'sugars'         => $this->pick($nutriments, $nutrimentsEstimated, 'sugars_100g'),
                'carbs'          => $this->pick($nutriments, $nutrimentsEstimated, 'carbohydrates_100g'),
                'fiber'          => $this->pick($nutriments, $nutrimentsEstimated, 'fiber_100g'),
                'proteins'       => $this->pick($nutriments, $nutrimentsEstimated, 'proteins_100g'),
                'salt'           => $this->pick($nutriments, $nutrimentsEstimated, 'salt_100g'),
                'sodium'         => $this->pick($nutriments, $nutrimentsEstimated, 'sodium_100g'),

                // Énergie
                'energy_kcal100g'    => $this->pick($nutriments, $nutrimentsEstimated, 'energy-kcal_100g'),
                'energy_kj100g'      => $this->pick($nutriments, $nutrimentsEstimated, 'energy-kj_100g'),

                // Minéraux
                'calcium'        => $this->pick($nutriments, $nutrimentsEstimated, 'calcium_100g'),
                'iron'           => $this->pick($nutriments, $nutrimentsEstimated, 'iron_100g'),
                'copper'         => $this->pick($nutriments, $nutrimentsEstimated, 'copper_100g'),
                'potassium'      => $this->pick($nutriments, $nutrimentsEstimated, 'potassium_100g'),
                'magnesium'      => $this->pick($nutriments, $nutrimentsEstimated, 'magnesium_100g'),
                'manganese'      => $this->pick($nutriments, $nutrimentsEstimated, 'manganese_100g'),
                'phosphorus'     => $this->pick($nutriments, $nutrimentsEstimated, 'phosphorus_100g'),
                'zinc'           => $this->pick($nutriments, $nutrimentsEstimated, 'zinc_100g'),
                'selenium'       => $this->pick($nutriments, $nutrimentsEstimated, 'selenium_100g'),
                'iodine'         => $this->pick($nutriments, $nutrimentsEstimated, 'iodine_100g'),
                'fluoride'       => $this->pick($nutriments, $nutrimentsEstimated, 'fluoride_100g'),
                'chromium'       => $this->pick($nutriments, $nutrimentsEstimated, 'chromium_100g'),
                'cobalt'         => $this->pick($nutriments, $nutrimentsEstimated, 'cobalt_100g'),
                'molybdenum'     => $this->pick($nutriments, $nutrimentsEstimated, 'molybdenum_100g'),
                'chloride'       => $this->pick($nutriments, $nutrimentsEstimated, 'chloride_100g'),
                // rares
                'arsenic'        => $this->pick($nutriments, $nutrimentsEstimated, 'arsenic_100g'),
                'boron'          => $this->pick($nutriments, $nutrimentsEstimated, 'boron_100g'),
                'rubidium'       => $this->pick($nutriments, $nutrimentsEstimated, 'rubidium_100g'),
                'tin'            => $this->pick($nutriments, $nutrimentsEstimated, 'tin_100g'),
                'vanadium'       => $this->pick($nutriments, $nutrimentsEstimated, 'vanadium_100g'),

                // Vitamines
                'vitamin_a'      => $this->pick($nutriments, $nutrimentsEstimated, 'vitamin-a_100g', 'retinol_100g', 'beta-carotene_100g'),
                'vitamin_d'      => $this->pick($nutriments, $nutrimentsEstimated, 'vitamin-d_100g'),
                'vitamin_e'      => $this->pick($nutriments, $nutrimentsEstimated, 'vitamin-e_100g', 'alpha-tocopherol_100g'),
                'vitamin_k'      => $this->pick($nutriments, $nutrimentsEstimated, 'vitamin-k_100g', 'phylloquinone_100g'),
                'vitamin_c'      => $this->pick($nutriments, $nutrimentsEstimated, 'vitamin-c_100g'),
                'vitamin_b1'     => $this->pick($nutriments, $nutrimentsEstimated, 'vitamin-b1_100g'),
                'vitamin_b2'     => $this->pick($nutriments, $nutrimentsEstimated, 'vitamin-b2_100g'),
                'vitamin_b3'     => $this->pick($nutriments, $nutrimentsEstimated, 'niacin_100g', 'vitamin-pp_100g', 'pp_100g'),
                'pantothenic_acid'=>$this->pick($nutriments, $nutrimentsEstimated, 'pantothenic-acid_100g', 'vitamin-b5_100g'),
                'vitamin_b6'     => $this->pick($nutriments, $nutrimentsEstimated, 'vitamin-b6_100g'),
                'vitamin_b9'     => $this->pick($nutriments, $nutrimentsEstimated, 'vitamin-b9_100g', 'folates_100g'),
                'vitamin_b12'    => $this->pick($nutriments, $nutrimentsEstimated, 'vitamin-b12_100g'),
                'biotin'         => $this->pick($nutriments, $nutrimentsEstimated, 'biotin_100g', 'vitamin-b7_100g', 'vitamin-b8_100g'),
                'choline'        => $this->pick($nutriments, $nutrimentsEstimated, 'choline_100g'),

                // Lipides détaillés (optionnels mais utiles)
                'mono_fat'       => $this->pick($nutriments, $nutrimentsEstimated, 'monounsaturated-fat_100g'),
                'poly_fat'       => $this->pick($nutriments, $nutrimentsEstimated, 'polyunsaturated-fat_100g'),
                'trans_fat'      => $this->pick($nutriments, $nutrimentsEstimated, 'trans-fat_100g'),
                'omega3_ala'     => $this->pick($nutriments, $nutrimentsEstimated, 'alpha-linolenic-acid_100g'),
                'omega3_epa'     => $this->pick($nutriments, $nutrimentsEstimated, 'epa_100g'),
                'omega3_dha'     => $this->pick($nutriments, $nutrimentsEstimated, 'dha_100g'),
                'omega6_la'      => $this->pick($nutriments, $nutrimentsEstimated, 'linoleic-acid_100g'),

                // Sucres détaillés
                'lactose'        => $this->pick($nutriments, $nutrimentsEstimated, 'lactose_100g'),
                'maltose'        => $this->pick($nutriments, $nutrimentsEstimated, 'maltose_100g'),
                'fructose'       => $this->pick($nutriments, $nutrimentsEstimated, 'fructose_100g'),
                'glucose'        => $this->pick($nutriments, $nutrimentsEstimated, 'glucose_100g'),
                'sucrose'        => $this->pick($nutriments, $nutrimentsEstimated, 'sucrose_100g'),
                'galactose'      => $this->pick($nutriments, $nutrimentsEstimated, 'galactose_100g'),
                'polyols'        => $this->pick($nutriments, $nutrimentsEstimated, 'polyols_100g'),
                'starch'         => $this->pick($nutriments, $nutrimentsEstimated, 'starch_100g'),

                // Autres
                'cholesterol'    => $this->pick($nutriments, $nutrimentsEstimated, 'cholesterol_100g'),
                'water'          => $this->pick($nutriments, $nutrimentsEstimated, 'water_100g'),
                'alcohol'        => $this->pick($nutriments, $nutrimentsEstimated, 'alcohol_100g'),
                'caffeine'       => $this->pick($nutriments, $nutrimentsEstimated, 'caffeine_100g'),
            ];
            $ingredient = Ingredient::updateOrCreate(
                ['barcode' => $productCode], // Critère de recherche
                [ // Données à insérer ou mettre à jour
                    $nutr,
                    'name' => $name,
                    'ingredient_type_id' => $ingredientType->id,
                    'product_quantity_unit'  => $productQuantityUnit,
                    'product_quantity'  => $prdouctQuantity,
                    'energy_kcal100g' => $nutriments['energy-kcal_100g'],
                    'verified' => false,
                ]
            );


            // Gestion des marques
            if (!empty($product['brands'])) {
                $brands = array_map('trim', explode(',', $product['brands']));
                foreach ($brands as $brandName) {
                    $brandsToSync[$ingredient->id][] = $brandName;
                }
            }

            // Association de l'image AWS
            $awsUrl = $this->generateAwsImageUrl($productCode);
            if ($awsUrl) {
                $this->attachImageFromAws($ingredient, $awsUrl, $documentRepository);
            }
        }

        // Synchroniser les marques
        $this->syncBrands($brandsToSync);

        // Indexer dans Meilisearch
    }

    function pick(array $a, array $b, string ...$keys) {
        foreach ($keys as $k) {
            if (array_key_exists($k, $a) && $a[$k] !== null) return $a[$k];
            if (array_key_exists($k, $b) && $b[$k] !== null) return $b[$k];
        }
        return null;
    }

    /**
     * Génère une URL AWS pour une image de produit.
     */
    private function generateAwsImageUrl(string $productCode): ?string
    {
        $paddedCode = str_pad($productCode, 13, '0', STR_PAD_LEFT);
        $folderPath = preg_replace('/(...)(...)(...)(.*)/', '$1/$2/$3/$4', $paddedCode);
        $filename = '1.jpg';
        return "https://images.openfoodfacts.org/images/products/{$folderPath}/{$filename}";
    }

    /**
     * Attache une image AWS à un ingrédient.
     */
    private function attachImageFromAws(Ingredient $ingredient, string $imageUrl, DocumentRepository $documentRepository): void
    {
        try {
            $uploadedFile = $documentRepository->downloadImage($imageUrl);

            if ($uploadedFile) {
                $documents = $documentRepository->storeOnlyOne([$uploadedFile], $ingredient);
                if ($documents && $documents->isNotEmpty()) {
                    $ingredient->image_id = $documents->first()->id;
                    $ingredient->save();
                    $ingredient->unsearchable();
                    $ingredient->refresh();
                    $ingredient->searchable();
                    dump("Image attachée pour l'ingrédient {$ingredient->name}.");
                }
            }
        } catch (\Exception $e) {
            dump("Exception lors de l'attachement de l'image : {$e->getMessage()} pour l'ingrédient {$ingredient->name}.");
        }
    }

    /**
     * Synchronise les marques avec les ingrédients.
     */
    private function syncBrands(array $brandsToSync): void
    {
        foreach ($brandsToSync as $ingredientId => $brands) {
            $ingredient = Ingredient::find($ingredientId);

            if ($ingredient) {
                foreach ($brands as $brandName) {
                    $brand = Brand::firstOrCreate(
                        [
                            'name' => $brandName,
                            'is_verified' => true,
                        ]
                    );
                    $ingredient->brands()->syncWithoutDetaching($brand->id);
                    $brand->save();
//                    $brand->unsearchable();
//                    $brand->refresh();
//                    $brand->searchable();
                }
            }
        }
    }

    /**
     * Indexe les données dans Meilisearch.
     */

}
