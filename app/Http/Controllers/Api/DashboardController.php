<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboardService): JsonResponse
    {
        abort_if(! $request->user(), 401, 'Unauthenticated.');

        return response()->json([
            'data' => $dashboardService->buildFor($request->user()),
        ]);
    }
}