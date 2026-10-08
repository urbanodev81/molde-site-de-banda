<?php

use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\ChallengeOptions;
use AltchaOrg\Altcha\Hasher\Algorithm;
use App\Support\Captcha\AltchaVerifier;
use Illuminate\Support\Facades\Cache;

const CHAVE_HMAC = 'chave-de-teste-nao-usar-em-producao';

function payloadResolvido(?string $chave = null, int $max = 500, ?int $expiraEm = 600): string
{
    $altcha = new Altcha($chave ?? CHAVE_HMAC);

    $desafio = $altcha->createChallenge(new ChallengeOptions(
        algorithm: Algorithm::SHA256,
        maxNumber: $max,
        expires: $expiraEm === null ? null : new DateTimeImmutable("{$expiraEm} seconds"),
    ));

    $solucao = $altcha->solveChallenge(
        $desafio->challenge, $desafio->salt, Algorithm::SHA256, $desafio->maxNumber,
    );

    return base64_encode(json_encode([
        'algorithm' => $desafio->algorithm,
        'challenge' => $desafio->challenge,
        'number' => $solucao->number,
        'salt' => $desafio->salt,
        'signature' => $desafio->signature,
        'took' => $solucao->took,
    ]));
}

beforeEach(function () {
    Cache::flush();
    $this->verifier = new AltchaVerifier(CHAVE_HMAC, custo: 500);
});

it('aceita uma solução legítima', function () {
    expect($this->verifier->verify(payloadResolvido()))->toBeTrue();
});

it('RECUSA a mesma solução apresentada duas vezes (replay)', function () {
    $token = payloadResolvido();

    expect($this->verifier->verify($token))->toBeTrue()
        ->and($this->verifier->verify($token))->toBeFalse();
});

it('recusa desafio assinado com outra chave', function () {
    expect($this->verifier->verify(payloadResolvido('chave-do-atacante')))->toBeFalse();
});

it('recusa token vazio, lixo e base64 que não decodifica', function () {
    expect($this->verifier->verify(''))->toBeFalse()
        ->and($this->verifier->verify('nao-e-base64-de-nada'))->toBeFalse()
        ->and($this->verifier->verify(base64_encode('{"isto":"nao e um payload"}')))->toBeFalse();
});

it('recusa desafio vencido', function () {
    expect($this->verifier->verify(payloadResolvido(expiraEm: -1)))->toBeFalse();
});

it('recusa solução com número trocado', function () {
    $payload = json_decode(base64_decode(payloadResolvido()), true);
    $payload['number'] = $payload['number'] + 1;

    expect($this->verifier->verify(base64_encode(json_encode($payload))))->toBeFalse();
});

it('emite desafio no formato v1 plano, que é o que o widget resolve', function () {
    $desafio = $this->verifier->criarDesafio();

    expect($desafio)->toHaveKeys(['algorithm', 'challenge', 'maxnumber', 'salt', 'signature'])
        ->and($desafio['algorithm'])->toBe('SHA-256')
        ->and($desafio['signature'])->not->toBeEmpty()
        ->and($desafio['salt'])->toContain('expires=');
});
