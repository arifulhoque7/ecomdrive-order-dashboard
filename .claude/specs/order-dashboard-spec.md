# Spec — Mini Order Operations Dashboard (EcomDrive assessment)

Laravel 13 + Inertia v3 + React 19 + shadcn/ui + MySQL. 4h budget.
This document is the complete build contract. Everything needed is written here.

---

## 0. Installed baseline (verified 2026-07-25)

Already scaffolded as `laravel/react-starter-kit` — **skip** the brief's
`composer create-project` / `breeze:install` steps.

| Thing | State |
|---|---|
| PHP | 8.4.23 (`^8.3`) |
| Laravel | `^13.17` |
| Inertia | `inertia-laravel ^3.0` + `@inertiajs/react ^3.0` |
| React / TS / Vite | 19.2 / 5.7 / 8 |
| Auth | **Fortify** `^1.37` (+2FA, passkeys) — not Breeze |
| Wayfinder | `^0.1.14` + vite plugin (`formVariants: true`) → `@/actions`, `@/routes` |
| UI | shadcn new-york, neutral base, cssVariables, lucide |
| Tailwind | v4 via `@tailwindcss/vite`; `@custom-variant dark (&:is(.dark *))` |
| Fonts | self-hosted via `laravel-vite-plugin/fonts` → `bunny('Instrument Sans')`, emitted by `@fonts` in `app.blade.php` |
| Tests | Pest 4 + pest-plugin-laravel, PHPUnit 12 |
| Static | Larastan 3, Pint, ESLint 9, Prettier 3 |
| Boost | `^2.2`, MCP wired |
| node / npm / pnpm | 22.22.2 / 10.9.7 / 10.8.1 |
| DB | sqlite now (10 tables, 0 users). MySQL 9.7.1 available, `root` / `password` |
| Git | **not a repo yet** |

shadcn components present: `alert avatar badge breadcrumb button card checkbox
collapsible dialog dropdown-menu icon input input-otp label navigation-menu
placeholder-pattern select separator sheet sidebar skeleton sonner spinner
toggle toggle-group tooltip`.

**To add**: `table tabs popover calendar textarea alert-dialog scroll-area`.

App routes today: `/`, `/dashboard` (inertia-only), `/settings/*`. `app/` holds
only `User` + Fortify/settings controllers. No `app/Enums`, no
`app/Http/Resources`, no domain `app/Actions`.

---

## 1. Engineering conventions

Binding rules for every file in this build.

**PHP / Laravel**
- **LESS IS MORE.** No ceremony, no indirection for a single call site.
- **No `->value` on enums.** Pass the enum itself; Eloquent casts and `Rule::enum`
  handle conversion. If a raw string is truly needed, expose a named method on
  the enum — never `->value` inline.
- **No `normalize*()` / `parse*()` methods.** Shape data at the boundary:
  Form Request `prepareForValidation`, model cast, or API Resource.
- **No `isset()`** → null-coalescing `??`.
- **Facades over helpers**: `Auth::id()`, `Gate::authorize()`, `Http::`, `Cache::`.
- **No `DB::`** — `Model::query()` and Eloquent aggregates only.
- **Collections over arrays** everywhere.
- **Resource controllers** by default; **single-action `__invoke` controllers**
  for complex logic; prefer a new controller over a non-resource method on an
  existing one (`OrderStatusController@update`, not `OrderController@updateStatus`).
- **Actions** live in `app/Actions/**`: single purpose, one `execute()` method,
  typed model/DTO params, explicit return type.
- **Form Requests always** — rules + custom messages, array-style rules.
- Constructor property promotion; explicit return types and param type hints on
  every method; PHPDoc array shapes; no inline comments unless the logic is
  genuinely tricky.
- Casts declared in a `casts(): array` method, not the `$casts` property.
- Relationship methods carry return type hints (`: BelongsTo`, `: HasMany`).
- Eager load to kill N+1. `env()` only inside `config/`.
- `php artisan make:*` for every new file, with `--no-interaction`.

