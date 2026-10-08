<?php

namespace App\Http\Controllers;

use App\Support\Captcha\AltchaVerifier;
use App\Support\Captcha\CaptchaVerifier;
use Illuminate\Http\JsonResponse;

class CaptchaChallengeController extends Controller
{
    public function __invoke(CaptchaVerifier $verifier): JsonResponse
    {
        abort_unless($verifier instanceof AltchaVerifier, 404);

        return response()->json($verifier->criarDesafio())

            ->header('Cache-Control', 'no-store, private');
    }
}
