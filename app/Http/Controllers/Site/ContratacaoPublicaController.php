<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Enums\OrigemContratacao;
use App\Enums\TipoEvento;
use App\Enums\TipoInteracao;
use App\Http\Controllers\Controller;
use App\Mail\NovoPedidoDeContratacao;
use App\Models\Contratacao;
use App\Models\User;
use App\Rules\Captcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Enum;

class ContratacaoPublicaController extends Controller
{
    private const SEGUNDOS_MINIMOS = 3;

    private const RECADO_DE_SUCESSO = 'Recebemos! A gente responde pelo WhatsApp ou pelo e-mail que você deixou.';

    public function __invoke(Request $request): RedirectResponse
    {
        if ($this->ehRobo($request)) {
            return back()->with('sucesso', self::RECADO_DE_SUCESSO)->withFragment('contratar');
        }

        $semLinks = $this->limparTextoLivre($request, 'mensagem');

        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required_without:telefone', 'nullable', 'email', 'max:255'],
            'telefone' => ['required_without:email', 'nullable', 'string', 'max:20'],
            'tipo_evento' => ['required', new Enum(TipoEvento::class)],
            'data_pretendida' => ['nullable', 'date', 'after_or_equal:today'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'local' => ['nullable', 'string', 'max:255'],
            'mensagem' => ['nullable', 'string', 'max:2000', $semLinks],

            'consentimento' => ['accepted'],

            'captcha_token' => Captcha::rules(),
        ], [
            'email.required_without' => 'Deixe um e-mail ou um telefone — sem um dos dois não temos como responder.',
            'telefone.required_without' => 'Deixe um telefone ou um e-mail — sem um dos dois não temos como responder.',
            'consentimento.accepted' => 'Precisamos do seu ok para guardar seu contato e responder.',
            'data_pretendida.after_or_equal' => 'A data do evento não pode ser no passado.',
        ]);

        $pedido = Contratacao::create([
            ...collect($dados)->except(['consentimento', 'captcha_token'])->all(),
            'origem' => OrigemContratacao::Site,
            'consentimento_em' => now(),
            'consentimento_ip' => $request->ip(),
        ]);

        $pedido->registrarInteracao(
            'Pedido recebido pelo formulário do site.',
            TipoInteracao::Nota->value,
        );

        $this->avisarABanda($pedido);

        return back()
            ->with('sucesso', self::RECADO_DE_SUCESSO)
            ->withFragment('contratar');
    }

    private function ehRobo(Request $request): bool
    {
        if ($request->filled('site')) {
            return true;
        }

        $abertoEm = $request->integer('aberto_em');

        return $abertoEm > 0 && (time() - $abertoEm) < self::SEGUNDOS_MINIMOS;
    }

    private function avisarABanda(Contratacao $pedido): void
    {
        try {
            $destinatarios = User::query()->ativos()
                ->permission('contratacoes.ver')
                ->pluck('email')
                ->filter()
                ->all();

            if ($destinatarios !== []) {
                Mail::to($destinatarios)->send(new NovoPedidoDeContratacao($pedido));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function limparTextoLivre(Request $request, string $campo): \Closure
    {
        $texto = $request->input($campo);

        if (is_string($texto) && $texto !== '') {
            $request->merge([$campo => trim(strip_tags($texto))]);
        }

        return function (string $atributo, mixed $valor, \Closure $falha): void {
            if (is_string($valor) && preg_match_all('~(?:https?://|www\.)\S+~i', $valor) > 2) {
                $falha('A mensagem tem links demais. Deixe no máximo 2 e envie de novo.');
            }
        };
    }
}
