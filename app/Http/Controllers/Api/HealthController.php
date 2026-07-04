<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Health\ReadinessChecker;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
        ]);
    }

    public function readiness(ReadinessChecker $readinessChecker): JsonResponse
    {
        $result = $readinessChecker->run();

        return response()->json(
            $result,
            $result['status'] === 'ready' ? 200 : 503,
        );
    }
}
