<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth\Tests\Feature;

use Carbon\CarbonImmutable;
use ForgeStack\PasswordlessAuth\Actions\ConsumeLoginLink;
use ForgeStack\PasswordlessAuth\Actions\RequestLoginLink;
use ForgeStack\PasswordlessAuth\Contracts\AccessPolicy;
use ForgeStack\PasswordlessAuth\Contracts\LoginLinkSender;
use ForgeStack\PasswordlessAuth\Contracts\SubjectDirectory;
use ForgeStack\PasswordlessAuth\Enums\LoginLinkRejection;
use ForgeStack\PasswordlessAuth\Events\LoginLinkConsumed;
use ForgeStack\PasswordlessAuth\Events\LoginLinkRefused;
use ForgeStack\PasswordlessAuth\Exceptions\LoginLinkRejected;
use ForgeStack\PasswordlessAuth\Models\LoginLink;
use ForgeStack\PasswordlessAuth\Tests\Support\InMemorySubjects;
use ForgeStack\PasswordlessAuth\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class ConsumeLoginLinkTest extends TestCase
{
    use RefreshDatabase;

    private InMemorySubjects $subjects;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subjects = new InMemorySubjects();
        $this->subjects->subjects['ana@example.mx'] = 'subject-1';
        $this->subjects->allowed['subject-1'] = true;

        $this->app->instance(SubjectDirectory::class, $this->subjects);
        $this->app->instance(AccessPolicy::class, $this->subjects);
        $this->app->instance(LoginLinkSender::class, $this->subjects);
    }

    private function issueToken(): string
    {
        $this->app->make(RequestLoginLink::class)->handle('ana@example.mx');

        return $this->subjects->sent[0]['token'];
    }

    public function test_a_link_signs_in_once_and_returns_the_subject(): void
    {
        Event::fake([LoginLinkConsumed::class]);
        $token = $this->issueToken();

        $subjectId = $this->app->make(ConsumeLoginLink::class)->handle($token);

        $this->assertSame('subject-1', $subjectId);
        Event::assertDispatched(LoginLinkConsumed::class);
    }

    public function test_a_used_link_does_not_sign_in_again(): void
    {
        $token = $this->issueToken();
        $consume = $this->app->make(ConsumeLoginLink::class);
        $consume->handle($token);

        $this->expectException(LoginLinkRejected::class);

        try {
            $consume->handle($token);
        } catch (LoginLinkRejected $rejected) {
            $this->assertSame(LoginLinkRejection::Used, $rejected->reason);

            throw $rejected;
        }
    }

    public function test_an_expired_link_does_not_sign_in(): void
    {
        $token = $this->issueToken();
        LoginLink::query()->update(['expires_at' => CarbonImmutable::now()->subMinute()]);

        try {
            $this->app->make(ConsumeLoginLink::class)->handle($token);
            $this->fail('Se esperaba LoginLinkRejected.');
        } catch (LoginLinkRejected $rejected) {
            $this->assertSame(LoginLinkRejection::Expired, $rejected->reason);
        }
    }

    public function test_an_unknown_token_is_invalid(): void
    {
        try {
            $this->app->make(ConsumeLoginLink::class)->handle('token-que-no-existe');
            $this->fail('Se esperaba LoginLinkRejected.');
        } catch (LoginLinkRejected $rejected) {
            $this->assertSame(LoginLinkRejection::Invalid, $rejected->reason);
        }
    }

    public function test_a_link_whose_subject_lost_access_is_refused_without_marking_it_used(): void
    {
        Event::fake([LoginLinkRefused::class]);
        $token = $this->issueToken();
        unset($this->subjects->allowed['subject-1']);

        try {
            $this->app->make(ConsumeLoginLink::class)->handle($token);
            $this->fail('Se esperaba LoginLinkRejected.');
        } catch (LoginLinkRejected $rejected) {
            $this->assertSame(LoginLinkRejection::NoAccess, $rejected->reason);
        }

        $this->assertNull(LoginLink::query()->firstOrFail()->used_at);
        Event::assertDispatched(LoginLinkRefused::class);
    }

    public function test_previewing_a_link_does_not_spend_it(): void
    {
        $token = $this->issueToken();
        $consume = $this->app->make(ConsumeLoginLink::class);

        $this->assertNull($consume->rejectionFor($token));
        $this->assertNull($consume->rejectionFor($token));

        $this->assertNull(LoginLink::query()->firstOrFail()->used_at);
        $this->assertSame('subject-1', $consume->handle($token));
    }
}
