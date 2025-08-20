<?php

namespace App\Features\App\Controllers;




use App\Support\Abstract\Controller;
use Illuminate\Http\JsonResponse;

class AppController extends Controller {



    public function __construct() {

    }



    public function checkAvailability(): JsonResponse {

        return response()->json([
            'available' => true
        ]);

    }

    public function loadInitialData(): JsonResponse
    {

        return response()->json([
            'success' => true,
        ]);

    }

}
