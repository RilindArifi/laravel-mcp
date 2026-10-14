<?php

namespace Tests\Unit\Mcp;

use App\Mcp\Support\LocalUserResolver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class LocalUserResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_the_user_passed_as_an_argument(): void
    {
        $user = User::factory()->create(['email' => 'picked@example.com']);

        $this->assertTrue((new LocalUserResolver)->resolve('picked@example.com')->is($user));
    }

    public function test_it_falls_back_to_the_configured_user(): void
    {
        $user = User::factory()->create(['email' => 'configured@example.com']);

        config(['taskflow.mcp.local_user' => 'configured@example.com']);

        $this->assertTrue((new LocalUserResolver)->resolve()->is($user));
    }

    public function test_it_fails_when_no_user_is_configured(): void
    {
        config(['taskflow.mcp.local_user' => null]);

        $this->expectException(InvalidArgumentException::class);

        (new LocalUserResolver)->resolve();
    }

    public function test_it_fails_for_an_unknown_email_instead_of_picking_someone(): void
    {
        User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        (new LocalUserResolver)->resolve('nobody@example.com');
    }
}
