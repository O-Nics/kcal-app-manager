<?php

namespace App\Support\Jobs\Intern;

use App\Features\Food\Services\IngredientImporterService;
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
        $ingredientService = new IngredientImporterService();
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
                    foreach ($batch as  $product){
                        $ingredientService->processBatch($product);
                    }
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
            foreach ($batch as  $product){
                $ingredientService->processBatch($product);
            }
            $count += count($batch);
        }

        fclose($handle);
        dump("{$count} ingrédients importés et indexés.");
    }


}

