<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Citizen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CitizenProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var Citizen $citizen */
        $citizen = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $citizen->id,
                'dni' => $citizen->dni,
                'first_name' => $citizen->first_name,
                'last_name' => $citizen->last_name,
                'email' => $citizen->email,
            ],
        ]);
    }
}
