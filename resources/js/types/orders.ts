export type OrderStatus =
    | 'pending'
    | 'processing'
    | 'shipped'
    | 'delivered'
    | 'cancelled'
    | 'refunded';

export type StatusOption = {
    value: OrderStatus;
    label: string;
};

export type OrderListItem = {
    id: number;
    order_number: string;
    customer_name: string;
    customer_email: string;
    status: OrderStatus;
    status_label: string;
    status_badge_class: string;
    items_count: number;
    currency: string;
    total_cents: number;
    placed_at: string;
};

export type OrderItem = {
    id: number;
    sku: string;
    name: string;
    image_url: string | null;
    quantity: number;
    unit_price_cents: number;
    line_total_cents: number;
};

export type OrderActivity = {
    id: number;
    type: string;
    type_label: string;
    from_status: OrderStatus | null;
    to_status: OrderStatus | null;
    to_status_badge_class: string | null;
    description: string;
    actor: string | null;
    created_at: string;
};

export type OrderInsight = {
    summary: string;
    next_actions: string[];
    missing_info: string[];
    draft_reply: string;
    source: string;
};

export type OrderDetail = {
    id: number;
    order_number: string;
    status: OrderStatus;
    status_label: string;
    status_badge_class: string;
    allowed_transitions: StatusOption[];
    currency: string;
    totals: {
        subtotal_cents: number;
        discount_cents: number;
        shipping_cents: number;
        tax_cents: number;
        total_cents: number;
    };
    customer: {
        name: string;
        email: string;
        phone: string | null;
        city: string | null;
    };
    shipping_address: Record<string, string> | null;
    notes: string | null;
    placed_at: string;
    items: OrderItem[];
    activities: OrderActivity[];
    ai_insight: OrderInsight | null;
    ai_insight_generated_at: string | null;
};

export type OrderFilters = {
    q: string | null;
    status: OrderStatus | null;
    date_from: string | null;
    date_to: string | null;
    sort: string;
    direction: 'asc' | 'desc';
    per_page: string;
};

export type OrderSummary = {
    orders_count: number;
    pending_orders: number;
    delivered_orders: number;
    revenue_cents: number;
    avg_order_value_cents: number;
    open_orders: number;
};

export type StatusCounts = Record<OrderStatus | 'all', number>;

export type Paginated<T> = {
    data: T[];
    meta: {
        current_page: number;
        from: number | null;
        to: number | null;
        total: number;
        per_page: number;
        last_page: number;
    };
};
