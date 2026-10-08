<?php

use App\Models\Foto;
use App\Models\User;
use App\Support\Alertas\AlertaDeErro;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function () {
    $this->withoutDefer();
});

function contaDoTeto(string $email): User
{
    return User::create([
        'name' => 'Teto', 'email' => $email, 'password' => 'password',
        'ativo' => true, 'email_verified_at' => now(),
    ]);
}

test('trocar de IP não contorna o bloqueio da conta', function () {
    $user = contaDoTeto('rodizio@example.com');

    foreach (range(1, 5) as $i) {
        $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$i}"])
            ->post('/login', ['email' => $user->email, 'password' => 'errada']);
    }

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
        ->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('conta travada avisa o operador por e-mail — uma vez por e-mail e IP', function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    contaDoTeto('alvo@example.com');
    Mail::shouldReceive('send')->once();

    foreach (range(1, 8) as $i) {
        $this->post('/login', ['email' => 'alvo@example.com', 'password' => 'errada-'.$i]);
    }
});

test('login, esqueci-senha e redefinir aceitam no máximo 5 por minuto, cada um no seu balde', function () {
    Mail::fake();

    foreach (['/login' => ['email' => 'x@example.com', 'password' => 'errada'],
        '/forgot-password' => ['email' => 'x@example.com'],
        '/reset-password' => ['token' => 't', 'email' => 'x@example.com', 'password' => 'a', 'password_confirmation' => 'a']] as $url => $dados) {
        foreach (range(1, 5) as $i) {
            expect($this->post($url, $dados)->status())->not->toBe(429);
        }
        $this->post($url, $dados)->assertStatus(429);
    }
});

test('o 500 avisa o operador, e o mesmo erro repetido não vira rajada', function () {
    Mail::shouldReceive('send')->twice();

    $erro = new RuntimeException('Quebrou');
    AlertaDeErro::avisar($erro);
    AlertaDeErro::avisar($erro);
    AlertaDeErro::avisar(new RuntimeException('Outro'));
});

test('esqueci-senha exige captcha fora de local/testing', function () {
    $this->withoutMiddleware();
    $this->app['env'] = 'staging';

    $this->post('/forgot-password', ['email' => 'x@example.com'])
        ->assertSessionHasErrors('captcha_token');

    $this->app['env'] = 'testing';
});

test('chave de rota que não é UUID dá 404, não 500 (22P02)', function () {
    (new Foto)->resolveRouteBinding('nao-e-uuid');
})->throws(NotFoundHttpException::class);

test('e-mail sem conta e pedido repetido recebem a mesma resposta do envio', function () {
    Notification::fake();
    $neutra = 'Se o e-mail estiver cadastrado, enviamos o link de redefinição.';

    $user = contaDoTeto('esqueci@example.com');

    foreach ([$user->email, 'ninguem@example.com', $user->email] as $email) {
        $this->post('/forgot-password', ['email' => $email])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', $neutra);
    }
});
