<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Resources\OrderListResource;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * How many days of trading the revenue chart covers.
     */
    protected const int TREND_DAYS = 14;

    /**
     * The morning read on the order book: what came in, what is stuck, and who
     * is spending.
     */
    public function __invoke(): Response
    {
        return Inertia::render('dashboard', [
            'headline' => $this->headline(),
            'revenueTrend' => $this->revenueTrend(),
            'statusBreakdown' => $this->statusBreakdown(),
            'needsAttention' => $this->needsAttention(),
            'recentOrders' => $this->recentOrders(),
            'topCustomers' => $this->topCustomers(),
        ]);
    }

    /**
     * @return array<string, int>
     */
    protected function headline(): array
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->subDays(30);

        $monthRevenue = (int) $this->earning()->where('placed_at', '>=', $monthStart)->sum('total_cents');
        $monthCount = $this->earning()->where('placed_at', '>=', $monthStart)->count();

        return [
            'orders_today' => Order::query()->whereDate('placed_at', $today)->count(),
            'revenue_today' => (int) $this->earning()->whereDate('placed_at', $today)->sum('total_cents'),
            'revenue_30d' => $monthRevenue,
            'avg_order_value_30d' => $monthCount === 0 ? 0 : (int) round($monthRevenue / $monthCount),
            'open_orders' => Order::query()->whereIn('status', OrderStatus::open())->count(),
            'awaiting_payment' => Order::query()->where('status', OrderStatus::Pending)->count(),
        ];
    }

    /**
     * Daily earned revenue, with quiet days included so the chart keeps its shape.
     *
     * @return array<int, array{day: string, label: string, revenue_cents: int}>
     */
    protected function revenueTrend(): array
    {
        $from = Carbon::today()->subDays(self::TREND_DAYS - 1);

        $earned = $this->earning()
            ->where('placed_at', '>=', $from)
            ->selectRaw('DATE(placed_at) as day, SUM(total_cents) as revenue_cents')
            ->groupBy('day')
            ->pluck('revenue_cents', 'day');

        return Collection::times(self::TREND_DAYS, function (int $offset) use ($from, $earned): array {
            $day = $from->copy()->addDays($offset - 1);

            return [
                'day' => $day->toDateString(),
                'label' => $day->format('M j'),
                'revenue_cents' => (int) $earned->get($day->toDateString(), 0),
            ];
        })->all();
    }

    /**
     * @return array<int, array{value: OrderStatus, label: string, badge_class: string, count: int}>
     */
    protected function statusBreakdown(): array
    {
        $counts = Order::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Collection::make(OrderStatus::cases())
            ->map(fn (OrderStatus $status) => [
                'value' => $status,
                'label' => $status->label(),
                'badge_class' => $status->badgeClass(),
                'count' => (int) $counts->get($status->value, 0),
            ])
            ->all();
    }

    /**
     * Open orders that have waited longest — the queue to work through today.
     *
     * @return array<int, mixed>
     */
    protected function needsAttention(): array
    {
        return OrderListResource::collection(
            Order::query()
                ->whereIn('status', OrderStatus::open())
                ->with('customer')
                ->withCount('items')
                ->oldest('placed_at')
                ->limit(6)
                ->get()
        )->resolve();
    }

    /**
     * @return array<int, mixed>
     */
    protected function recentOrders(): array
    {
        return OrderListResource::collection(
            Order::query()
                ->with('customer')
                ->withCount('items')
                ->latest('placed_at')
                ->limit(6)
                ->get()
        )->resolve();
    }

    /**
     * @return array<int, array{name: string, email: string, orders_count: int, revenue_cents: int}>
     */
    protected function topCustomers(): array
    {
        return Customer::query()
            ->withCount(['orders as paid_orders_count' => fn (Builder $query) => $query
                ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Refunded])])
            ->withSum(['orders as revenue_cents' => fn (Builder $query) => $query
                ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Refunded])], 'total_cents')
            ->orderByDesc('revenue_cents')
            ->limit(5)
            ->get()
            ->map(fn (Customer $customer) => [
                'name' => $customer->name,
                'email' => $customer->email,
                'orders_count' => (int) $customer->paid_orders_count,
                'revenue_cents' => (int) $customer->revenue_cents,
            ])
            ->all();
    }

    /**
     * Orders that actually earned money — cancelled and refunded never count.
     *
     * @return Builder<Order>
     */
    protected function earning(): Builder
    {
        return Order::query()->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Refunded]);
    }
}
