<?php

declare(strict_types=1);

namespace App\Services\Central;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CentralPresenceClient
{
    public function isConfigured(): bool
    {
        if (app()->environment('testing')) {
            return false;
        }

        return filled(config('services.central.base_url'))
            && filled(config('services.central.project_token'));
    }

    public function ping(string $identificador, string $nome, ?string $pagina = null): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        try {
            $resposta = $this->requisicao()->post($this->url('/api/presence/ping'), array_filter([
                'user_identifier' => $identificador,
                'user_name' => $nome,
                'page_url' => $pagina,
            ]));

            if ($resposta->failed()) {
                Log::warning('Central recusou o ping de presença.', [
                    'status' => $resposta->status(),
                    'user_identifier' => $identificador,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Falha ao empurrar presença para o Central.', ['erro' => $e->getMessage()]);
        }
    }

    public function reportarErro(array $dados): bool
    {
        if (! $this->isConfigured()) {
            Log::info('Relato de erro não enviado: integração com o Central desligada.', [
                'titulo' => $dados['title'] ?? null,
            ]);

            return false;
        }

        try {
            $resposta = $this->requisicao(10)->post($this->url('/api/reports'), $dados);

            if ($resposta->failed()) {
                Log::warning('Central recusou o relato de erro.', [
                    'status' => $resposta->status(),
                    'corpo' => $resposta->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Falha ao enviar relato de erro para o Central.', ['erro' => $e->getMessage()]);

            return false;
        }
    }

    public function heartbeat(string $slug, string $estado = 'success'): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        try {
            $this->requisicao()->post($this->url("/api/heartbeat/{$slug}"), ['state' => $estado]);
        } catch (\Throwable $e) {
            Log::warning('Falha ao enviar heartbeat para o Central.', [
                'slug' => $slug,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    public function enviarFeedbackDeTela(array $dados): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $resposta = $this->requisicao()->post($this->url('/api/screen-feedback'), $dados);

            if ($resposta->status() === 429) {
                throw new \RuntimeException('Central recusou por limite de taxa; nova tentativa depois.');
            }

            if ($resposta->failed()) {
                Log::warning('Central recusou o feedback de tela.', [
                    'status' => $resposta->status(),
                    'corpo' => $resposta->body(),
                ]);

                return false;
            }

            return true;
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('Falha ao enviar feedback de tela.', ['erro' => $e->getMessage()]);

            return false;
        }
    }

    private function requisicao(int $timeout = 5): PendingRequest
    {
        return Http::withToken((string) config('services.central.project_token'))
            ->timeout($timeout)
            ->acceptJson();
    }

    private function url(string $caminho): string
    {
        return rtrim((string) config('services.central.base_url'), '/').$caminho;
    }

    public function entregarComChave(string $path, array $dados, string $chave): Response
    {
        $baseUrl = rtrim((string) config('services.central.base_url'), '/');

        return Http::withToken((string) config('services.central.project_token'))
            ->withHeaders(['Idempotency-Key' => $chave])
            ->timeout(10)
            ->acceptJson()
            ->post($baseUrl.$path, $dados);
    }
}