**Database**
- **No column defaults in migrations.** Set values through `$attributes` on the
  model or on `creating`.
- **`json` column for unstructured data**, cast on the model → `ai_insight`,
  `shipping_address`, `meta`.

**Frontend**
- `cn()` for all class merging.
- Wayfinder imports only — never a hardcoded URL string.
- After any TS change: `npm run types:check && npm run lint:check`.

**Tests**
- Pest, feature-first, factories with states. Iterate with
  `php artisan test --compact --filter=Order`; full run `--parallel`.

---

## 2. Product decisions

1. **MySQL** — database `ecomdrive`, local creds `root` / `password`. The brief
   asks for MySQL or PostgreSQL.
2. **Keep Fortify auth.** All order routes behind `auth` + `verified`; seed one
   demo operator. Zero auth code written.
3. **Inertia controllers are the API.** One true JSON endpoint for the AI
   feature; everything else is Inertia visits with `preserveState`.
4. **Money as integer cents** (`bigInteger`), formatted client-side with
   `Intl.NumberFormat`.
5. Status transitions are a **domain rule on the enum**, enforced by the Form
   Request → invalid transition returns 422.
6. No new base folders. Everything lands in existing `app/`, `resources/js/`,
   `database/`, `tests/`.

---

## 3. Domain

### `app/Enums/OrderStatus.php` — backed string enum

Cases: `Pending, Processing, Shipped, Delivered, Cancelled, Refunded`.

Methods:
- `label(): string`
- `badgeClass(): string` — light-pill Tailwind classes (§5.5)
- `transitions(): array` — reachable `self[]`
- `canTransitionTo(self $next): bool`
- `isOpen(): bool` — pending | processing | shipped (drives the "open orders" card)

Transition matrix:

```
Pending    → Processing, Cancelled
Processing → Shipped, Cancelled
Shipped    → Delivered, Refunded
Delivered  → Refunded
Cancelled  → (terminal)
Refunded   → (terminal)
```

### `app/Enums/ActivityType.php`

`OrderPlaced, StatusChanged, Note, Payment, AiInsight`.

### Tables — no column defaults

**customers**
`id, name, email uniq, phone nullable, city nullable, timestamps`

**orders**
```
id, order_number uniq, customer_id FK cascade,
status string index, currency char(3),
subtotal_cents, discount_cents, shipping_cents, tax_cents, total_cents  (bigInteger)
shipping_address json nullable, notes text nullable,
placed_at timestamp index,
ai_insight json nullable, ai_insight_generated_at timestamp nullable,
timestamps
index (status, placed_at)
```

**order_items**
`id, order_id FK cascade, sku, name, quantity unsignedInteger,
unit_price_cents, line_total_cents, timestamps`

**order_activities**
`id, order_id FK cascade, user_id FK nullOnDelete nullable, type string,
from_status nullable, to_status nullable, description text, meta json nullable,
timestamps, index (order_id, created_at)`

### Models

- **`Order`** — `casts()`: `status => OrderStatus::class`, `placed_at =>
  'datetime'`, `shipping_address => 'array'`, `ai_insight => 'array'`,
  `ai_insight_generated_at => 'datetime'`.
  `$attributes`: `currency => 'USD'`, `status => OrderStatus::Pending`,
  `discount_cents => 0`, `shipping_cents => 0`, `tax_cents => 0`.
  Relations `customer(): BelongsTo`, `items(): HasMany`, `activities(): HasMany`.
  Scopes `search(?string $term)`, `withStatus(?OrderStatus $status)`,
  `placedBetween(?CarbonInterface $from, ?CarbonInterface $to)`.
- **`Customer`** — `orders(): HasMany`.
- **`OrderItem`**, **`OrderActivity`** — `casts()` for `type`, `from_status`,
  `to_status`, `meta`.

### Seed

