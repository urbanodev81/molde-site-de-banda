<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        config(['services.captcha.hmac_key' => 'chave-de-teste-nao-usar-em-producao']);
    })
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Browser');
