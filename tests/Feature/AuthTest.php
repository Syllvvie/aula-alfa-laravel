<?php

use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

function criarAdmin(): User
{
    $admin = User::factory()->create();
    Profile::create(['user_id' => $admin->id, 'type' => 'admin']);

    return $admin;
}

// ---------- Visitante ----------

it('redireciona visitante de / para o login', function () {
    $this->get('/')->assertRedirect('/login');
});

it('redireciona visitante de /admin/users para o login', function () {
    $this->get('/admin/users')->assertRedirect('/login');
});

it('mostra login e cadastro para visitante', function () {
    $this->get('/login')->assertOk();
    $this->get('/register')->assertOk();
});

// ---------- Cadastro ----------

it('cadastra uma conta comum sem perfil admin', function () {
    $this->post('/store', [
        'name' => 'Teste Comum',
        'email' => 'comum@local.test',
        'password' => 'senha12345',
    ])->assertRedirect('/login');

    $user = User::where('email', 'comum@local.test')->first();

    expect($user)->not->toBeNull();
    expect($user->profile)->toBeNull();
});

it('valida email duplicado e senha curta no cadastro', function () {
    User::factory()->create(['email' => 'repetido@local.test']);

    $this->post('/store', [
        'name' => 'Fulano',
        'email' => 'repetido@local.test',
        'password' => '123',
    ])->assertSessionHasErrors(['email', 'password']);
});

it('não permite virar admin enviando type no cadastro', function () {
    $this->post('/store', [
        'name' => 'Esperto',
        'email' => 'esperto@local.test',
        'password' => 'senha12345',
        'type' => 'admin',
    ]);

    $user = User::where('email', 'esperto@local.test')->first();

    expect($user->profile?->type)->not->toBe('admin');
});

// ---------- Login ----------

it('mostra erro genérico e preserva o email com senha errada', function () {
    User::factory()->create(['email' => 'a@local.test']);

    $this->from('/login')
        ->post('/login', ['email' => 'a@local.test', 'password' => 'errada'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['email' => 'Credenciais inválidas.'])
        ->assertSessionHasInput('email', 'a@local.test')
        ->assertSessionMissing('_old_input.password');
});

it('bloqueia depois de 5 tentativas erradas', function () {
    RateLimiter::clear('b@local.test|127.0.0.1');
    User::factory()->create(['email' => 'b@local.test']);

    foreach (range(1, 5) as $i) {
        $this->post('/login', ['email' => 'b@local.test', 'password' => 'errada']);
    }

    // Mesmo com a senha CERTA ('password' é a senha da factory), continua bloqueado.
    $this->post('/login', ['email' => 'b@local.test', 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('leva usuário comum para / depois do login', function () {
    User::factory()->create(['email' => 'c@local.test']);

    $this->post('/login', ['email' => 'c@local.test', 'password' => 'password'])
        ->assertRedirect(route('home'));

    $this->assertAuthenticated();
});

it('leva admin para o painel depois do login', function () {
    $admin = criarAdmin();

    $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('admin.users.index'));
});

it('leva admin ao destino que ele pediu antes do login', function () {
    $admin = criarAdmin();

    $this->get('/admin/users')->assertRedirect('/login');

    $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('admin.users.index'));
});

// ---------- Autorização ----------

it('responde 403 para usuário comum no painel', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin/users')
        ->assertForbidden();
});

it('permite admin no painel', function () {
    $this->actingAs(criarAdmin())
        ->get('/admin/users')
        ->assertOk();
});

// ---------- Logout ----------

it('encerra a sessão com POST /logout', function () {
    $this->actingAs(User::factory()->create())
        ->post('/logout')
        ->assertRedirect('/login');

    $this->assertGuest();
});

it('não aceita logout por GET', function () {
    $this->actingAs(User::factory()->create())
        ->get('/logout')
        ->assertStatus(405);
});