<?php

declare(strict_types=1);

use App\Enums\StatusShow;
use App\Enums\TipoShow;
use App\Enums\TipoVideo;
use App\Models\Foto;
use App\Models\Local;
use App\Models\Musica;
use App\Models\Show;
use App\Models\User;
use App\Models\Video;
use App\Support\DuracaoMp4;
use App\Support\EnvioDeVideo;
use App\Support\Perfis;
use App\Support\VideoExterno;
use Database\Seeders\PerfisESeguranca;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(PerfisESeguranca::class);

    $this->local = Local::create(['nome' => 'Bar do Centro', 'slug' => 'bar-do-centro']);

    $this->banda = User::create([
        'name' => 'Banda', 'email' => 'banda@teste.local', 'password' => 'senha-de-teste-123',
        'ativo' => true, 'email_verified_at' => now(),
    ]);
    $this->banda->syncRoles([Perfis::BANDA]);
});

function mp4DeTeste(string $nome): UploadedFile
{
    return new UploadedFile(base_path("tests/fixtures/{$nome}"), $nome, 'video/mp4', null, true);
}

function noiteComMidia(array $atributos = []): Show
{
    return Show::create([
        'local_id' => test()->local->id,
        'comeca_em' => now()->subWeek(),
        'status' => StatusShow::Realizado,
        'tipo' => TipoShow::Publico,
        'publicado' => true,
        ...$atributos,
    ]);
}

it('lê a duração de um mp4 sem ffmpeg', function () {
    expect(DuracaoMp4::segundos(base_path('tests/fixtures/curto-3s.mp4')))->toEqualWithDelta(3.0, 0.3)
        ->and(DuracaoMp4::segundos(base_path('tests/fixtures/longo-100s.mp4')))->toEqualWithDelta(100.0, 1.0);
});

it('⚠️ não inventa duração de arquivo que não é mp4', function () {
    $falso = tempnam(sys_get_temp_dir(), 'mp4');
    file_put_contents($falso, str_repeat('mvhd isto não é vídeo ', 50));

    expect(DuracaoMp4::segundos($falso))->toBeNull();
});

it('aceita o vídeo curto e guarda duração e peso', function () {
    $this->actingAs($this->banda)
        ->post(route('painel.videos.store'), [
            'titulo' => 'Titanium', 'tipo' => 'arquivo', 'mp4' => mp4DeTeste('curto-3s.mp4'), 'publicado' => '1',
        ])
        ->assertSessionHasNoErrors();

    $video = Video::sole();

    expect($video->tipo)->toBe(TipoVideo::Arquivo)
        ->and($video->duracao_segundos)->toBe(3)
        ->and($video->tamanho_bytes)->toBeGreaterThan(0);
    Storage::disk('public')->assertExists($video->arquivo_mp4_path);
});

it('⚠️ recusa o vídeo acima do limite de duração', function () {
    $this->actingAs($this->banda)
        ->post(route('painel.videos.store'), ['titulo' => 'Longo', 'tipo' => 'arquivo', 'mp4' => mp4DeTeste('longo-100s.mp4')])
        ->assertSessionHasErrors('mp4');

    expect(Video::count())->toBe(0);
});

it('⚠️ recusa o vídeo acima do limite de peso', function () {
    $pesado = UploadedFile::fake()->create('pesado.mp4', (Video::LIMITE_MB * 1024) + 1, 'video/mp4');

    $this->actingAs($this->banda)
        ->post(route('painel.videos.store'), ['titulo' => 'Pesado', 'tipo' => 'arquivo', 'mp4' => $pesado])
        ->assertSessionHasErrors('mp4');
});

it('⚠️ recusa o mp4 cuja duração não se consegue ler — trava que deixa passar não é trava', function () {
    $this->actingAs($this->banda)
        ->post(route('painel.videos.store'), [
            'titulo' => 'Corrompido', 'tipo' => 'arquivo',
            'mp4' => UploadedFile::fake()->create('corrompido.mp4', 50, 'video/mp4'),
        ])
        ->assertSessionHasErrors('mp4');
});

it('reconhece Vimeo, Instagram e TikTok e devolve o endereço de embed', function (string $link, string $embed) {
    expect(VideoExterno::reconhecer($link)['embed'] ?? null)->toBe($embed);
})->with([
    'vimeo' => ['https://vimeo.com/123456789', 'https://player.vimeo.com/video/123456789?dnt=1'],
    'instagram reel' => ['https://www.instagram.com/reel/C9xYz_AbCd/?igsh=abc', 'https://www.instagram.com/reel/C9xYz_AbCd/embed'],
    'tiktok' => ['https://www.tiktok.com/@banda/video/7412345678901234567', 'https://www.tiktok.com/embed/v2/7412345678901234567'],
]);

