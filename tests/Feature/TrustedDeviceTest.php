<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth\Tests\Feature;

use Carbon\CarbonImmutable;
use ForgeStack\PasswordlessAuth\Actions\ListTrustedDevices;
use ForgeStack\PasswordlessAuth\Actions\RestoreTrustedSession;
use ForgeStack\PasswordlessAuth\Actions\RevokeTrustedDevice;
use ForgeStack\PasswordlessAuth\Actions\TrustDevice;
use ForgeStack\PasswordlessAuth\Contracts\AccessPolicy;
use ForgeStack\PasswordlessAuth\Models\TrustedDevice;
use ForgeStack\PasswordlessAuth\Tests\Support\InMemorySubjects;
use ForgeStack\PasswordlessAuth\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TrustedDeviceTest extends TestCase
{
    use RefreshDatabase;

    private InMemorySubjects $subjects;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-10-21 10:00:00');

        $this->subjects = new InMemorySubjects();
        $this->subjects->allowed['subject-1'] = true;
        $this->subjects->allowed['subject-2'] = true;

        $this->app->instance(AccessPolicy::class, $this->subjects);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_trusting_a_device_stores_only_the_hash_of_its_token(): void
    {
        $token = $this->app->make(TrustDevice::class)->handle('subject-1', 'Chrome en Mac');

        $device = TrustedDevice::query()->firstOrFail();

        $this->assertNotSame($token, $device->token_hash);
        $this->assertSame(hash('sha256', $token), $device->token_hash);
        $this->assertSame('subject-1', $device->subject_id);
    }

    public function test_a_trusted_device_restores_the_session_and_rotates_its_token(): void
    {
        $token = $this->app->make(TrustDevice::class)->handle('subject-1');

        $restored = $this->app->make(RestoreTrustedSession::class)->handle($token);

        $this->assertSame('subject-1', $restored['subject_id']);
        $this->assertNotNull($restored['token']);
        $this->assertNotSame($token, $restored['token']);
    }

    public function test_the_previous_token_keeps_working_for_sixty_seconds_only(): void
    {
        $token = $this->app->make(TrustDevice::class)->handle('subject-1');
        $restore = $this->app->make(RestoreTrustedSession::class);

        $restore->handle($token);

        CarbonImmutable::setTestNow('2026-10-21 10:00:30');
        $withinGrace = $restore->handle($token);

        $this->assertSame('subject-1', $withinGrace['subject_id']);
        $this->assertNull($withinGrace['token'], 'Dentro de la ventana no se emite un token nuevo.');

        CarbonImmutable::setTestNow('2026-10-21 10:02:00');

        $this->assertNull($restore->handle($token));
    }

    public function test_a_device_unused_for_the_configured_days_cannot_restore(): void
    {
        $token = $this->app->make(TrustDevice::class)->handle('subject-1');

        CarbonImmutable::setTestNow(CarbonImmutable::now()->addDays(61));

        $this->assertNull($this->app->make(RestoreTrustedSession::class)->handle($token));
    }

    public function test_using_the_device_extends_its_life(): void
    {
        $token = $this->app->make(TrustDevice::class)->handle('subject-1');
        $restore = $this->app->make(RestoreTrustedSession::class);

        CarbonImmutable::setTestNow(CarbonImmutable::now()->addDays(50));
        $restored = $restore->handle($token);
        $this->assertNotNull($restored);

        CarbonImmutable::setTestNow(CarbonImmutable::now()->addDays(50));
        $this->assertSame('subject-1', $restore->handle($restored['token'])['subject_id']);
    }

    public function test_a_revoked_device_cannot_restore(): void
    {
        $token = $this->app->make(TrustDevice::class)->handle('subject-1');
        $device = TrustedDevice::query()->firstOrFail();

        $this->assertTrue($this->app->make(RevokeTrustedDevice::class)->handle('subject-1', $device->id));

        $this->assertNull($this->app->make(RestoreTrustedSession::class)->handle($token));
    }

    public function test_a_person_whose_access_was_withdrawn_cannot_restore(): void
    {
        $token = $this->app->make(TrustDevice::class)->handle('subject-1');
        unset($this->subjects->allowed['subject-1']);

        $this->assertNull($this->app->make(RestoreTrustedSession::class)->handle($token));
    }

    public function test_a_person_cannot_revoke_another_persons_device(): void
    {
        $this->app->make(TrustDevice::class)->handle('subject-1');
        $device = TrustedDevice::query()->firstOrFail();

        $this->assertFalse($this->app->make(RevokeTrustedDevice::class)->handle('subject-2', $device->id));
        $this->assertNull($device->fresh()->revoked_at);
    }

    public function test_a_person_sees_only_their_own_devices(): void
    {
        $trust = $this->app->make(TrustDevice::class);
        $trust->handle('subject-1', 'Laptop');
        $trust->handle('subject-2', 'Celular');

        $devices = $this->app->make(ListTrustedDevices::class)->handle('subject-1');

        $this->assertCount(1, $devices);
        $this->assertSame('Laptop', $devices->first()->label);
    }
}
