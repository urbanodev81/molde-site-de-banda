<?php

namespace App\Support\Alertas;

use Illuminate\Mail\Message;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;

final class EmailFormatado
{
    private string $assunto = '';

    private ?string $titulo = null;

    private array $blocos = [];

    private ?string $remetente = null;

    private ?array $responderPara = null;

    private ?string $assinatura = null;

    private function __construct(private readonly string $para) {}

    public static function para(string $para): self
    {
        return new self($para);
    }

    public function assunto(string $assunto): self
    {
        $this->assunto = $assunto;

        return $this;
    }

    public function titulo(string $titulo): self
    {
        $this->titulo = $titulo;

        return $this;
    }

    public function paragrafo(string $texto): self
    {
        $this->blocos[] = ['p', $texto];

        return $this;
    }

    public function campo(string $rotulo, mixed $valor): self
    {
        $this->blocos[] = ['c', $rotulo, self::texto($valor)];

        return $this;
    }

    public function campos(array $campos): self
    {
        foreach ($campos as $rotulo => $valor) {
            $this->campo((string) $rotulo, $valor);
        }

        return $this;
    }

    public function textoCorrido(string $texto): self
    {
        foreach (preg_split('/\R/', trim($texto)) ?: [] as $linha) {
            $linha = trim($linha);
            if ($linha === '') {
                continue;
            }

            preg_match('/^([^:]{1,30}):\s+(.+)$/u', $linha, $m) === 1
                ? $this->campo($m[1], $m[2])
                : $this->paragrafo($linha);
        }

        return $this;
    }

    public function botao(string $rotulo, string $url): self
    {
        $this->blocos[] = ['b', $rotulo, $url];

        return $this;
    }

    public function remetente(string $nome): self
    {
        $this->remetente = $nome;

        return $this;
    }

    public function responderPara(string $email, ?string $nome = null): self
    {
        $this->responderPara = [$email, $nome];

        return $this;
    }

    public function assinatura(string $assinatura): self
    {
        $this->assinatura = $assinatura;

        return $this;
    }

    public function enviar(): void
    {
        $html = (string) $this->mensagem()->render();
        $texto = $this->textoPuro();

        Mail::send([], [], function (Message $m) use ($html, $texto) {
            $m->to($this->para)->subject($this->assunto)->html($html)->text($texto);

            if ($this->remetente !== null) {
                $m->from((string) config('mail.from.address'), $this->remetente);
            }
            if ($this->responderPara !== null) {
                $m->replyTo($this->responderPara[0], $this->responderPara[1]);
            }
        });
    }

    public function mensagem(): MailMessage
    {
        $msg = (new MailMessage)->subject($this->assunto);

        if ($this->titulo !== null) {
            $msg->greeting(self::escapar($this->titulo));
        }

        foreach ($this->blocos as $bloco) {
            match ($bloco[0]) {
                'p' => $msg->line(self::escapar($bloco[1])),
                'c' => $msg->line('**'.self::escapar($bloco[1]).':** '.self::escapar($bloco[2])),
                'b' => $msg->action($bloco[1], $bloco[2]),
            };
        }

        return $msg->salutation(self::escapar($this->assinatura ?? (string) config('app.name')));
    }

    private function textoPuro(): string
    {
        $linhas = $this->titulo !== null ? [$this->titulo, ''] : [];

        foreach ($this->blocos as $bloco) {
            $linhas[] = match ($bloco[0]) {
                'p' => $bloco[1]."\n",
                'c' => $bloco[1].': '.$bloco[2],
                'b' => "\n".$bloco[1].': '.$bloco[2]."\n",
            };
        }

        $linhas[] = '';
        $linhas[] = $this->assinatura ?? (string) config('app.name');

        return implode("\n", $linhas);
    }

    private static function texto(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }

        return is_scalar($valor)
            ? (string) $valor
            : (string) json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private static function escapar(string $texto): string
    {
        return preg_replace('/([\\\\`*_{}\[\]()#+\-.!|~>])/', '\\\\$1', $texto) ?? $texto;
    }
}
