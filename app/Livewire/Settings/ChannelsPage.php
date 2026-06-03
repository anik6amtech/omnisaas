<?php

namespace App\Livewire\Settings;

use App\Domain\Channels\Actions\ConnectChannel;
use App\Domain\Channels\Enums\ChannelType;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Models\Channel;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Tenant Settings → Channels: connect a Meta channel (WhatsApp / Instagram /
 * Messenger). The manual-token form here is the dev path; the Meta Embedded
 * Signup / Facebook Login for Business OAuth flow resolves to the same
 * (external_id, access_token) and calls the same ConnectChannel action.
 */
#[Layout('components.layouts.app')]
class ChannelsPage extends Component
{
    use InteractsWithWorkspace;

    #[Validate('required|in:whatsapp,instagram,facebook')]
    public string $type = 'whatsapp';

    #[Validate('required|string')]
    public string $external_id = '';

    public string $name = '';

    #[Validate('required|string')]
    public string $access_token = '';

    // Optional — "bring your own Meta app" (self-hosted / agency). Blank ⇒ the
    // platform's shared app credentials (config/services.php) are used.
    public string $app_id = '';

    public string $app_secret = '';

    public string $verify_token = '';

    /**
     * @return Collection<int, Channel>
     */
    #[Computed]
    public function channels(): Collection
    {
        return Channel::query()->orderBy('type')->get();
    }

    public function connect(ConnectChannel $action): void
    {
        $this->validate();

        try {
            $action->execute(
                ChannelType::from($this->type),
                trim($this->external_id),
                trim($this->access_token),
                $this->name ?: null,
                trim($this->app_id) ?: null,
                trim($this->app_secret) ?: null,
                trim($this->verify_token) ?: null,
            );
        } catch (UniqueConstraintViolationException) {
            $this->addError('external_id', 'This channel is already connected to another workspace.');

            return;
        }

        $this->reset('external_id', 'name', 'access_token', 'app_id', 'app_secret', 'verify_token');
        unset($this->channels);
    }

    /** Per-channel webhook URL (bring-your-own-app); paste into that Meta app. */
    public function channelWebhookUrl(Channel $channel): string
    {
        return url("/webhooks/meta/{$channel->type->value}/{$channel->id}");
    }

    public function disconnect(string $id): void
    {
        Channel::query()->whereKey($id)->delete();
        unset($this->channels);
    }

    /** The webhook callback URL to register in the Meta App dashboard. */
    public function webhookUrl(string $type): string
    {
        return url("/webhooks/meta/{$type}");
    }

    /** The verify token to paste into the Meta webhook config (from .env). */
    public function verifyToken(): string
    {
        return (string) config('services.meta.webhook_verify_token');
    }

    /**
     * Step-by-step connection guide for the selected channel.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function setupSteps(): array
    {
        return match ($this->type) {
            'whatsapp' => [
                'In Meta for Developers, create an app and add the <b>WhatsApp</b> product.',
                'Under WhatsApp → API Setup, copy your <b>Phone number ID</b> into the field above.',
                'Create a System User with <code>whatsapp_business_messaging</code> + <code>whatsapp_business_management</code> and generate a <b>permanent access token</b>; paste it above.',
                'In WhatsApp → Configuration, set the Callback URL + Verify token below, then <b>Subscribe</b> to the <code>messages</code> field.',
                'Complete Business Verification (required for production sending).',
            ],
            'facebook' => [
                'In Meta for Developers, create an app and add the <b>Messenger</b> product.',
                'Connect your Facebook <b>Page</b> and copy its <b>Page ID</b> into the field above.',
                'Generate a <b>Page access token</b> for that Page and paste it above.',
                'In Messenger → Settings → Webhooks, set the Callback URL + Verify token below and subscribe to <code>messages</code>, <code>messaging_postbacks</code>.',
                'Submit for App Review (Advanced Access) for <code>pages_messaging</code> before going live.',
            ],
            'instagram' => [
                'Connect an Instagram <b>Professional/Business</b> account to a Facebook Page.',
                'In Meta for Developers, add the <b>Instagram</b> (Messaging) product and link the account.',
                'Copy your <b>Instagram account ID</b> and a token with <code>instagram_manage_messages</code> + <code>instagram_manage_comments</code> above.',
                'Set the Callback URL + Verify token below and subscribe to <code>messages</code> and <code>comments</code> (for comment-to-DM).',
                'Note the ~200 DMs/hour limit — the dispatcher queues the rest automatically.',
            ],
            default => [],
        };
    }

    public function render()
    {
        return view('livewire.settings.channels-page');
    }
}
