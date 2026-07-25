<?php

namespace App\Http\Controllers;

use App\Actions\Orders\UpdateOrderStatus;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OrderStatusController extends Controller
{
    public function __construct(protected UpdateOrderStatus $updateOrderStatus) {}

    /**
     * Move an order to the next status the operator picked.
     */
    public function update(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $status = $request->status();

        $this->updateOrderStatus->execute($order, $status, Auth::user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Order :number is now :status.', [
                'number' => $order->order_number,
                'status' => $status->label(),
            ]),
        ]);

        return to_route('orders.show', $order);
    }
}
