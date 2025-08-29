<?php

use App\Models\Document;
use App\Support\Repositories\User\UserRepositoryEloquent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Support\Repositories\Document\DocumentRepositoryEloquent;
use Spatie\LaravelData\DataCollection;
use App\Support\Data\ImageData;
use App\Models\Test\TestDocumentable;

uses(TestCase::class, RefreshDatabase::class);


