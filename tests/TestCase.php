<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;
use Laravel\Passport\ClientRepository;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create the Passport client that personal access tokens are issued from.
     */
    protected function createPersonalAccessClient(): void
    {
        app(ClientRepository::class)->createPersonalAccessGrantClient('Testing', 'users');
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
