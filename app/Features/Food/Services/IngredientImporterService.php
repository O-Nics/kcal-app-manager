<?php

namespace App\Features\Food\Services;

use AllowDynamicProperties;
use App\Models\Brand;
use App\Models\Ingredient;
use App\Models\IngredientType;
use App\Support\Data\ImageData;
use App\Support\Repositories\Document\DocumentRepository;
use Illuminate\Support\Str;

#[AllowDynamicProperties] class IngredientImporterService
{


    public function __construct()
    {
        $this->documentRepository = resolve(DocumentRepository::class);
    }
    private const TYPE_PRIORITY = [
        'Boissons' => 100,
        'Plats préparés' => 90,
        'Sauces et condiments' => 80,
        'Pâtisserie et boulangerie' => 70,
        'Produits sucrés' => 60,
        'Substituts végétaux' => 55,
        'Viandes' => 50,
        'Poissons et fruits de mer' => 50,
        'Produits laitiers' => 45,
        'Céréales' => 40,
        'Légumineuses' => 35,
        'Conserves et bocaux' => 30,
        'Épicerie salée' => 30,
        'Noix et graines' => 25,
        'Herbes et épices' => 20,
        'Fruits secs' => 20,
        'Légumes' => 15,
        'Fruits' => 10,
    ];

    /** @var array|null */
    private static ?array $KEYWORDS_CACHE = null;

    private function getProductName(array $product): ?string
    {
        foreach (['product_name_fr','product_name','product_name_en','generic_name_fr','generic_name'] as $k) {
            if (!empty($product[$k]) && is_string($product[$k])) {
                $name = trim($product[$k]);
                if ($name !== '') {
                    return mb_strlen($name) > 255 ? (mb_substr($name, 0, 252) . '...') : $name;
                }
            }
        }
        return null;
    }

    private function getCategoriesFromProduct(array $product): array
    {
        $categories = [];
        if (!empty($product['categories']) && is_string($product['categories'])) {
            $categories = array_merge($categories, array_map('trim', explode(',', $product['categories'])));
        }
        if (!empty($product['categories_tags']) && is_array($product['categories_tags'])) {
            $categories = array_merge($categories, $product['categories_tags']);
        }
        if (!empty($product['categories_hierarchy']) && is_array($product['categories_hierarchy'])) {
            $categories = array_merge($categories, $product['categories_hierarchy']);
        }
        return array_values(array_unique(array_filter($categories), SORT_STRING));
    }


    public function processBatch(mixed $product): void
    {
        $brandsToSync = [];
        $now = now();

            $productCode = $product['code'] ?? $product['_id'] ?? null;
            if ($productCode === null) {
                return;
            }

            // --- Pré-lecture nutriments + filtres d'éligibilité ---
            $nutriments = $product['nutriments'] ?? [];
            $nutrimentsEstimated = $product['nutriments_estimated'] ?? [];

            $name = $this->getProductName($product);
            if ($name === null) {
                return;
            }


            if (!isset($nutriments['energy-kcal_100g']) || $nutriments['energy-kcal_100g'] <= 0) {
                return;
            }

            // --- BARCODE ---
            $barcode = $productCode;

            // --- QUANTITY + UNIT (fallbacks : product_quantity / "350 g" → 350 & g) ---
            [$qtyFromString, $unitFromString] = $this->parseQuantity($product['quantity'] ?? null);
            $productQuantity = $product['product_quantity'] ?? $qtyFromString;
            $productQuantityUnit = $product['product_quantity_unit']
                ?? $unitFromString
                ?? 'g';


            // --- CONSERVATION CONDITIONS (fallbacks) ---
            $conservationConditions = $product['conservation_conditions_fr']
                ?? $product['conservation_conditions']
                ?? $product['conservation']
                ?? $product['packaging_text_fr']
                ?? $product['packaging_text']
                ?? null;

            $nutriScore = $this->extractNutriScore($product);

            $ecoScore   = $this->extractEcoScore($product);

            // --- Nutriments (avec tes picks) ---
            $finalNutriment = [
                // --- Nutri-Score & Environmental score (JSON) ---

                'fat'            => $this->pick($nutriments, $nutrimentsEstimated, 'fat_100g'),
                'saturated_fat'  => $this->pick($nutriments, $nutrimentsEstimated, 'saturated-fat_100g'),
                'sugars'         => $this->pick($nutriments, $nutrimentsEstimated, 'sugars_100g'),
                'carbs'          => $this->pick($nutriments, $nutrimentsEstimated, 'carbohydrates_100g'),
                'fiber'           => $this->pick($nutriments, $nutrimentsEstimated, 'fiber_100g'),
                'proteins'       => $this->pick($nutriments, $nutrimentsEstimated, 'proteins_100g'),
                'salt'           => $this->pick($nutriments, $nutrimentsEstimated, 'salt_100g'),
                'sodium'         => $this->pick($nutriments, $nutrimentsEstimated, 'sodium_100g'),

                // Énergie
                'energy_kcal100g'=> $this->pick($nutriments, $nutrimentsEstimated, 'energy-kcal_100g'),
                'energy_kj100g'  => $this->pick($nutriments, $nutrimentsEstimated, 'energy-kj_100g', 'energy_100g'),

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
                'fluoride'        => $this->pick($nutriments, $nutrimentsEstimated, 'fluoride_100g'),
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

                // Lipides détaillés
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


            $tempIngredientTypeId = IngredientType::where('code', 21)->value('id');
            // --- UPSERT INGREDIENT ---
            $attributes = $finalNutriment + [
                'name'                   => $name,
                'barcode'                => $barcode,
                'ingredient_type_id'     => $tempIngredientTypeId,
                'product_quantity'       => $productQuantity !== null ? (float) $productQuantity : null,
                'product_quantity_unit'  => $productQuantityUnit,
                'reference_value'        => 100,
                'conservation_conditions'=> $conservationConditions,
                'nutriscore'             => $nutriScore,
                'environmental_score'    => $ecoScore,
                'verified'               => false,
            ];

            $ingredient = Ingredient::updateOrCreate(
                ['barcode' => $productCode],
                $attributes
            );

            $categories = $this->getCategoriesFromProduct($product);
            $this->verifyIngredientCategory($ingredient, $categories);

            // Vérification/ suggestion de type (pas d'association ici)

            // --- Marques à synchroniser ---
            if (!empty($product['brands'])) {
                $brands = array_map('trim', explode(',', $product['brands']));
                foreach ($brands as $brandName) {
                    if ($brandName !== '') {
                        $brandsToSync[$ingredient->id][] = $brandName;
                    }
                }
            }

            $this->syncBrands($brandsToSync);

//             --- Image AWS (optionnel) ---
            if ($awsUrl = $this->generateAwsImageUrl($productCode)) {
                $this->attachImageFromAws($ingredient, $awsUrl);
            }


        // Sync marques
    }
    private function parseQuantity(?string $q): array
    {
        if (!$q) return [null, null];
        if (preg_match('/([0-9]+(?:[\\.,][0-9]+)?)\\s*([a-zA-Zµ]+)?/u', $q, $m)) {
            $val = isset($m[1]) ? (float) str_replace(',', '.', $m[1]) : null;
            $unit= $m[2] ?? null;
            return [$val, $unit];
        }
        return [null, null];
    }
    function pick(array $a, array $b, string ...$keys) {
        foreach ($keys as $k) {
            if (array_key_exists($k, $a) && $a[$k] !== null) return $a[$k];
            if (array_key_exists($k, $b) && $b[$k] !== null) return $b[$k];
        }
        return null;
    }

    /**
     * Returns true if at least one of the given keys exists in the array and is a non-empty string after trim.
     */


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
    private function attachImageFromAws(Ingredient $ingredient, string $imageUrl): void
    {
        try {
            $uploadedFile = $this->documentRepository->downloadImage($imageUrl);

            if ($uploadedFile) {
                $file = new ImageData();
                $file->file = $uploadedFile;
                $documents = $this->documentRepository->store([$file], $ingredient);
                if ($documents->isNotEmpty()) {
                    $ingredient->image_id = $documents->first()->id;
                    $ingredient->save();
//                    $ingredient->unsearchable();
//                    $ingredient->refresh();
//                    $ingredient->searchable();
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
        dump('brand : ' . count($brandsToSync) . json_encode($brandsToSync));
        foreach ($brandsToSync as $ingredientId => $brands) {
            $ingredient = Ingredient::find($ingredientId);
            if (!$ingredient) {
                continue;
            }

            // Normalize + unique brand names
            $normalized = [];
            foreach ($brands as $brandName) {
                $name = trim((string)$brandName);
                if ($name === '') continue;
                $key = (string) Brand::makeNameNorm($brandName);
                $normalized[$key] = $name; // keep original casing for display
            }

            foreach ($normalized as $norm => $originalName) {
                $brand = Brand::firstOrCreate(
                    ['name_norm' => $norm],
                    ['name' => $originalName, 'is_verified' => false]
                );
                $ingredient->brands()->syncWithoutDetaching($brand->id);
            }
        }
    }

    /**
     * Indexe les données dans Meilisearch.
     */

    /**
     * Extracts Nutri-Score from the product array.
     */
    private function extractNutriScore(array $p): ?array
    {

        $version = $p['nutriscore_version']
            ?? (isset($p['nutriscore']['2023']) ? '2023'
                : (isset($p['nutriscore']['2021']) ? '2021' : null));

        $grade = $p['nutriscore_grade']
            ?? ($p['nutrition_grades'] ?? null)
            ?? ($version && isset($p['nutriscore'][$version]['grade'])
                ? $p['nutriscore'][$version]['grade']
                : null);

        $score = $p['nutriscore_score']
            ?? ($version && isset($p['nutriscore'][$version]['score'])
            ? $p['nutriscore'][$version]['score']
            : null)
            ?? ($p['nutriscore_data']['score'] ?? null);

        if($version == null && ($grade == null || $grade == 'unknown') && $score == null){
            return null;
        }

        return [
            'version' => $version,
            'grade'   => $grade,
            'score'   => is_numeric($score) ? (int) $score : null,
        ];
    }

    /**
     * Extracts Environmental Score from the product array.
     */
    private function extractEcoScore(array $p): ?array
    {
        $grade = $p['environmental_score_grade']
            ?? ($p['ecoscore_grade'] ?? null)
            ?? ($p['environmental_score_data']['grade'] ?? null);

        $score = $p['environmental_score_score']
            ?? ($p['environmental_score_data']['score'] ?? null);

        $agribalyseCode = $p['environmental_score_data']['agribalyse']['agribalyse_food_code']
            ?? ($p['categories_properties']['agribalyse_food_code:en'] ?? null);

        if(($grade == null || $grade == 'unknown') && $score == null && $agribalyseCode == null){
            return null;
        }
        return [
            'grade'           => $grade,
            'score'           => is_numeric($score) ? (int) $score : null,
            'agribalyse_code' => $agribalyseCode,
        ];
    }

    /**
     * Vérifie la catégorie probable d'un ingrédient selon ses catégories OFF et un dictionnaire de mots-clés.
     * Ne réalise AUCUNE association. Se contente de détecter et de signaler via dump().
     */
    private function verifyIngredientCategory(Ingredient $ingredient, array $offCategories,): void
    {
        $suggestedCode = $this->suggestTypeFromCategories($offCategories, $ingredient->name);
        if ($suggestedCode !== null) {
            $ingredientType = IngredientType::query()->where('code', $suggestedCode)->first();
            if($ingredientType){
                $ingredient->ingredient_type_id = $ingredientType->id;
                $ingredient->save();
            }
        }
    }

    /**
     * Propose un type (parmi ta liste métier) à partir des catégories/textes OFF et éventuellement du nom produit.
     * Retourne le code numérique du type ou null si non déterminé.
     */
    private function suggestTypeFromCategories(array $offCategories, ?string $productName = null): ?int
    {
        $map = $this->keywordsMap();

        // Priorité par type (plus haut = plus prioritaire) — clés par LABEL
        $priority = self::TYPE_PRIORITY;

        // Normalisation des entrées OFF (slugs + texte brut)
        $slugs = [];
        $texts = [];
        foreach ($offCategories as $raw) {
            if (!is_string($raw)) continue;
            $raw = trim($raw);
            if ($raw === '') continue;
            if (str_contains($raw, ':') || str_contains($raw, '-')) {
                $slugs[] = strtolower($raw);
            }
            $texts[] = $this->normalize($raw);
        }

        // Ajoute le nom produit pour affiner (ou fallback si vide)
        if (is_string($productName) && trim($productName) !== '') {
            $texts[] = $this->normalize($productName);
        }

        // Score par type (on tracke le LABEL et le CODE)
        $bestLabel = null;
        $bestCode = null;
        $bestScore = 0;

        foreach ($map as $label => $keys) {
            $score = 0;
            $matched = false;

            // 1) Slugs OFF (match exact ou préfixe)
            foreach (($keys['slugs'] ?? []) as $slug) {
                $slug = strtolower($slug);
                foreach ($slugs as $s) {
                    if ($s === $slug || str_starts_with($s, $slug)) {
                        $score += 5; // slug = signal fort
                        $matched = true;
                    }
                }
            }

            // 2) Termes FR/EN dans le texte/categories ou nom produit
            $joined = ' ' . implode(' ', $texts) . ' ';
            foreach (($keys['fr'] ?? []) as $term) {
                $term = $this->normalize($term);
                if ($term !== '' && str_contains($joined, ' ' . $term . ' ')) {
                    $score += 2;
                    $matched = true;
                }
            }
            foreach (($keys['en'] ?? []) as $term) {
                $term = $this->normalize($term);
                if ($term !== '' && str_contains($joined, ' ' . $term . ' ')) {
                    $score += 2;
                    $matched = true;
                }
            }

            // 3) Pondération par priorité si match trouvé
            if ($matched) {
                $score += $priority[$label] ?? 0;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestLabel = $label;
                $bestCode = $keys['code'] ?? null; // on retient le code
            }
        }

        // Seuil minimal pour éviter des faux positifs faibles
        return $bestScore >= 10 ? $bestCode : null;
    }

    /**
     * Dictionnaire minimal de mots-clés FR/EN + slugs OFF par type, avec code numérique.
     * TIP: déplace ceci dans un config() si besoin et enrichis au fil de l'eau.
     */
    private function keywordsMap(): array
    {
        if (self::$KEYWORDS_CACHE !== null) {
            return self::$KEYWORDS_CACHE;
        }
        return self::$KEYWORDS_CACHE = [
            'Fruits' => [
                'code' => 1,
                'fr' => ['fruit','fruits','pomme','banane','poire','orange','citron','raisin','kiwi','mangue','fraise','framboise','myrtille','abricot','pêche','ananas','pastèque','melon','prune','cerise','compote'],
                'en' => ['fruit','fruits','apple','banana','pear','orange','lemon','grape','kiwi','mango','strawberry','raspberry','blueberry','apricot','peach','pineapple','watermelon','melon','plum','cherry','compote'],
                'slugs' => ['en:fruits','en:fruit-compotes','en:fruit-jams','en:dried-fruits']
            ],
            'Légumes' => [
                'code' => 2,
                'fr' => ['légume','légumes','carotte','tomate','pomme de terre','brocoli','courgette','salade','laitue','oignon','ail','poivron','épinard','haricot vert','chou','betterave','concombre','poireau'],
                'en' => ['vegetable','vegetables','carrot','tomato','potato','broccoli','zucchini','lettuce','onion','garlic','pepper','spinach','green bean','cabbage','beetroot','cucumber','leek'],
                'slugs' => ['en:vegetables','en:canned-vegetables','en:vegetable-purees','en:legume-vegetables']
            ],
            'Céréales' => [
                'code' => 3,
                'fr' => ['céréale','céréales','riz','blé','avoine','orge','maïs','seigle','quinoa','boulgour','semoule','muesli','flocons','granola'],
                'en' => ['cereal','cereals','rice','wheat','oat','barley','corn','rye','quinoa','bulgur','semolina','muesli','flakes','granola'],
                'slugs' => ['en:cereals-and-potatoes','en:cereals-and-their-products','en:breakfast-cereals','en:cereal-flakes','en:rice']
            ],
            'Légumineuses' => [
                'code' => 4,
                'fr' => ['légumineuse','légumineuses','lentille','pois chiche','haricot','fève','pois cassés','soja'],
                'en' => ['legume','pulses','lentil','chickpea','bean','fava','split peas','soy'],
                'slugs' => ['en:legumes','en:pulses','en:lentils','en:chickpeas','en:red-beans','en:white-beans']
            ],
            'Viandes' => [
                'code' => 5,
                'fr' => ['viande','bœuf','boeuf','porc','agneau','veau','volaille','charcuterie','jambon','saucisson'],
                'en' => ['meat','beef','pork','lamb','veal','poultry','cold-cuts','ham','sausage'],
                'slugs' => ['en:meats','en:meat-products','en:cured-meats','en:charcuteries']
            ],
            'Poissons et fruits de mer' => [
                'code' => 6,
                'fr' => ['poisson','poissons','fruits de mer','saumon','thon','sardine','maquereau','morue','cabillaud','crevette','moule','huître','calamar','surimi'],
                'en' => ['fish','seafood','salmon','tuna','sardine','mackerel','cod','prawn','shrimp','mussel','oyster','squid','surimi'],
                'slugs' => ['en:fish-and-seafood','en:canned-fish','en:smoked-fish','en:seafood']
            ],
            'Œufs' => [
                'code' => 7,
                'fr' => ['œuf','oeuf','œufs','oeufs','blanc d’œuf','jaune d’œuf','ovoproduits'],
                'en' => ['egg','eggs','egg white','egg yolk'],
                'slugs' => ['en:eggs','en:egg-products']
            ],
            'Produits laitiers' => [
                'code' => 8,
                'fr' => ['produit laitier','lait','yaourt','fromage','beurre','crème','lait fermenté','fromage frais','petit-suisse'],
                'en' => ['dairy','milk','yogurt','cheese','butter','cream','fermented milk','fresh cheese','cottage cheese'],
                'slugs' => ['en:dairies','en:milks','en:cheeses','en:butters','en:creams','en:yogurts']
            ],
            'Huiles et matières grasses' => [
                'code' => 9,
                'fr' => ['huile','huiles','graisse','matières grasses','margarine','saindoux','beurre clarifié','ghee'],
                'en' => ['oil','oils','fat','fats','margarine','lard','ghee','clarified butter'],
                'slugs' => ['en:fats','en:vegetable-oils','en:animal-fats','en:margarines','en:olive-oils']
            ],
            'Noix et graines' => [
                'code' => 10,
                'fr' => ['noix','noisette','amande','pistache','noix de cajou','cacahuète','graines','tournesol','sésame','courge','chia','lin'],
                'en' => ['nut','nuts','hazelnut','almond','pistachio','cashew','peanut','seeds','sunflower','sesame','pumpkin seed','chia','flax'],
                'slugs' => ['en:nuts','en:seeds','en:oilseeds','en:peanuts-and-nuts']
            ],
            'Herbes et épices' => [
                'code' => 11,
                'fr' => ['herbes','épices','aromates','basilic','persil','coriandre','curry','paprika','poivre','sel','ail en poudre','gingembre','curcuma'],
                'en' => ['herb','herbs','spice','spices','basil','parsley','coriander','curry','paprika','pepper','salt','garlic powder','ginger','turmeric'],
                'slugs' => ['en:herbs','en:spices','en:salt','en:pepper','en:seasonings']
            ],
            'Boissons' => [
                'code' => 12,
                'fr' => ['boisson','eau','eaux','jus','nectar','soda','limonade','thé','café','infusion','alcool','vin','bière','spiritueux','sirop'],
                'en' => ['drink','beverage','water','juice','nectar','soda','lemonade','tea','coffee','infusion','alcohol','wine','beer','spirits','syrup'],
                'slugs' => ['en:beverages','en:waters','en:juices-and-nectars','en:soft-drinks','en:teas','en:coffees','en:alcoholic-beverages','en:wines','en:beers','en:syrups']
            ],
            'Pâtisserie et boulangerie' => [
                'code' => 13,
                'fr' => ['boulangerie','pâtisserie','pain','baguette','viennoiserie','croissant','brioche','biscuit','sablé','gâteau','tarte','pâte à tarte','pâte feuilletée'],
                'en' => ['bakery','pastry','bread','baguette','viennoiserie','croissant','brioche','biscuit','cookie','cake','pie','pastry dough','puff pastry'],
                'slugs' => ['en:bakery','en:breads','en:sliced-breads','en:viennoiseries','en:cakes','en:biscuits-and-cakes','en:pastries']
            ],
            'Sauces et condiments' => [
                'code' => 14,
                'fr' => ['sauce','sauces','condiment','ketchup','moutarde','mayonnaise','aioli','pesto','soja','soja sauce','tamari','vinaigrette','vinaigre'],
                'en' => ['sauce','sauces','condiment','ketchup','mustard','mayonnaise','aioli','pesto','soy sauce','tamari','dressing','vinegar'],
                'slugs' => ['en:sauces','en:condiments','en:ketchups','en:mustards','en:mayonnaises','en:soy-sauces','en:salad-dressings','en:vinegars']
            ],
            'Produits sucrés' => [
                'code' => 15,
                'fr' => ['sucré','sucreries','confiserie','chocolat','bonbon','caramel','chocolaterie','pâte à tartiner','confiture','gelée','miel','sirop d’érable'],
                'en' => ['sweet','sweets','confectionery','chocolate','candy','caramel','spread','chocolate spread','jam','jelly','honey','maple syrup'],
                'slugs' => ['en:sweet-snacks','en:confectioneries','en:chocolate-products','en:chocolate-candies','en:sweet-spreads','en:hazelnut-spreads','en:chocolate-spreads','en:jams']
            ],
            'Plats préparés' => [
                'code' => 16,
                'fr' => ['plat préparé','plat cuisiné','plats cuisinés','surgelé salé','pizza','sandwich','wrap','soupe','lasagne','quiche','burger','nuggets'],
                'en' => ['ready meal','prepared meal','frozen meal','pizza','sandwich','wrap','soup','lasagna','quiche','burger','nuggets'],
                'slugs' => ['en:prepared-meals','en:frozen-foods','en:pizzas','en:sandwiches','en:soups']
            ],
            'Fruits secs' => [
                'code' => 17,
                'fr' => ['fruit sec','fruits secs','raisin sec','abricot sec','dattes','figues sèches','pruneaux','cranberries séchées'],
                'en' => ['dried fruit','raisins','dried apricots','dates','dried figs','prunes','dried cranberries'],
                'slugs' => ['en:dried-fruits','en:raisins','en:dates','en:apricots']
            ],
            'Conserves et bocaux' => [
                'code' => 18,
                'fr' => ['conserve','conserves','bocal','bocaux','en boîte','appertisé','sardines en boîte','thon en conserve','cornichons','légumes en bocal','compote en bocal'],
                'en' => ['canned','can','jar','preserve','canned fish','canned tuna','pickles','jarred vegetables','jarred fruit'],
                'slugs' => ['en:canned-foods','en:canned-fish','en:canned-vegetables','en:pickles','en:jars']
            ],
            'Épicerie salée' => [
                'code' => 19,
                'fr' => ['épicerie salée','apéro','apéritif','chips','crackers','bretzels','biscuits salés','snack salé','tortilla chips','popcorn'],
                'en' => ['savory grocery','appetizer','chips','crisps','crackers','pretzels','savory biscuits','savory snack','tortilla chips','popcorn'],
                'slugs' => ['en:salty-snacks','en:chips-and-fries','en:crisps','en:crackers','en:appetizers']
            ],
            'Substituts végétaux' => [
                'code' => 20,
                'fr' => ['substitut','végétal','substituts végétaux','tofu','seitan','tempeh','falafel','steak végétal','lait végétal','boisson végétale','yaourt végétal'],
                'en' => ['plant-based','plant based','meat substitute','tofu','seitan','tempeh','falafel','veggie burger','plant milk','non-dairy milk','plant-based yogurt'],
                'slugs' => ['en:plant-based-foods-and-beverages','en:plant-based-foods','en:meat-substitutes','en:tofu','en:seitan','en:tempeh','en:plant-based-milks','en:vegetable-based-foods']
            ],
        ];
    }

    /** Normalize a text for matching (lowercase, no accents, spaces → single spaces). */
    private function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        $s = preg_replace('/[^a-z0-9\-\s:]/', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }
}
