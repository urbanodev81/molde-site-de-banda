<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\Central\EnviarFeedbackDeTelaJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeedbackDeTelaController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $dados = $request->validate([

            'screen' => ['required', 'string', 'max:255'],
            'screen_label' => ['nullable', 'string', 'max:255'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        EnviarFeedbackDeTelaJob::dispatch([
            ...$dados,

            'user_identifier' => $request->user()?->uuid,
            'app_version' => config('app.version'),
            'metadata' => ['origem' => 'web'],
        ]);

        return back()->with('sucesso', 'Obrigado pela avaliação.');
    }
}