it('o link do YouTube colado vira o tipo YouTube, com o id', function () {
    expect(EnvioDeVideo::doLink('https://youtu.be/dQw4w9WgXcQ?si=rastreio'))
        ->toBe(['tipo' => 'youtube', 'youtube_id' => 'dQw4w9WgXcQ', 'link_url' => null]);
});

it('⚠️ recusa link de site que o CSP não libera — abriria um quadro em branco', function () {
    $this->actingAs($this->banda)
        ->post(route('painel.videos.store'), ['titulo' => 'Perfil', 'tipo' => 'link', 'link' => 'https://exemplo.com/video.mp4'])
        ->assertSessionHasErrors('link');
});

it('todo site reconhecido está liberado no frame-src', function () {
    $csp = $this->get('/')->headers->get('Content-Security-Policy');

    expect($csp)->toContain('https://www.youtube-nocookie.com')
        ->and($csp)->toContain('https://player.vimeo.com')
        ->and($csp)->toContain('https://www.instagram.com')
        ->and($csp)->toContain('https://www.tiktok.com')
        ->and($csp)->toContain("media-src 'self' blob:");
});

it('a música ligada a um vídeo enviado toca o arquivo no modal do repertório', function () {
    $video = Video::create(['titulo' => 'Titanium', 'tipo' => TipoVideo::Arquivo, 'arquivo_mp4_path' => 'videos/titanium.mp4', 'publicado' => true]);
    Musica::create(['titulo' => 'Titanium', 'artista' => 'Sia', 'video_id' => $video->id, 'publicada' => true]);

    $this->get('/repertorio')
        ->assertOk()
        ->assertSee('data-mp4="'.Storage::disk('public')->url('videos/titanium.mp4').'"', false)
        ->assertSee('Ver a versão');
});

it('⚠️ vídeo despublicado na biblioteca some do repertório também', function () {
    $video = Video::create(['titulo' => 'Titanium', 'tipo' => TipoVideo::Arquivo, 'arquivo_mp4_path' => 'videos/titanium.mp4', 'publicado' => false]);
    Musica::create(['titulo' => 'Titanium', 'artista' => 'Sia', 'video_id' => $video->id, 'publicada' => true]);

    $this->get('/repertorio')->assertOk()->assertDontSee('Ver a versão');
});

it('enviar o vídeo no cadastro da música põe o mesmo vídeo na página do show', function () {
    $noite = noiteComMidia();

    $this->actingAs($this->banda)
        ->post(route('painel.musicas.store'), [
            'titulo' => 'Titanium', 'artista' => 'Sia',
            'video_modo' => 'arquivo', 'video_arquivo' => mp4DeTeste('curto-3s.mp4'), 'video_show_id' => $noite->id,
        ])
        ->assertSessionHasNoErrors();

    $musica = Musica::sole();

    expect($musica->video->show_id)->toBe($noite->id);

    $this->get(route('site.show', $noite))
        ->assertOk()
        ->assertSee('Titanium — Sia');
});

it('⚠️ o mesmo link colado em dois lugares não vira dois vídeos', function () {
    $this->actingAs($this->banda);

    foreach (['Titanium', 'Titanium (acústico)'] as $titulo) {
        $this->post(route('painel.musicas.store'), [
            'titulo' => $titulo, 'video_modo' => 'link', 'video_link' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ])->assertSessionHasNoErrors();
    }

    expect(Video::count())->toBe(1)
        ->and(Musica::pluck('video_id')->unique())->toHaveCount(1);
});

it('⚠️ corrigir o tom não apaga o YouTube de uma música antiga', function () {
    $musica = Musica::create(['titulo' => 'Highway to Hell', 'youtube_id' => 'dQw4w9WgXcQ', 'publicada' => true]);

    $this->actingAs($this->banda)
        ->put(route('painel.musicas.update', $musica), ['titulo' => 'Highway to Hell', 'tom' => 'A', 'video_modo' => 'manter'])
        ->assertSessionHasNoErrors();

    expect($musica->fresh()->youtube_id)->toBe('dQw4w9WgXcQ');
});

it('o vídeo enviado na página do show nasce ligado à noite', function () {
    $noite = noiteComMidia();

    $this->actingAs($this->banda)
        ->post(route('painel.shows.videos.store', $noite), [
            'titulo' => 'Man! I Feel Like a Woman', 'modo' => 'arquivo', 'arquivo' => mp4DeTeste('curto-3s.mp4'),
        ])
        ->assertSessionHasNoErrors();

    expect(Video::sole()->show_id)->toBe($noite->id)
        ->and(Video::sole()->local_id)->toBe($this->local->id);
});

