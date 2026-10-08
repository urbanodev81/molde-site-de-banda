<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Lote\ListasEmLote;
use App\Support\Perfis;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Painel/Usuarios/Index', [
            'usuarios' => User::query()->when($request->boolean('arquivados'), fn ($q) => $q->onlyTrashed())
                ->with('roles')->orderBy('name')->get()
                ->map(fn (User $u) => [
                    'uuid' => $u->uuid,
                    'nome' => $u->name,
                    'email' => $u->email,
                    'telefone' => $u->telefone,
                    'ativo' => $u->ativo,
                    'perfis' => $u->roles->map(fn ($r) => Perfis::rotulo($r->name))->all(),
                    'ultimoAcesso' => $u->ultimo_acesso_em?->format('d/m/Y H:i'),
                    'euMesmo' => $u->getKey() === $request->user()?->getKey(),
                ])->all(),
            ...ListasEmLote::abas('usuarios', $request),
            'podeGerenciar' => $request->user()?->can('usuarios.gerenciar') ?? false,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Painel/Usuarios/Formulario', ['perfis' => $this->perfis()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => $this->regrasDoEmail(),
            'telefone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'perfis' => ['required', 'array', 'min:1'],
            'perfis.*' => [Rule::exists('roles', 'name')],
            'ativo' => ['boolean'],
        ]);

        $usuario = User::create([
            ...collect($dados)->except('perfis')->all(),

            'email_verified_at' => now(),
        ]);

        $usuario->syncRoles($dados['perfis']);

        return redirect()->route('painel.usuarios.index')->with('sucesso', 'Conta criada.');
    }

    private function regrasDoEmail(?User $usuario = null): array
    {
        return [
            'required', 'email', 'max:255',
            Rule::unique('users', 'email')->ignore($usuario?->id)->whereNull('deleted_at'),
            function (string $atributo, mixed $valor, Closure $falha) use ($usuario): void {
                $arquivada = User::onlyTrashed()->where('email', $valor)
                    ->when($usuario, fn ($q) => $q->whereKeyNot($usuario->getKey()))
                    ->exists();

                if ($arquivada) {
                    $falha('Já existe uma conta arquivada com este e-mail. Restaure-a na aba de arquivadas em vez de criar outra.');
                }
            },
        ];
    }

    public function edit(User $usuario): Response
    {
        return Inertia::render('Painel/Usuarios/Formulario', [
            'perfis' => $this->perfis(),
            'usuario' => [
                'uuid' => $usuario->uuid,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'telefone' => $usuario->telefone,
                'ativo' => $usuario->ativo,
                'perfis' => $usuario->roles->pluck('name')->all(),
            ],
        ]);
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $dados = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => $this->regrasDoEmail($usuario),
            'telefone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'perfis' => ['required', 'array', 'min:1'],
            'perfis.*' => [Rule::exists('roles', 'name')],
            'ativo' => ['boolean'],
        ]);

        if ($usuario->getKey() === $request->user()?->getKey()) {
            $dados['ativo'] = true;
            $dados['perfis'] = $usuario->roles->pluck('name')->all();
        }

        $usuario->update(collect($dados)->except(['perfis', 'password'])->all());

        if (filled($dados['password'] ?? null)) {
            $usuario->update(['password' => Hash::make($dados['password'])]);
        }

        $usuario->syncRoles($dados['perfis']);

        return redirect()->route('painel.usuarios.index')->with('sucesso', 'Conta atualizada.');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        abort_if($usuario->getKey() === $request->user()?->getKey(), 403,
            'Não é possível arquivar a própria conta por aqui.');

        $usuario->delete();

        return redirect()->route('painel.usuarios.index')->with('sucesso', 'Conta arquivada. Ela está na aba Arquivadas.');
    }

    private function perfis(): array
    {
        return Role::query()->orderBy('name')->get()
            ->map(fn (Role $r) => [
                'valor' => $r->name,
                'rotulo' => Perfis::rotulo($r->name),
                'descricao' => Perfis::descricao($r->name),
            ])->all();
    }
}
