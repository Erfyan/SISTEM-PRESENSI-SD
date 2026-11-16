<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_page(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Auth/Login'));
    }

    public function test_guest_cannot_access_protected_routes(): void
    {
        $this->get('/absensi')->assertRedirect('/login');
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/portal-ortu')->assertRedirect('/login');
    }

    public function test_user_cannot_login_with_incorrect_password(): void
    {
        $user = User::factory()->create([
            'username' => 'guru_test',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'username' => 'guru_test',
            'password' => 'wrong_password',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'username' => 'guru_nonaktif',
            'password' => Hash::make('password123'),
            'aktif' => false,
        ]);

        $response = $this->post('/login', [
            'username' => 'guru_nonaktif',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_admin_redirected_to_admin_dashboard_on_login(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin_sd',
            'password' => Hash::make('password123'),
            'peran' => 'admin',
            'aktif' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'admin_sd',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_guru_redirected_to_absensi_on_login(): void
    {
        $guru = User::factory()->create([
            'username' => 'guru_sd',
            'password' => Hash::make('password123'),
            'peran' => 'guru',
            'aktif' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'guru_sd',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('absensi.index'));
        $this->assertAuthenticatedAs($guru);
    }

    public function test_ortu_redirected_to_portal_ortu_on_login(): void
    {
        $ortu = User::factory()->create([
            'username' => 'ortu_sd',
            'password' => Hash::make('password123'),
            'peran' => 'ortu',
            'aktif' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'ortu_sd',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('portal-ortu.index'));
        $this->assertAuthenticatedAs($ortu);
    }

    public function test_user_with_wajib_ganti_password_is_redirected_to_change_password(): void
    {
        $ortu = User::factory()->create([
            'username' => 'ortu_baru',
            'password' => Hash::make('123456'),
            'peran' => 'ortu',
            'aktif' => true,
            'wajib_ganti_password' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'ortu_baru',
            'password' => '123456',
        ]);

        $response->assertRedirect(route('profil.password'));
    }

    public function test_user_can_update_password_and_clears_wajib_ganti_flag(): void
    {
        $user = User::factory()->create([
            'username' => 'user_pin',
            'password' => Hash::make('123456'),
            'peran' => 'ortu',
            'aktif' => true,
            'wajib_ganti_password' => true,
        ]);

        $response = $this->actingAs($user)->put('/profil/password', [
            'current_password' => '123456',
            'password' => '654321',
            'password_confirmation' => '654321',
        ]);

        $response->assertRedirect(route('portal-ortu.index'));

        $user->refresh();
        $this->assertFalse($user->wajib_ganti_password);
        $this->assertTrue(Hash::check('654321', $user->password));
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create([
            'username' => 'guru_aktif',
            'peran' => 'guru',
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
