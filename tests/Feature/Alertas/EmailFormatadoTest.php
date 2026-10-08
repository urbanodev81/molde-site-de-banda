<?php

use App\Support\Alertas\EmailFormatado;
use Symfony\Component\Mime\Email;

function ultimoEmailEnviado(): Email
{
    $mensagens = app('mailer')->getSymfonyTransport()->messages();

    return $mensagens->last()->getOriginalMessage();
}

beforeEach(fn () => config(['mail.default' => 'array']));

test('sai no layout do sistema, com HTML e texto', function () {
    EmailFormatado::para('operador@example.com')
        ->assunto('[APP] Teste')
        ->titulo('Alerta de segurança')
        ->paragrafo('Uma sessão foi aberta.')
        ->campo('IP', '10.0.0.1')
        ->enviar();

    $email = ultimoEmailEnviado();

    expect($email->getSubject())->toBe('[APP] Teste')
        ->and($email->getHtmlBody())->toMatch('/<strong[^>]*>IP:<\/strong>/')
        ->and($email->getHtmlBody())->toContain('Alerta de segurança')
        ->and($email->getTextBody())->toContain('IP: 10.0.0.1');
});

test('texto variável não vira formatação nem link', function () {
    EmailFormatado::para('operador@example.com')
        ->assunto('x')
        ->paragrafo('Motivo: *negrito* [clique](https://malicioso.example)')
        ->enviar();

    $html = ultimoEmailEnviado()->getHtmlBody();

    expect($html)->not->toContain('href="https://malicioso.example"')
        ->and($html)->not->toContain('<em>negrito</em>')
        ->and($html)->toContain('*negrito*');
});

test('o remetente leva o nome pedido, não o do inquilino', function () {
    config(['mail.from.name' => 'Imóveis Jacob', 'mail.from.address' => 'contato@example.com']);

    EmailFormatado::para('operador@example.com')->assunto('x')->remetente('APP')->enviar();

    $de = ultimoEmailEnviado()->getFrom()[0];

    expect($de->getName())->toBe('APP')
        ->and($de->getAddress())->toBe('contato@example.com');
});