`CustomerFactory` ×30. `OrderFactory` ×120 spread over the last 90 days across
all six statuses, each with 1–5 items. Totals are recomputed in `afterCreating`
from the actual items (subtotal → discount → shipping → 8% tax → total) so no
figure contradicts another. Every order gets an `OrderPlaced` activity plus 0–3
realistic follow-ups. `DatabaseSeeder` also creates the demo operator
`operator@ecomdrive.test` / `password`, email-verified.

---

## 4. Backend surface

`routes/web.php`, inside `middleware(['auth','verified'])`:

| Verb | URI | Controller | Notes |
|---|---|---|---|
| GET | `/orders` | `OrderController@index` | resource controller |
| GET | `/orders/{order}` | `OrderController@show` | |
| PATCH | `/orders/{order}/status` | `OrderStatusController@update` | own controller |
| POST | `/orders/{order}/insight` | `GenerateOrderInsightController` | `__invoke`, JSON |

`/dashboard` redirects to `orders.index` so the starter route isn't dead.

**`OrderIndexRequest`** — `q` string≤100 nullable; `status` nullable
`Rule::enum(OrderStatus::class)`; `date_from` / `date_to` date with
`after_or_equal`; `sort` in `placed_at|total_cents|status`; `direction` in
`asc|desc`; `per_page` in `10,20,50,100`. Custom messages on the date range.

**`index` props**
- `orders` — `OrderListResource::collection($paginator)` with `withQueryString()`
- `filters` — current filter state (controlled inputs + shareable URL)
- `statusCounts` — `groupBy('status')->count()` over the filtered-minus-status
  set, feeding the tab counters
- `summary` — one aggregate query over the filtered set: `total_orders`,
  `revenue_cents`, `avg_order_value_cents`, `open_orders`

**`show`** eager-loads `customer`, `items`, `activities.user`; returns
`OrderResource` (nesting `OrderItemResource`, `OrderActivityResource`) plus the
allowed next statuses from the enum, so the UI never offers an illegal move.

**Status update**
- `UpdateOrderStatusRequest`: `status` required + `Rule::enum` + a closure rule
  asserting `$order->status->canTransitionTo($next)`, message like
  *"Cannot move a delivered order back to processing."*
- `app/Actions/Orders/UpdateOrderStatus::execute(Order $order, OrderStatus
  $status, User $actor): Order` — transactional: update the order, append a
  `StatusChanged` activity carrying `from_status`, `to_status`, `user_id`.
- Controller redirects back with a flash message; the existing
  `use-flash-toast` hook + `sonner` surface it.

**Resources**
- `OrderListResource` — lean: number, customer name/email, status + label +
  badge class, items count, `total_cents`, `placed_at`.
- `OrderResource` — full order, totals block, nested items/activities,
  `allowed_transitions`, `ai_insight`, `ai_insight_generated_at`.

---

## 5. Design system

Primary color is the product's own; all spacing, radius, border and rhythm
values below are fixed — implement them exactly as written.

### 5.1 Brand color

In `resources/css/app.css`, replace the starter's neutral primary:

```css
:root {
  --primary: oklch(0.548 0.238 296.5);          /* violet-plum */
  --primary-foreground: oklch(0.985 0 0);
  --ring: oklch(0.548 0.238 296.5);
}
.dark {
  --primary: oklch(0.652 0.205 296.5);
  --primary-foreground: oklch(0.205 0.02 296);
  --ring: oklch(0.652 0.205 296.5);
}
```

**Single swap point** — change these two blocks and the whole app re-brands.
Nothing else may hardcode the hue. `--radius: 0.625rem` stays → cards use
`rounded-lg`.

### 5.2 Typography — sans + mono pairing

Add the mono family in `vite.config.ts` (self-hosted, same provider as the
existing font, no CDN):

```ts
fonts: [
  bunny('Geist',      { weights: [400, 500, 600, 700] }),
  bunny('Geist Mono', { weights: [400, 500] }),
],
```

Fallback if the provider lacks Geist at build time: `Inter` + `JetBrains Mono`.
Verify once, then lock.

```css
@theme {
  --font-sans: 'Geist', ui-sans-serif, system-ui, sans-serif,
               'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
  --font-mono: 'Geist Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
}
```

