<div class="mx-auto max-w-5xl p-6">
    <h1 class="mb-4 text-xl font-semibold text-gray-900">Orders</h1>

    @if ($paymentUrl)
        <div class="mb-4 flex items-center justify-between rounded-lg bg-green-50 p-3 text-sm text-green-800">
            <span>Payment link ready:</span>
            <a href="{{ $paymentUrl }}" target="_blank" class="font-medium underline">Open checkout</a>
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-2">Ref</th><th class="px-4 py-2">Customer</th>
                    <th class="px-4 py-2">Total</th><th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Payment</th><th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($this->orders as $order)
                    <tr wire:key="o-{{ $order->id }}">
                        <td class="px-4 py-2 font-mono text-xs">{{ $order->reference }}</td>
                        <td class="px-4 py-2">{{ $order->customer_name }}</td>
                        <td class="px-4 py-2">{{ $order->total }} {{ $order->currency }}</td>
                        <td class="px-4 py-2">
                            <select wire:change="updateStatus('{{ $order->id }}', $event.target.value)"
                                class="rounded border-gray-300 text-xs">
                                @foreach (['new', 'confirmed', 'shipped', 'delivered', 'cancelled', 'returned'] as $s)
                                    <option value="{{ $s }}" @selected($order->status === $s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-4 py-2">
                            <span @class([
                                'rounded-full px-2 py-0.5 text-xs',
                                'bg-green-100 text-green-700' => $order->payment_status === 'paid',
                                'bg-gray-100 text-gray-600' => $order->payment_status !== 'paid',
                            ])>{{ $order->payment_status }}</span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            @if ($order->payment_status !== 'paid')
                                <button wire:click="paymentLink('{{ $order->id }}')"
                                    class="rounded-lg bg-amber-600 px-3 py-1 text-xs font-medium text-white hover:bg-amber-700">Payment link</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">No orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
