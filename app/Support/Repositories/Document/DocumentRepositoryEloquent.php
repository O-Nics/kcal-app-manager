<?php

namespace App\Support\Repositories\Document;

use App\Models\Document;
use App\Models\Documentable;
use App\Support\Repositories\BaseRepository;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Spatie\LaravelData\DataCollection;
use Throwable;

class DocumentRepositoryEloquent extends BaseRepository implements DocumentRepository
{
    protected string $model = Document::class;

//    /**
//     * @param UploadedFile[] $files
//     * @param Documentable $documentable
//     * @return Collection
//     * @throws Throwable
//     */
    /**
     * @throws \ImagickException
     * @throws Throwable
     */
    public function store(DataCollection $files, Documentable $documentable, ?string $collection = null, ?string $private = null): Collection
    {
        $documents = collect();

           foreach ($files as $fileData) {
               $file = $fileData->file; // Illuminate\Http\UploadedFile

               $name = $file->getClientOriginalName();

               $width = null;
               $height = null;

               $extension = strtolower($file->getClientOriginalExtension());

               $relativeDir = $documentable->storagePath(); // chemin relatif au disk
               $disk = $private ? 'local' : 'public';
               if (!Storage::disk($disk)->directoryExists($relativeDir)) {
                   Storage::disk($disk)->makeDirectory($relativeDir);
               }

               if (in_array($extension, ['jpeg', 'jpg', 'png', 'webp', 'heic', 'tiff', 'avif'])) {
                   $manager = ImageManager::withDriver(new Driver());

                   $image = $manager->read($file->getRealPath())
                       ->orient()
                       ->scaleDown(1000, 1000);

                   $fileName = Carbon::now()->timestamp . '_' . Str::random() . '.' . $extension;
                   $relative = "$relativeDir/$fileName";

                   // Encoder l'image selon l'extension puis écrire via Storage
                   $encoded = match ($extension) {
                       'png'  => $image->toPng(),
                       'webp' => $image->toWebp(70),
                       'heic' => $image->toHeic(70),
                       'tiff' => $image->toTiff(70),
                       'avif' => $image->toAvif(70),
                       default => $image->toJpeg(70),
                   };

                   Storage::disk($disk)->put($relative, (string) $encoded);

                   $path = $disk === 'public' ? "public/{$relative}" : "app/{$relative}";

                   $width = $image->width();
                   $height = $image->height();
               } elseif ($extension === 'dng') {

                   // Pour les fichiers .dng, utiliser Imagick directement
                   $imagick = new \Imagick();

                   $imagick->readImage($file->getRealPath());

                   $imagick->resizeImage(1000, 1000, \Imagick::FILTER_LANCZOS, 1, true);

                   $imagick->setImageFormat('jpeg');

                   $fileName = Carbon::now()->timestamp . "_" . Str::random() . ".jpg";
                   $relative = "$relativeDir/$fileName";
                   Storage::disk($disk)->put($relative, $imagick->getImageBlob());

                   $path = $disk === 'public' ? "public/{$relative}" : "app/{$relative}";

                   $width = $imagick->getImageWidth();
                   $height = $imagick->getImageHeight();

                   $imagick->clear();

               } else {
                   $fileName = Carbon::now()->timestamp . '_' . Str::random() . '.' . $extension;
                   $stored = Storage::disk($disk)->putFileAs($relativeDir, $file, $fileName);
                   $path = $disk === 'public' ? $stored : $stored;
               }

               $documentPath = basename($path);

               $document = new Document();
               $document->setAttribute('name', $name);
               $document->setAttribute('file_name', $documentPath);
               $document->setAttribute('width', $width);
               $document->setAttribute('height', $height);
               $document->setAttribute('collection', $collection);
               $document->setAttribute('private', $private);
               $document->setAttribute('disk', $disk);
               $document->documentable()->associate($documentable);
               $document->saveOrFail();

               $documents->add($document);
           }

        return $documents;
    }

    public function delete(Document $document): void
    {
       $document->delete();
    }
}
