<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
            'contentEncoding' => ['nullable', 'string', 'max:100'],
        ]);

        $request->user()->updatePushSubscription(
            $dados['endpoint'],
            $dados['keys']['p256dh'],
            $dados['keys']['auth'],
            $dados['contentEncoding'] ?? 'aesgcm',
        );

        return response()->json(['status' => 'inscrito']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
        ]);

        $request->user()->deletePushSubscription($dados['endpoint']);

        return response()->json(['status' => 'removido']);
    }
}
