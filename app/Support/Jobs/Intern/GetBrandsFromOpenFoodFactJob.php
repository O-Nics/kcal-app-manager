<?php

namespace App\Support\Jobs\Intern;

use App\Models\Brand;
use App\Support\Repositories\Ingredient\IngredientRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GetBrandsFromOpenFoodFactJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(IngredientRepository $ingredientRepository): void
    {
        dump('Fetching brands from Open Food Facts (streaming)...');

        $batch = [];
        $batchSize = 1000; // DB upsert batch size
        $now = now();
        $count = 0;
        $seen = [];

        foreach ($this->streamBrandNames() as $brandName) {
            $key = Brand::makeNameNorm($brandName);
            if ($key === '' || isset($seen[$key])) {
                continue; // skip duplicates and empties
            }
            $seen[$key] = true;
            $batch[] = [
                'name' => $brandName,
                'name_norm' => $key,
                'is_verified' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= $batchSize) {
                Brand::upsert($batch, ['name_norm'], ['name', 'is_verified', 'updated_at']);
                $count += count($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            Brand::upsert($batch, ['name_norm'], ['name', 'is_verified', 'updated_at']);
            $count += count($batch);
        }

        dump("Imported/updated ~{$count} brands (batched upsert).");
        dump('Done.');
    }

    /**
     * Stream brand names from OFF without loading everything in memory.
     * @return \Generator<string>
     */
    private function streamBrandNames(): \Generator
    {
        $token = Str::random(32);
        $headers = [
            'User-Agent' => sprintf(
                "MyApp/0.1 (App: Hinti; System: Apple; User_id: %s; Support: nicolasplanche972@gmail.com)",
                $token
            ),
            'Accept' => 'application/json',
        ];

        $baseUrl = 'https://world.openfoodfacts.org/brands.json';
        $page = 1;
        $pageSize = 1000; // OFF allows up to 1000 per page

        try {
            do {
                $response = Http::withHeaders($headers)
                    ->timeout(20)
                    ->retry(3, 500)
                    ->get($baseUrl, [
                        'page' => $page,
                        'page_size' => $pageSize,
                    ]);

                if (!$response->successful()) {
                    Log::warning('Open Food Facts response was not successful.', [
                        'url' => $baseUrl,
                        'status' => $response->status(),
                        'page' => $page,
                        'page_size' => $pageSize,
                    ]);
                    break;
                }

                $json = $response->json();
                $tags = $json['tags'] ?? [];

                $fetchedCount = is_array($tags) ? count($tags) : 0;
                dump("Fetched page {$page} with {$fetchedCount} brands...");

                if ($fetchedCount === 0) {
                    break;
                }

                // Yield each brand name, use associative set outside to dedupe if needed at DB unique index level
                foreach ($tags as $tag) {
                    if (isset($tag['name']) && is_string($tag['name'])) {
                        dump(" - " . $tag['name']);
                        $name = trim($tag['name']);
                        if ($name !== '') {
                            yield $name;
                        }
                    }
                }

                $page++;
            } while ($fetchedCount === $pageSize);
        } catch (\Throwable $e) {
            Log::error('Failed to stream brands from Open Food Facts: ' . $e->getMessage(), [
                'url' => $baseUrl,
            ]);
        }
    }
}
