<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\License;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GlosaLicenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_always_has_glosa_access(): void
    {
        $superAdmin = User::factory()->create(['role' => 'SuperAdmin']);
        $this->assertTrue($superAdmin->hasGlosaAccess());
    }

    public function test_admin_without_glosa_access_returns_false(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        
        License::create([
            'license_key'      => License::generateKey(),
            'admin_id'         => $admin->id,
            'duration_type'    => '1month',
            'starts_at'        => now(),
            'expires_at'       => now()->addDays(30),
            'status'           => 'active',
            'has_glosa_access' => false,
            'created_by'       => $admin->id,
        ]);

        $this->assertFalse($admin->hasGlosaAccess());
    }

    public function test_admin_with_glosa_access_returns_true(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        License::create([
            'license_key'      => License::generateKey(),
            'admin_id'         => $admin->id,
            'duration_type'    => '1month',
            'starts_at'        => now(),
            'expires_at'       => now()->addDays(30),
            'status'           => 'active',
            'has_glosa_access' => true,
            'created_by'       => $admin->id,
        ]);

        $this->assertTrue($admin->hasGlosaAccess());
    }

    public function test_regular_user_inherits_admin_glosa_access(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        License::create([
            'license_key'      => License::generateKey(),
            'admin_id'         => $admin->id,
            'duration_type'    => '1month',
            'starts_at'        => now(),
            'expires_at'       => now()->addDays(30),
            'status'           => 'active',
            'has_glosa_access' => true,
            'created_by'       => $admin->id,
        ]);

        $user = User::factory()->create([
            'role'       => 'User',
            'created_by' => $admin->id,
        ]);

        $this->assertTrue($user->hasGlosaAccess());
    }
}
