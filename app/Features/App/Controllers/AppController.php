<?php

namespace App\Features\App\Controllers;




use App\Support\Abstract\Controller;
use Illuminate\Http\JsonResponse;

class AppController extends Controller {



    public function __construct() {

    }



    public function availability(): JsonResponse {

        return response()->json([
            'available' => true
        ]);

    }

    public function initialData(): JsonResponse
    {

        return response()->json([
            'success' => true,
        ]);

    }

}
