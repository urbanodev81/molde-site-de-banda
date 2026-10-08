# Site de banda — molde

Site público e painel de manutenção para uma banda: agenda, galeria, repertório,
imprensa, materiais e funil de contratação.

- **Site público em Blade**: o conteúdo chega no HTML, para buscador e motor
  generativo lerem sem executar JavaScript.
- **Painel em Vue 3 + Inertia**, com perfis e permissões por rota.
- Laravel 13 · PHP 8.4 · PostgreSQL · Tailwind 3 · Pest.

A banda ("A melhor banda"), as pessoas, as casas, as datas e as imagens deste
molde são inventadas. Troque
em `database/seeders/ConteudoInicial.php`, em `public/sementes/` e nas
configurações do painel.

## Rodar

```bash
cp .env.example .env
composer install && npm install
php artisan key:generate && php artisan migrate
php artisan db:seed --class=PerfisESeguranca
php artisan db:seed --class=ConteudoInicial
npm run build && php artisan serve
```

## Testes

```bash
php vendor/bin/pest --testsuite=Feature
```

## Licença

Proprietária, todos os direitos reservados. Ver `LICENSE`.