it('⚠️ "tirar da noite" desliga e não apaga — o vídeo pode ser a versão de uma música', function () {
    $noite = noiteComMidia();
    $video = Video::create(['titulo' => 'Titanium', 'tipo' => TipoVideo::Youtube, 'youtube_id' => 'dQw4w9WgXcQ', 'show_id' => $noite->id]);
    $foto = Foto::create(['show_id' => $noite->id, 'arquivo_path' => 'fotos/a.jpg', 'publicada' => true]);

    $this->actingAs($this->banda);
    $this->delete(route('painel.shows.videos.desligar', [$noite, $video]))->assertRedirect();
    $this->delete(route('painel.shows.fotos.desligar', [$noite, $foto]))->assertRedirect();

    expect($video->fresh())->not->toBeNull()->and($video->fresh()->show_id)->toBeNull()
        ->and($foto->fresh())->not->toBeNull()->and($foto->fresh()->show_id)->toBeNull();
});

it('⚠️ não desliga vídeo de OUTRA noite pela URL desta', function () {
    $esta = noiteComMidia();
    $outra = noiteComMidia(['comeca_em' => now()->subMonth()]);
    $video = Video::create(['titulo' => 'X', 'tipo' => TipoVideo::Youtube, 'youtube_id' => 'dQw4w9WgXcQ', 'show_id' => $outra->id]);

    $this->actingAs($this->banda)
        ->delete(route('painel.shows.videos.desligar', [$esta, $video]))
        ->assertNotFound();

    expect($video->fresh()->show_id)->toBe($outra->id);
});

it('⚠️ quem só gerencia a agenda não sobe vídeo pela página do show', function () {
    $producao = User::create([
        'name' => 'Produção', 'email' => 'producao@teste.local', 'password' => 'senha-de-teste-123',
        'ativo' => true, 'email_verified_at' => now(),
    ]);
    $producao->syncRoles([Perfis::PRODUCAO]);

    $this->actingAs($producao)
        ->post(route('painel.shows.videos.store', noiteComMidia()), ['titulo' => 'X', 'modo' => 'link', 'link' => 'https://youtu.be/dQw4w9WgXcQ'])
        ->assertForbidden();
});

it('as telas do painel com a biblioteca abrem com o que ela precisa', function () {
    $noite = noiteComMidia();
    Video::create(['titulo' => 'Sem show', 'tipo' => TipoVideo::Youtube, 'youtube_id' => 'dQw4w9WgXcQ']);

    $this->actingAs($this->banda)
        ->get(route('painel.shows.show', $noite))
        ->assertOk()
        ->assertInertia(fn ($pagina) => $pagina
            ->component('Painel/Shows/Detalhe')
            ->where('midia.showId', $noite->id)
            ->where('midia.limites.mb', Video::LIMITE_MB)
            ->has('midia.biblioteca', 1)
            ->where('midia.podeVideos', true));

    $this->actingAs($this->banda)
        ->get(route('painel.musicas.index'))
        ->assertOk()
        ->assertInertia(fn ($pagina) => $pagina
            ->component('Painel/Musicas/Index')
            ->has('biblioteca', 1)
            ->where('limites.segundos', Video::LIMITE_SEGUNDOS));
});

it('⚠️ ligar da biblioteca não rouba o vídeo de outra noite', function () {
    $esta = noiteComMidia();
    $outra = noiteComMidia(['comeca_em' => now()->subMonth()]);
    $video = Video::create(['titulo' => 'X', 'tipo' => TipoVideo::Youtube, 'youtube_id' => 'dQw4w9WgXcQ', 'show_id' => $outra->id]);

    $this->actingAs($this->banda)
        ->put(route('painel.shows.videos.vincular', $esta), ['video_id' => $video->id])
        ->assertSessionHasErrors('video_id');

    expect($video->fresh()->show_id)->toBe($outra->id);
});

it('a banda grava título e descrição da foto pelo painel', function () {
    $foto = Foto::create(['arquivo_path' => 'fotos/x.webp', 'publicada' => true]);

    $this->actingAs($this->banda)
        ->put(route('painel.fotos.update', $foto), [
            'legenda' => 'As três no palco',
            'descricao' => 'Primeira noite no Bar do Centro.',
            'publicada' => true,
            'destaque' => false,
        ])
        ->assertSessionHasNoErrors();

    expect($foto->fresh())
        ->legenda->toBe('As três no palco')
        ->descricao->toBe('Primeira noite no Bar do Centro.');
});
