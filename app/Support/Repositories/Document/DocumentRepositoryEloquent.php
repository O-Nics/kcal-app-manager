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
//use Intervention\Image\Facades\Image;
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
//    public function store(array $files, Documentable $documentable): Collection
//    {
//        $documents = collect();
//
//
//        foreach ($files as $file) {
//            $name = $file->getClientOriginalName();
//
//            $width = null;
//            $height = null;
//
//            if(in_array(strtolower($file->getClientOriginalExtension()), ['jpeg', 'jpg', 'png'])) {
////                dd($file->getRealPath());
//                $image = Image::make($file->getRealPath())
//                    ->orientate() // ✅ Corrige l'orientation selon les métadonnées EXIF
//                    ->resize(600, 600, function ($constraint) {
//                        $constraint->aspectRatio();
//                        $constraint->upsize();
//                    });
//                $storagePath = "app/{$documentable->storagePath()}";
//
//                if (!Storage::directoryExists($storagePath)) {
//                    Storage::createDirectory($documentable->storagePath());
//                }
//                $fileName = Carbon::now()->timestamp . "_" . Str::random() . ".".strtolower($file->getClientOriginalExtension());
//                $image->save(storage_path("$storagePath/$fileName"), 70);
//                $path = $image->basePath();
//
//                $width = $image->width();
//                $height = $image->height();
//            }
//            elseif (strtolower($file->getClientOriginalExtension()) === 'dng') {
//                // Pour les fichiers .dng, utiliser Imagick
//                $imagick = new \Imagick();
//
//                try {
//                    $imagick->readImage($file->getRealPath());
//
//                    $imagick->resizeImage(600, 600, \Imagick::FILTER_LANCZOS, 1, true);
//
//                    $imagick->setImageFormat('jpeg');
//                    $imagick->writeImage('output.jpg');
//
//                    $imagick->clear();
//                    $imagick->destroy();
//                } catch (\Exception $e) {
//                    dd('Erreur lors du traitement du fichier DNG : ' . $e->getMessage());
//                }
//            }
//            else {
//                dd('Type de fichier non supporté');
//                $path = $file->store($documentable->storagePath());
//            }
//
//            $parts = explode('/', $path);
//            $documentPath = $parts[count($parts)-1];
//
//            $document = new Document();
//            $document->setAttribute('name', $name);
//            $document->setAttribute('file_name', $documentPath);
//            $document->setAttribute('width', $width);
//            $document->setAttribute('height', $height);
//            $document->documentable()->associate($documentable);
//            $document->saveOrFail();
//
//            $documents->add($document);
//        }
//
//        return $documents;
//    }
//
//
//    public function storeOnlyOne(array $files, Documentable $documentable): Collection
//    {
//        $documents = collect();
//
//        // Supprimer le document existant s'il y en a un
//        $existingDocuments = $documentable->documents()->get(); // Assurez-vous que la relation `documents` existe
//        foreach ($existingDocuments as $existingDocument) {
//            Storage::delete($documentable->storagePath() . '/' . $existingDocument->file_name);
//            $existingDocument->delete();
//        }
//
//        foreach ($files as $file) {
//            $name = $file->getClientOriginalName();
//
//            $width = null;
//            $height = null;
//            $path = null;
//
//            if (in_array(strtolower($file->getClientOriginalExtension()), ['jpeg', 'jpg', 'png'])) {
//                $image = Image::make($file->getRealPath())->resize(600, 600, function ($constraint) {
//                    $constraint->aspectRatio();
//                    $constraint->upsize();
//                });
//                $storagePath = "app/{$documentable->storagePath()}";
//
//                if (!Storage::directoryExists($storagePath)) {
//                    Storage::createDirectory($documentable->storagePath());
//                }
//                $fileName = Carbon::now()->timestamp . "_" . Str::random() . "." . strtolower($file->getClientOriginalExtension());
//                $image->save(storage_path("$storagePath/$fileName"), 70);
//                $path = "$storagePath/$fileName";
//
//                $width = $image->width();
//                $height = $image->height();
//            } elseif (strtolower($file->getClientOriginalExtension()) === 'dng') {
//                // Traitement pour les fichiers .dng
//                $imagick = new \Imagick();
//
//                try {
//                    $imagick->readImage($file->getRealPath());
//                    $imagick->resizeImage(600, 600, \Imagick::FILTER_LANCZOS, 1, true);
//                    $imagick->setImageFormat('jpeg');
//
//                    $storagePath = "app/{$documentable->storagePath()}";
//                    if (!Storage::directoryExists($storagePath)) {
//                        Storage::createDirectory($documentable->storagePath());
//                    }
//
//                    $fileName = Carbon::now()->timestamp . "_" . Str::random() . ".jpg";
//                    $fullPath = storage_path("$storagePath/$fileName");
//                    $imagick->writeImage($fullPath);
//                    $path = "$storagePath/$fileName";
//
//                    $imagick->clear();
//                    $imagick->destroy();
//                } catch (\Exception $e) {
//                    dd('Erreur lors du traitement du fichier DNG : ' . $e->getMessage());
//                }
//            } else {
//                dd('Type de fichier non supporté');
//                $path = $file->store($documentable->storagePath());
//            }
//
//            $parts = explode('/', $path);
//            $documentPath = $parts[count($parts) - 1];
//
//            $document = new Document();
//            $document->setAttribute('name', $name);
//            $document->setAttribute('file_name', $documentPath);
//            $document->setAttribute('width', $width);
//            $document->setAttribute('height', $height);
//            $document->documentable()->associate($documentable);
//            $document->saveOrFail();
//
//            $documents->add($document);
//        }
//
//        return $documents;
//    }
//    public function storeFromAi(string $imageUrl, Documentable $documentable): Collection{
//        $documents = collect();
//        $name = 'recipe_image';
//        $width = null;
//        $height = null;
//        $image = Image::make($imageUrl)->resize(1200, 1200, function ($constraint) {
//            $constraint->aspectRatio();
//            $constraint->upsize();
//        });
//        $storagePath = "app/{$documentable->storagePath()}";
//
//        if (!Storage::directoryExists($storagePath)) {
//            Storage::createDirectory($documentable->storagePath());
//        }
//        $fileName = Carbon::now()->timestamp . "_" . Str::random() . ".jpg";
//        $image->save(storage_path("$storagePath/$fileName"), 70);
//        $path = $image->basePath();
//
//        $width = $image->width();
//        $height = $image->height();
//
//        $parts = explode('/', $path);
//        $documentPath = $parts[count($parts)-1];
//
//        $document = new Document();
//        $document->setAttribute('name', $name);
//        $document->setAttribute('file_name', $documentPath);
//        $document->setAttribute('width', $width);
//        $document->setAttribute('height', $height);
//        $document->documentable()->associate($documentable);
//        $document->saveOrFail();
//
//        $documents->add($document);
//
//
//        return $documents;
//
//    }
//
//    public function delete(Document $document): void
//    {
//        $document->delete();
//    }
//
//    public function downloadImage(string $url): ?UploadedFile
//    {
//        try {
//            // Vérification des en-têtes pour la taille et le type MIME
//            $imageHeaders = get_headers($url, 1);
//
//            if (isset($imageHeaders['Content-Length']) && $imageHeaders['Content-Length'] > 5 * 1024 * 1024) { // 5MB
//                dump('Image trop grande pour être téléchargée.');
//                return null;
//            }
//
//            if (!isset($imageHeaders['Content-Type']) || !str_starts_with($imageHeaders['Content-Type'], 'image/')) {
//                dump('Le fichier n\'est pas une image valide. Type MIME : ' . $imageHeaders['Content-Type'] );
//                return null;
//            }
//
//            // Charger l'image depuis l'URL
//            $image = Image::make($url);
//
//            // Redimensionner l'image si elle est trop grande
//            if ($image->width() > 600 || $image->height() > 600) {
//                $image->resize(600, null, function ($constraint) {
//                    $constraint->aspectRatio();
//                    $constraint->upsize(); // Évite d'agrandir si l'image est plus petite
//                });
//            }
//
//            // Sauvegarder l'image dans un fichier temporaire
//            $tempPath = tempnam(sys_get_temp_dir(), 'image_');
//            $image->encode('jpg', 70)->save($tempPath); // Sauvegarde avec compression
//
//            // Créer un UploadedFile pour le fichier temporaire
//            $uploadedFile = new UploadedFile(
//                $tempPath,
//                basename(parse_url($url, PHP_URL_PATH)), // Récupère uniquement le nom du fichier depuis l'URL
//                $image->mime(),
//                null,
//                true
//            );
//
//            // Libérer la mémoire occupée par l'image
//            $image->destroy();
//
//            return $uploadedFile;
//
//        } catch (\Intervention\Image\Exception\NotReadableException $e) {
//            dump('Image non lisible : ' . $e->getMessage());
//        } catch (\Exception $e) {
//            dump('Erreur inattendue : ' . $e->getMessage());
//        }
//
//        return null;
//    }
//
//    public function rename(Document $document, string $name): Document
//    {
//        // TODO: Implement rename() method.
//    }
}
