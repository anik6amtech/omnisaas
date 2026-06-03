<?php

namespace App\Livewire\Orders;

use App\Domain\Orders\Actions\CreateOrderPaymentLink;
use App\Models\Order;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class OrdersPage extends Component
{
    public ?string $paymentUrl = null;

    /**
     * @return Collection<int, Order>
     */
    #[Computed]
    public function orders(): Collection
    {
        return Order::query()->with('items')->orderByDesc('created_at')->limit(50)->get();
    }

    public function paymentLink(string $orderId, CreateOrderPaymentLink $action): void
    {
        $order = Order::query()->find($orderId);

        if ($order !== null) {
            $this->paymentUrl = $action->execute($order);
        }
    }

    public function updateStatus(string $orderId, string $status): void
    {
        Order::query()->whereKey($orderId)->update(['status' => $status]);
        unset($this->orders);
    }

    public function render()
    {
        return view('livewire.orders.orders-page');
    }
}
