<?php

namespace App\Livewire\Settings;

use App\Domain\Channels\Actions\ConnectChannel;
use App\Domain\Channels\Enums\ChannelType;
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
    #[Validate('required|in:whatsapp,instagram,facebook')]
    public string $type = 'whatsapp';

    #[Validate('required|string')]
    public string $external_id = '';

    public string $name = '';

    #[Validate('required|string')]
    public string $access_token = '';

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
            );
        } catch (UniqueConstraintViolationException) {
            $this->addError('external_id', 'This channel is already connected to another workspace.');

            return;
        }

        $this->reset('external_id', 'name', 'access_token');
        unset($this->channels);
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

    public function render()
    {
        return view('livewire.settings.channels-page');
    }
}