**Mono is the data font, not decoration.**

| `font-mono` | `font-sans` |
|---|---|
| order number `ORD-4821XZ` | customer names |
| SKU | product titles |
| every money figure | labels, headings, prose |
| quantities, tab counts, pagination counts | button text |
| dates and timestamps in table + timeline | descriptions, AI prose |

Every numeric column adds `tabular-nums` so digits align row to row — that is
the entire point of the pairing. Applies to summary card values, item line
totals, the totals block, and the "1–20 of 120" footer.

Type scale:
- page title — `text-2xl font-bold leading-8`
- card / section title — `text-lg font-semibold`
- table header — `text-[12px] font-normal uppercase leading-[1.4] tracking-normal text-[#828282]`
- body / cell — `text-sm`
- meta / footer — `text-xs text-muted-foreground`
- summary value — `text-2xl font-bold font-mono tabular-nums`

### 5.3 Page shell

```
<section className="mx-auto w-full max-w-full">
  <header className="mb-6 flex items-center justify-between gap-4">    ← title + CTA
    <h1 className="text-2xl font-bold leading-8 text-foreground">
  [summary cards grid]                                                 ← mb-6
  <div className="rounded-lg border border-border bg-card shadow-sm">  ← ONE card
    [filters row] [secondary filters] [table] [footer]
  </div>
</section>
```

Hard rule: **loading / empty / error swap only the table body.** Card chrome,
tabs and search stay mounted so the page never reflows while the operator types.

### 5.4 Summary cards

Grid `grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6`. Each shadcn `Card` at
`p-4`:

- icon chip — `inline-flex size-7 items-center justify-center rounded-md bg-primary/10 text-primary`
- label — `text-xs font-medium uppercase tracking-wide text-muted-foreground`
- value — `mt-2 text-2xl font-bold font-mono tabular-nums text-foreground`
- meta — `text-xs text-muted-foreground`

Cards: **Total orders**, **Revenue**, **Avg order value**, **Open orders**.
All react to the active filters; state that in the `text-xs` caption.

### 5.5 Status badge — light pill, `rounded-md`

| status | light | dark |
|---|---|---|
| Delivered | `bg-emerald-100 text-emerald-800` | `dark:bg-emerald-500/15 dark:text-emerald-300` |
| Shipped | `bg-sky-100 text-sky-800` | `dark:bg-sky-500/15 dark:text-sky-300` |
| Processing | `bg-amber-100 text-amber-800` | `dark:bg-amber-500/15 dark:text-amber-300` |
| Pending | `bg-neutral-100 text-neutral-700` | `dark:bg-neutral-500/15 dark:text-neutral-300` |
| Cancelled | `bg-rose-100 text-rose-800` | `dark:bg-rose-500/15 dark:text-rose-300` |
| Refunded | `bg-violet-100 text-violet-800` | `dark:bg-violet-500/15 dark:text-violet-300` |

`OrderStatus::badgeClass()` is the source of truth; React just consumes the string.

### 5.6 Filters row

- container — `flex flex-wrap items-center justify-between gap-3 border-b border-border px-4 pt-3 pb-2`
- left: status tabs · right: search + funnel toggle
- search — `relative` wrapper; `<Search size={16}/>` at
  `pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground`;
  input `h-9 w-60 rounded-md border-border pl-9 text-sm`; 300 ms debounce
- funnel button — `relative inline-flex size-5 items-center justify-center transition-colors`,
  active `text-primary` else `text-muted-foreground hover:text-foreground`;
  active-count badge `absolute -right-1.5 -top-1.5 inline-flex h-4 min-w-4
  items-center justify-center rounded-full bg-primary px-1 text-[10px]
  font-medium text-primary-foreground`
- secondary row (date-from, date-to, sort, per-page) revealed underneath —
  `flex flex-wrap items-center gap-2 border-b border-border bg-muted/20 px-4 py-3`;
  "Clear" button `ml-auto h-9 gap-1.5 border-border bg-card text-sm
  text-muted-foreground hover:bg-muted hover:text-foreground` with `<X size={14}/>`
