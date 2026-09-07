<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Finance\BriCallbackAuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BriCallbackTokenController extends Controller
{
    public function __invoke(Request $request, BriCallbackAuthenticationService $authentication): JsonResponse
    {
        $data = $request->validate(['grantType' => ['required', 'in:client_credentials']]);
        $token = $authentication->issue(
            (string) $request->header('X-CLIENT-KEY'),
            (string) $request->header('X-TIMESTAMP'),
            (string) $request->header('X-SIGNATURE'),
            $data['grantType'],
        );

        return response()->json($token);
    }
}
