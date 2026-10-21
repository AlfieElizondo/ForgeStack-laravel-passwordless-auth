<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth\Tests\Feature;

use ForgeStack\PasswordlessAuth\Actions\RequestLoginLink;
use ForgeStack\PasswordlessAuth\Events\LoginLinkRequested;
use ForgeStack\PasswordlessAuth\Events\LoginLinkRequestedForUnknownEmail;
use ForgeStack\PasswordlessAuth\Models\LoginLink;
use ForgeStack\PasswordlessAuth\Tests\Support\InMemorySubjects;
use ForgeStack\PasswordlessAuth\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class RequestLoginLinkTest extends TestCase
{
    use RefreshDatabase;

    private InMemorySubjects $subjects;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subjects = new InMemorySubjects();
        $this->subjects->subjects['ana@example.mx'] = 'subject-1';
        $this->subjects->allowed['subject-1'] = true;

        $this->app->instance(\ForgeStack\PasswordlessAuth\Contracts\SubjectDirectory::class, $this->subjects);
        $this->app->instance(\ForgeStack\PasswordlessAuth\Contracts\AccessPolicy::class, $this->subjects);
        $this->app->instance(\ForgeStack\PasswordlessAuth\Contracts\LoginLinkSender::class, $this->subjects);
    }

    public function test_a_known_subject_with_access_gets_a_single_use_link_by_email(): void
    {
        Event::fake([LoginLinkRequested::class]);

        $this->app->make(RequestLoginLink::class)->handle('ana@example.mx');

        $this->assertCount(1, $this->subjects->sent);
        $this->assertSame('ana@example.mx', $this->subjects->sent[0]['email']);
        $this->assertDatabaseCount('login_links', 1);
        Event::assertDispatched(LoginLinkRequested::class);
    }

    public function test_the_database_stores_only_the_hash_of_the_token(): void
    {
        $this->app->make(RequestLoginLink::class)->handle('ana@example.mx');

        $token = $this->subjects->sent[0]['token'];
        $link = LoginLink::query()->firstOrFail();

        $this->assertNotSame($token, $link->token_hash);
        $this->assertSame(hash('sha256', $token), $link->token_hash);
    }

    public function test_the_link_expires_after_the_configured_minutes(): void
    {
        $this->app->make(RequestLoginLink::class)->handle('ana@example.mx');

        $link = LoginLink::query()->firstOrFail();

        $this->assertEqualsWithDelta(
            now()->addMinutes(15)->timestamp,
            $link->expires_at->timestamp,
            5,
        );
    }

    public function test_an_unknown_email_sends_nothing_and_stores_nothing(): void
    {
        Event::fake([LoginLinkRequestedForUnknownEmail::class]);

        $this->app->make(RequestLoginLink::class)->handle('nadie@example.mx');

        $this->assertSame([], $this->subjects->sent);
        $this->assertDatabaseCount('login_links', 0);
        Event::assertDispatched(LoginLinkRequestedForUnknownEmail::class);
    }

    public function test_a_subject_whose_access_was_withdrawn_gets_no_link(): void
    {
        unset($this->subjects->allowed['subject-1']);

        $this->app->make(RequestLoginLink::class)->handle('ana@example.mx');

        $this->assertSame([], $this->subjects->sent);
        $this->assertDatabaseCount('login_links', 0);
    }

    public function test_the_email_is_normalised_before_looking_it_up(): void
    {
        $this->app->make(RequestLoginLink::class)->handle('  ANA@example.mx ');

        $this->assertCount(1, $this->subjects->sent);
    }
}