- the secondary row stays open whenever any secondary filter is active

### 5.7 Status tabs — underline, not pills

Row — `-mb-2 flex min-w-0 max-w-full items-stretch overflow-x-auto pb-2`.
Tab — `relative inline-flex h-11 shrink-0 items-center gap-1.5 whitespace-nowrap
px-4 text-sm font-medium transition-colors`; active `text-primary`, else
`text-foreground hover:text-primary`. Count — `font-mono text-[#a5a5aa]`.
Underline — `absolute inset-x-0 -bottom-2 h-0.5`, `bg-primary` when active else
`bg-transparent`. `role="tablist"` / `role="tab"` / `aria-selected`.

Tabs: All · Pending · Processing · Shipped · Delivered · Cancelled · Refunded.

### 5.8 Orders table

- `bg-card` > `overflow-x-auto` > `table w-full min-w-256 text-left`, with
  `role="grid"`, `aria-label`, and a `<caption className="sr-only">`
- `thead` — `border-b border-border bg-card`; header row `h-10`
- `th` — `whitespace-nowrap px-2 text-[12px] font-normal uppercase leading-[1.4]
  tracking-normal text-[#828282]`; sortable columns wrap the label in
  `button inline-flex items-center gap-1 uppercase hover:text-foreground` with
  `ArrowUpDown / ArrowUp / ArrowDown size={12}`; `aria-sort` set correctly
- `tbody tr` — `group h-18 border-b border-border bg-card last:border-b-0
  hover:bg-muted/40`; the whole row navigates to the order
- `td` — `px-2 align-middle text-sm text-foreground`; first cell `px-4`, last
  cell `pr-4 pl-2 text-right`
- **sticky columns** — head `sticky z-20 bg-card`, body
  `sticky z-10 bg-card group-hover:bg-muted/40`; order number pinned `left-0`,
  row actions pinned `right-0`
- columns — Order # (mono, medium) · Customer (name over email, two lines) ·
  Items (mono count) · Total (mono tabular, right-aligned) · Status (badge) ·
  Placed (mono date + `text-xs` relative) · ⋯ actions
- footer — `flex flex-wrap items-center justify-between gap-3 border-t
  border-border px-4 py-3 text-sm text-muted-foreground`; left
  `text-xs font-mono` "Showing 1–20 of 120"; right holds rows-per-page `Select`
  (trigger `h-8 cursor-pointer rounded-md border border-border bg-card pl-2
  pr-6 text-xs font-medium focus:border-primary focus:outline-none`), pager
  buttons `inline-flex size-8 items-center justify-center rounded-md border
  border-border bg-card hover:bg-muted disabled:opacity-40` with
  `ChevronLeft / ChevronRight size={14}`, and the page indicator
  `min-w-20 px-2 text-center text-xs font-mono font-medium`

### 5.9 Skeleton / empty / error

- **skeleton** — `divide-y divide-border`, 8 rows of
  `flex items-center gap-4 px-6 py-4` holding `Skeleton h-3.5` bars plus an
  `h-5 w-16 rounded-full` badge placeholder; `role="status" aria-busy
  aria-live="polite"`
- **empty** — `flex flex-col items-center px-6 py-16 text-center`; lucide
  `PackageSearch` inside a `size-16 rounded-full bg-primary/10 text-primary`
  badge; title `mt-10 text-2xl font-bold leading-8`; body
  `mt-3 max-w-md text-sm leading-6 text-muted-foreground`; CTA is
  "Clear filters" when filters are active
- **error** — same frame, destructive-tinted chip, "Retry" button

### 5.10 Order detail

```
[← back link]  text-sm text-muted-foreground hover:text-foreground
<header className="mb-6 flex flex-wrap items-center justify-between gap-4">
  left:  h1 font-mono text-2xl font-bold  ·  status badge  ·  placed-at meta
  right: status Select (allowed transitions only)  ·  Regenerate insight
<div className="grid gap-6 lg:grid-cols-3">
  left (lg:col-span-2):  items table card  +  totals block
  right:                 customer card · shipping card · AI insight card
</div>
<activity timeline card className="mt-6">
```

