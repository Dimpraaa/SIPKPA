<?php

namespace Tests\Feature;

use App\Models\AngkatanPkpa;
use App\Models\ModelMl;
use App\Models\PesertaPkpa;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_and_login_logout_regenerate_authentication_state(): void
    {
        $user = User::factory()->create();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk()->assertSee('Masuk ke akun');

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password123'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('login'))->assertRedirect(route('dashboard'));
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_invalid_credentials_are_rate_limited_without_authenticating(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'salah'])
                ->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak sesuai.']);
        }
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password123'])
            ->assertSessionHasErrors(['email' => 'Terlalu banyak percobaan masuk. Coba lagi dalam 60 detik.']);
        $this->assertGuest();
    }

    public function test_management_can_read_but_cannot_execute_any_admin_operation(): void
    {
        $user = User::factory()->create(['role' => 'manajemen']);
        $angkatan = AngkatanPkpa::factory()->create();
        $peserta = PesertaPkpa::factory()->for($angkatan, 'angkatan')->create();
        $model = ModelMl::factory()->create();
        Http::preventStrayRequests();
        Http::fake();
        $this->actingAs($user);

        foreach (['dashboard', 'angkatan.index', 'peserta.index', 'model.index', 'prediksi.index'] as $route) {
            $this->get(route($route))->assertOk()->assertDontSee('action="'.route('model.store').'"', false)->assertDontSee('Tambah peserta');
        }
        $this->get(route('peserta.show', $peserta))->assertOk()->assertDontSee('Jalankan prediksi');
        foreach (['angkatan.create', 'peserta.create', 'penilaian.import', 'penilaian.template'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
        foreach ([
            ['POST', route('angkatan.store')], ['PUT', route('angkatan.update', $angkatan)], ['DELETE', route('angkatan.destroy', $angkatan)],
            ['POST', route('peserta.store')], ['PUT', route('peserta.update', $peserta)], ['DELETE', route('peserta.destroy', $peserta)],
            ['POST', route('penilaian.import.store')], ['POST', route('model.store')], ['PATCH', route('model.activate', $model)],
            ['POST', route('prediksi.individual', $peserta)], ['POST', route('prediksi.angkatan', $angkatan)],
        ] as [$method, $uri]) {
            $this->call($method, $uri)->assertForbidden();
        }
        Http::assertNothingSent();
        $this->assertDatabaseCount('angkatan_pkpa', 1);
        $this->assertDatabaseCount('peserta_pkpa', 1);
        $this->assertDatabaseCount('hasil_prediksi', 0);
    }
}
