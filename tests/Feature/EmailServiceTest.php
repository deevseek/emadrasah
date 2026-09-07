<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OutgoingEmailStatus;
use App\Jobs\SendOutgoingEmail;
use App\Mail\ManualServiceEmail;
use App\Models\OutgoingEmail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EmailServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_officer_role_has_only_the_access_needed_for_email_service(): void
    {
        $role = Role::findByName('petugas-email');

        $this->assertSame('Petugas Email', $role->display_name);
        $this->assertTrue($role->is_system);
        $this->assertEqualsCanonicalizing(
            ['dashboard.view', 'email-service.view', 'email-service.send'],
            $role->permissions->pluck('name')->all(),
        );

        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->syncRoles([$role]);

        $this->actingAs($user)->get(route('email-service.index'))->assertOk();
        $this->actingAs($user)->get(route('email-service.create'))->assertOk();
        $this->actingAs($user)->get(route('users.index'))->assertForbidden();
    }

    public function test_user_without_permission_cannot_open_module(): void
    {
        $this->actingAs($this->user())->get(route('email-service.index'))->assertForbidden();
        $this->actingAs($this->user())->get(route('email-service.create'))->assertForbidden();
    }

    public function test_authorized_user_can_open_compose_page(): void
    {
        config(['mail.from.address' => 'madrasah@example.test', 'mail.from.name' => 'MI Pengujian']);

        $this->actingAs($this->user(['email-service.send']))
            ->get(route('email-service.create'))
            ->assertOk()
            ->assertSee('Tulis Email')
            ->assertSee('madrasah@example.test')
            ->assertSee('<input type="hidden" name="action" value="send">', false);
    }

    public function test_recipient_is_validated_and_duplicate_across_fields_is_rejected(): void
    {
        $user = $this->user(['email-service.send']);
        $this->actingAs($user)->post(route('email-service.store'), $this->payload(['to_addresses' => 'bukan-email']))
            ->assertSessionHasErrors('to_addresses.0');
        $this->actingAs($user)->post(route('email-service.store'), $this->payload([
            'to_addresses' => 'wali@example.test',
            'cc_addresses' => 'WALI@example.test',
        ]))->assertSessionHasErrors('to_addresses');
    }

    public function test_subject_and_body_are_required(): void
    {
        $this->actingAs($this->user(['email-service.send']))
            ->post(route('email-service.store'), $this->payload(['subject' => '', 'body' => '']))
            ->assertSessionHasErrors(['subject', 'body']);
    }

    public function test_validation_errors_use_clear_indonesian_messages(): void
    {
        $response = $this->actingAs($this->user(['email-service.send']))
            ->post(route('email-service.store'), $this->payload([
                'to_addresses' => '',
                'action' => '',
            ]));

        $response->assertSessionHasErrors([
            'to_addresses' => 'Alamat email tujuan wajib diisi.',
            'action' => 'Tindakan pengiriman email wajib dipilih.',
        ]);
        $this->assertNotContains('validation.required', session('errors')->all());
    }

    public function test_invalid_attachment_is_rejected_and_not_stored(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user(['email-service.send']))
            ->post(route('email-service.store'), $this->payload([
                'attachments' => [UploadedFile::fake()->create('program.php', 5, 'text/x-php')],
            ]))->assertSessionHasErrors('attachments.0');

        Storage::disk('local')->assertDirectoryEmpty('outgoing-emails');
    }

    public function test_email_is_sent_and_record_becomes_sent(): void
    {
        Mail::fake();
        Storage::fake('local');
        $user = $this->user(['email-service.send']);

        $this->actingAs($user)->post(route('email-service.store'), $this->payload([
            'to_addresses' => "wali@example.test\ninstansi@example.test",
            'cc_addresses' => 'kepala@example.test',
            'attachments' => [UploadedFile::fake()->create('surat.pdf', 50, 'application/pdf')],
        ]))->assertRedirect();

        $email = OutgoingEmail::firstOrFail();
        $this->assertSame(OutgoingEmailStatus::Sent, $email->status);
        $this->assertNotNull($email->sent_at);
        Storage::disk('local')->assertExists($email->attachments[0]['path']);
        Mail::assertSent(ManualServiceEmail::class, fn (ManualServiceEmail $mail) => $mail->hasTo('wali@example.test')
            && $mail->hasTo('instansi@example.test') && $mail->hasCc('kepala@example.test'));
    }

    public function test_failed_delivery_marks_record_failed_without_raw_exception(): void
    {
        $email = $this->queuedEmail();
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('password=rahasia host=smtp.internal'));

        app(SendOutgoingEmail::class, ['outgoingEmailId' => $email->id])->handle();

        $email->refresh();
        $this->assertSame(OutgoingEmailStatus::Failed, $email->status);
        $this->assertStringNotContainsString('rahasia', (string) $email->error_message);
        $this->assertStringNotContainsString('smtp.internal', (string) $email->error_message);
    }

    public function test_failed_email_can_be_resent(): void
    {
        Mail::fake();
        $user = $this->user(['email-service.send']);
        $email = $this->queuedEmail($user);
        $email->update(['status' => OutgoingEmailStatus::Failed, 'error_message' => 'Pengiriman gagal.']);

        $this->actingAs($user)->post(route('email-service.resend', $email))
            ->assertRedirect(route('email-service.show', $email))
            ->assertSessionHas('status', 'Email berhasil dikirim.');

        $this->assertSame(OutgoingEmailStatus::Sent, $email->fresh()->status);
        Mail::assertSent(ManualServiceEmail::class);
    }

    public function test_from_address_comes_from_mail_config_not_user_input(): void
    {
        config(['mail.from.address' => 'resmi@example.test', 'mail.from.name' => 'Madrasah Resmi']);
        $email = $this->queuedEmail();
        $mailable = new ManualServiceEmail($email);

        $this->assertSame('resmi@example.test', $mailable->envelope()->from->address);
        $this->assertSame('Madrasah Resmi', $mailable->envelope()->from->name);
        $this->assertSame('resmi@example.test', $mailable->envelope()->replyTo[0]->address);
    }

    public function test_body_is_escaped_in_html_email_and_detail_page(): void
    {
        $user = $this->user(['email-service.view']);
        $email = $this->queuedEmail($user, '<script>alert("xss")</script>');

        $this->actingAs($user)->get(route('email-service.show', $email))
            ->assertOk()->assertDontSee('<script>', false)->assertSee('&lt;script&gt;', false);
        $html = (new ManualServiceEmail($email))->render();
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_manual_email_is_sent_immediately_even_when_default_queue_uses_database(): void
    {
        Mail::fake();
        config(['queue.default' => 'database']);
        $user = $this->user(['email-service.send']);

        $this->actingAs($user)
            ->post(route('email-service.store'), $this->payload())
            ->assertRedirect();

        $this->assertSame(OutgoingEmailStatus::Sent, OutgoingEmail::latest('id')->firstOrFail()->status);
        Mail::assertSent(ManualServiceEmail::class);
    }

    private function user(array $permissions = []): User
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'to_addresses' => 'wali@example.test',
            'cc_addresses' => '',
            'bcc_addresses' => '',
            'subject' => 'Surat Pemberitahuan',
            'body' => 'Isi pemberitahuan resmi.',
            'action' => 'send',
        ], $overrides);
    }

    private function queuedEmail(?User $user = null, string $body = 'Isi email'): OutgoingEmail
    {
        return OutgoingEmail::create([
            'user_id' => ($user ?? $this->user())->id,
            'to_addresses' => ['wali@example.test'],
            'subject' => 'Informasi Madrasah',
            'body' => $body,
            'status' => OutgoingEmailStatus::Queued,
        ]);
    }
}