- **Detail cards** — `rounded-lg border border-border bg-card p-6 shadow-sm`;
  title `text-lg font-semibold`; divider `mb-4 mt-4 h-px w-full bg-border`;
  body `dl grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2`. Each item: icon
  chip `size-7 rounded-md bg-primary/10 text-primary`, `dt`
  `text-xs font-medium uppercase tracking-wide text-muted-foreground`, `dd`
  `text-sm` rendering `—` when blank.
- **Items table** — same `th` / `td` metrics as §5.8 with rows at `h-14`;
  SKU, qty, unit price and line total all `font-mono tabular-nums`; numeric
  columns right-aligned.
- **Totals** — right-aligned `dl` beneath the items table; rows
  `flex justify-between py-1 text-sm` with muted sans labels and
  `font-mono tabular-nums` values; grand total
  `mt-2 border-t border-border pt-2 text-lg font-bold`.
- **Timeline** — `relative pl-6` with rail
  `absolute left-2 top-1 bottom-1 w-px bg-border`; each entry `relative py-3`
  with dot `absolute -left-4 top-4 size-2 rounded-full bg-primary` (muted for
  non-status events); description `text-sm`; actor + timestamp
  `mt-0.5 text-xs font-mono text-muted-foreground`.

### 5.11 Buttons, motion, a11y

- primary CTA — `inline-flex h-10 items-center gap-2 rounded-md px-5 text-sm
  font-medium leading-5 shadow-sm`, lucide icon `size={16} strokeWidth={2}`
- icon-only controls — `size-8` or `size-5`, each with an `aria-label`
- motion limited to `transition-colors`; no layout animation
- inputs focus with `focus:border-primary focus:outline-none`
- dark mode is already wired through `use-appearance`; every custom color ships
  a `dark:` counterpart

---

## 6. Frontend files

```
resources/js/pages/orders/index.tsx
resources/js/pages/orders/show.tsx
resources/js/components/orders/
  order-summary-cards.tsx      order-filters.tsx        order-status-tabs.tsx
  orders-table.tsx             order-status-badge.tsx   order-status-select.tsx
  order-items-table.tsx        order-totals.tsx         order-activity-timeline.tsx
  ai-insight-card.tsx          orders-empty.tsx         orders-skeleton.tsx
  customer-cell.tsx            money.tsx
resources/js/types/orders.d.ts
```

- `app-sidebar.tsx` gains an **Orders** entry (`Package` icon) as the primary
  nav item.
- URL state — search, status, date range, sort, page all live in the query
  string. Changes go through `router.get(orders.index.url({ query }), {},
  { preserveState: true, preserveScroll: true, replace: true })`, so the back
  button and shared links both work.
- Every route call through Wayfinder
  (`@/actions/App/Http/Controllers/OrderController`).
- `cn()` for every conditional class.
- `money.tsx` exports a single formatter component rendering cents as
  `font-mono tabular-nums` currency.

---

## 7. AI feature

`POST /orders/{order}/insight` → `GenerateOrderInsightController::__invoke` →
`app/Actions/Orders/GenerateOrderInsight::execute(Order $order): array`.

- Calls the **Anthropic Messages API** (`claude-sonnet-5`) via
  `Http::withHeaders(...)->timeout(20)->post(...)`. No new composer dependency.
- Config in `config/services.php` → `anthropic.key`, `anthropic.model`; env
  `ANTHROPIC_API_KEY`. `env()` never leaves the config file.
- The prompt carries an order snapshot — status, age in days, totals, items,
  address completeness, activity timeline — and demands strict JSON:

```json
{
  "summary": "2–3 sentence recap of what happened on this order",
  "next_actions": ["…"],
  "missing_info": ["…"],
  "draft_reply": "customer-ready support message"
}
```

