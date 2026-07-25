<?php

namespace App\Http\Controllers;

use App\Actions\Orders\CreateOrder;
use App\Actions\Orders\RecalculateOrderTotals;
use App\Enums\OrderStatus;
use App\Http\Requests\OrderIndexRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderListResource;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * The order book, filtered by the operator's search, status tab and date range.
     */
    public function index(OrderIndexRequest $request): Response
    {
        $orders = $this->filtered($request)
            ->withStatus($request->enum('status', OrderStatus::class))
            ->with('customer')
            ->withCount('items')
            ->orderBy($request->sort(), $request->direction())
            ->paginate($request->perPage())
            ->withQueryString();

        return Inertia::render('orders/index', [
            'orders' => OrderListResource::collection($orders),
            'filters' => $request->filters(),
            'summary' => $this->summary($request),
            'statusCounts' => $this->statusCounts($request),
            'statuses' => Collection::make(OrderStatus::cases())
                ->map(fn (OrderStatus $status) => [
                    'value' => $status,
                    'label' => $status->label(),
                ]),
        ]);
    }

    /**
     * The counter: catalogue on one side, running order on the other.
     */
    public function create(): Response
    {
        return Inertia::render('orders/create', [
            'products' => Product::query()
                ->active()
                ->with('category')
                ->orderBy('name')
                ->get()
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'category' => $product->category->name,
                    'price_cents' => $product->price_cents,
                    'image_url' => $product->image_url,
                ]),
            'customers' => Customer::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'pricing' => [
                'free_shipping_threshold_cents' => RecalculateOrderTotals::FREE_SHIPPING_THRESHOLD_CENTS,
                'flat_shipping_cents' => RecalculateOrderTotals::FLAT_SHIPPING_CENTS,
                'tax_rate' => RecalculateOrderTotals::TAX_RATE,
            ],
        ]);
    }

    /**
     * Ring up the order and open it at the top of the fulfilment queue.
     */
    public function store(StoreOrderRequest $request, CreateOrder $createOrder): RedirectResponse
    {
        $order = $createOrder->execute(
            customer: $request->customer(),
            lines: $request->lines(),
            actor: Auth::user(),
            discountCents: $request->integer('discount_cents'),
            notes: $request->string('notes')->value() ?: null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Order :number created.', ['number' => $order->order_number]),
        ]);

        return to_route('orders.show', $order);
    }

    /**
     * A single order with everything the operator needs to act on it.
     */
    public function show(Order $order): Response
    {
        $order->load(['customer', 'items', 'activities.user']);

        return Inertia::render('orders/show', [
            'order' => (new OrderResource($order))->resolve(),
        ]);
    }

    /**
     * Orders matching every filter except the status tab, which the tab counts
     * and the summary cards both need to stay comparable.
     *
     * @return Builder<Order>
     */
    protected function filtered(OrderIndexRequest $request): Builder
    {
        return Order::query()
            ->search($request->string('q')->value())
            ->placedBetween($request->date('date_from'), $request->date('date_to'));
    }

    /**
     * Headline metrics for the filtered order book. Cancelled and refunded
     * orders never count as revenue, so they are excluded from both the money
     * figures and the average they feed.
     *
     * @return array<string, int>
     */
    protected function summary(OrderIndexRequest $request): array
    {
        $status = $request->enum('status', OrderStatus::class);
        $scoped = fn (): Builder => $this->filtered($request)->withStatus($status);
        $earning = fn (): Builder => $scoped()->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Refunded]);

        $revenueCents = (int) $earning()->sum('total_cents');
        $earningCount = $earning()->count();

        return [
            'orders_count' => $scoped()->count(),
            'revenue_cents' => $revenueCents,
            'avg_order_value_cents' => $earningCount === 0 ? 0 : (int) round($revenueCents / $earningCount),
            'open_orders' => $scoped()->whereIn('status', OrderStatus::open())->count(),
        ];
    }

    /**
     * Per-status counts for the tab row, aware of every filter but the tab itself.
     *
     * @return array<string, int>
     */
    protected function statusCounts(OrderIndexRequest $request): array
    {
        $counts = $this->filtered($request)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Collection::make(OrderStatus::cases())
            ->keyBy('value')
            ->map(fn (OrderStatus $status, string $key) => (int) $counts->get($key, 0))
            ->put('all', (int) $counts->sum())
            ->all();
    }
}
