<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentChannel;
use App\Http\Requests\StorePublicOrderRequest;
use App\Models\Order;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function create(Template $template): View
    {
        abort_unless($template->is_active, 404);

        return view('orders.create', [
            'template' => $template,
        ]);
    }

    public function store(StorePublicOrderRequest $request, Template $template): RedirectResponse
    {
        abort_unless($template->is_active, 404);

        $price = $template->effectivePrice();

        $order = Order::query()->create([
            'order_number' => Order::generateOrderNumber(),
            'template_id' => $template->getKey(),
            'customer_name' => $request->string('customer_name')->trim()->value(),
            'customer_phone' => $request->string('customer_phone')->trim()->value(),
            'customer_email' => $request->filled('customer_email')
                ? $request->string('customer_email')->trim()->value()
                : null,
            'base_price' => $price,
            'addons' => null,
            'discount' => 0,
            'total' => $price,
            'status' => OrderStatus::Pending,
            'payment_channel' => PaymentChannel::ManualTransfer,
            'notes' => $request->filled('notes')
                ? $request->string('notes')->trim()->value()
                : null,
        ]);

        return redirect()
            ->route('order.thank-you', $order)
            ->with('order_number', $order->order_number);
    }

    public function thankYou(Order $order): View
    {
        return view('orders.thank-you', [
            'order' => $order->load('template'),
        ]);
    }
}