One call satisfies all four AI capabilities the brief lists: summarize activity,
suggest next actions, detect incomplete information, generate a support response.

- The result persists to `orders.ai_insight` + `ai_insight_generated_at` (json
  column, cast on the model), so reopening an order costs nothing. An explicit
  **Regenerate** button forces a fresh call.
- **Graceful degradation is required.** Missing key or HTTP failure produces a
  deterministic rule-based insight — status-driven next actions, missing
  phone/email/address detection, templated reply — tagged `"source":
  "fallback"`, still returning HTTP 200. The grader can run the app with no API
  key and still see the feature work.
- `ai-insight-card.tsx` — button fires a `useHttp` POST; pulsing skeleton while
  pending; then sections for summary (sans prose), next actions (checklist with
  `CheckCircle2 size={14}`), missing info (amber chips) and the draft reply in a
  `rounded-md border border-border bg-muted/30 p-3 text-sm` block with a copy
  button (`use-clipboard` already exists). A `text-xs text-muted-foreground`
  line shows generated-at and `source`.

---

## 8. Tests (Pest)

The brief asks for at least two meaningful tests; ship four feature tests plus
one unit test.

1. **`OrderIndexTest`** — search matches both order number and customer name;
   status filter narrows results; date-range filter works; summary values and
   tab counts match the filtered set; guests are redirected. Assertions via
   `assertInertia`.
2. **`OrderStatusUpdateTest`** — a legal transition persists **and** writes an
   `order_activities` row with the right `from_status`, `to_status`, `user_id`;
   an illegal transition returns 422 with the status unchanged and no activity
   row created.
3. **`GenerateOrderInsightTest`** — `Http::fake()` an Anthropic payload → parsed
   and cached onto the order; a faked failure → fallback insight, HTTP 200,
   `source=fallback`, no exception leaking.
4. **`OrderShowTest`** — detail renders items and activities, the
   allowed-transition list matches the enum, and there is no N+1 (query-count
   assertion).
5. **`OrderStatusEnumTest`** (unit) — the full transition matrix; terminal
   states expose no outgoing moves.

Factories carry states (`OrderFactory::pending()`, `->delivered()`,
`->withItems($n)`); all feature tests use `RefreshDatabase`.

---

## 9. Gates before submit

```bash
vendor/bin/pint --dirty --format agent
composer types:check                 # larastan
npm run lint:check && npm run format:check && npm run types:check
php artisan test --parallel
npm run build
```

---

## 10. Deploy + submission

- `git init`; commits authored `Ariful Hoque <hoquea57@gmail.com>`, **no
  Co-Authored-By trailer**; push to a public GitHub repo.
- Host on **Laravel Cloud** — MySQL addon; env `APP_KEY`, `ANTHROPIC_API_KEY`;
  build step `npm run build`; release step `php artisan migrate --seed --force`.
- README covers: local setup · architecture and technology choices · AI tooling
  (Claude Code as the build agent, Anthropic Messages API as the shipped runtime
  feature) · known limitations (no frontend test runner, no order create/edit,
  single tenant, AI response not streamed) · potential improvements.
- Screen capture ≤5 min: list → tabs / search / date filter → summary cards
  react → order detail → status change with the timeline entry appearing → AI
  insight → dark mode.

---

## 11. Build order (4h)

| # | Step | ~min |
|---|---|---|
| 1 | MySQL + `.env`; enums; 4 migrations; models with casts + scopes; factories; seeder | 45 |
| 2 | Form Requests, Resources, controllers, action, routes, `wayfinder:generate` | 45 |
| 3 | Theme pass: primary tokens, Geist + Geist Mono, shadcn adds, sidebar entry | 20 |
| 4 | Orders index: summary cards, tabs, filters, table, footer, empty + skeleton | 50 |
| 5 | Order detail: items, totals, cards, timeline, status select | 30 |
| 6 | AI action + endpoint + card + fallback | 30 |
| 7 | Tests green; Pint / PHPStan / ESLint / types green | 25 |
| 8 | README, git, deploy, video | 25 |
