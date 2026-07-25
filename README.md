# EcomDrive — Order Operations Dashboard

An internal tool for an e-commerce operations team: browse and filter the order
book, open an order to see its items, totals and full activity history, move it
along the fulfilment path, ring up new orders at a point-of-sale counter, and
ask an AI assistant what to do next.

Built with Laravel 13, Inertia v3, React 19 and MySQL.

## Submission

| | |
| --- | --- |
| **Repository** | <https://github.com/arifulhoque7/ecomdrive-order-dashboard> (private) |
| **Live deployment** | _pending — see “Deploying” below_ |
| **Screen recording** | _≤ 5 min walkthrough: orders → filters → detail → status change → POS sale → AI insight_ |
| **Approximate time spent** | ~4 hours |
| **Demo login** | `operator@ecomdrive.test` / `password` |

### How the brief is covered

| Requirement | Where |
| --- | --- |
| View a list of orders | `/orders` — paginated table, sortable columns |
| Search and filter by customer, status and date | Search matches order number, customer name and email; status tabs with live counts; date-range filter; all of it in the URL |
| Open an order: details, items, totals, activity history | `/orders/{id}` — line items with photos, totals breakdown, customer and shipping cards, full timeline |
| Update the order status | Status control offering only legal transitions; illegal moves rejected with a 422 and an audit row written for every change |
| Summary cards with key metrics | Orders index (filter-aware) and a dedicated dashboard with revenue trend, order-book split, oldest open orders and top customers |
| AI-assisted feature | `POST /orders/{id}/insight` — one call returns an activity summary, next actions, missing-information flags and a draft customer reply |
| Persistent storage | MySQL, with migrations, factories and seeders |
| At least two meaningful automated tests | 85 Pest tests (see the Tests section) |

---

## Running it locally

**Requirements:** PHP 8.3+, Composer, Node 20+, MySQL 8+.

```bash
git clone <this-repo> ecomdrive && cd ecomdrive

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Point `.env` at a database you have created:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecomdrive
DB_USERNAME=root
DB_PASSWORD=
```

Then build the schema, seed it, and start everything:

```bash
php artisan migrate --seed
composer run dev          # serves the app, queue, logs and Vite together
```

Open <http://localhost:8000> and sign in:

| Email | Password |
| --- | --- |
| `operator@ecomdrive.test` | `password` |

The seed creates 10 categories, 24 products (photography from
[Shopify Burst](https://burst.shopify.com), free for commercial use), 32
customers, ~124 orders spread over 90 days, and an activity trail on every order
that matches the status it ended up in.

### The AI assistant (optional)

The insight feature works with **no key configured** — it falls back to a
deterministic, rule-based brief.

To use a real model, sign in and go to **Settings → AI assistant**: choose a
provider, paste its API key, hit **Refresh models** to load that account's
catalogue, pick one and save. The key is stored encrypted in the database.

The same thing can be seeded from `.env` instead, which is what a fresh install
falls back to:

```dotenv
AI_PROVIDER=claude          # claude | openai | gemini
ANTHROPIC_API_KEY=sk-ant-...
# OPENAI_API_KEY=sk-...
# GEMINI_API_KEY=...
```

### Checks

```bash
php artisan test           # 85 tests
vendor/bin/pint            # PHP formatting
composer types:check       # Larastan
npm run lint:check && npm run types:check && npm run format:check
```

---

## What it does

| Screen | What's there |
| --- | --- |
| **Dashboard** | Today's orders and takings, 30-day revenue and AOV, a 14-day revenue chart, the order book split by status, the oldest open orders ("needs attention"), recent orders, and top customers by spend. |
| **Orders** | Filterable order book — search by order number, customer name or email; status tabs with live counts; date range; sortable columns; page size. Summary cards recompute against the active filter, and the whole filter state lives in the URL. |
| **Order detail** | Line items with product photos, a totals breakdown, customer and shipping cards, the full activity timeline, a status control that only offers legal moves, and the AI insight panel. |
| **New order (POS)** | Product grid with category tabs and search, a running cart with quantity steppers, existing-customer picker or inline new customer, discount and notes, and live totals that mirror the server's pricing rules exactly. |
| **Products** | Catalogue table with photo, SKU, category, price, units sold and visibility. Add and edit in a dialog. Hidden products disappear from the counter. |
| **Categories** | Add, rename and describe categories, with a live product count. Deleting one that still holds products is refused. |

---

## Architecture and choices

### Backend

- **Laravel 13 + Inertia v3.** Inertia controllers *are* the API — they return
  typed props over XHR, so there is no second serialization layer to keep in
  sync. The one true JSON endpoint is the AI insight route, which the client
  calls with Inertia's `useHttp`.
- **Money is stored in integer cents** (`*_cents` columns) and formatted at the
  edge with `Intl.NumberFormat`. No floats touch a price.
- **`RecalculateOrderTotals` is the single source of truth for money.** Subtotal
  is summed from line items, discount is capped at subtotal, shipping is free at
  or above $75 (else a flat $9.95), tax is 8.25% of the net. The seeder, the POS
  and any future edit path all call the same action, so an order's totals can
  never contradict its items.
- **Status transitions live on the enum.** `OrderStatus` owns the matrix
  (`Pending → Processing → Shipped → Delivered`, with cancel/refund branches and
  two terminal states), its own label, and its badge colours. The Form Request
  rejects an illegal move with a 422; the UI only ever renders the legal ones.
- **Line items snapshot the product** (name, SKU, price, image) at the moment of
  sale, so editing or deleting a catalogue product never rewrites history.
- **Prices always come from the catalogue, never the request** — a tampered
  `unit_price_cents` in the POST body is ignored, and there is a test for it.
- Every write goes through a single-purpose action in `app/Actions/Orders/`
  (`CreateOrder`, `UpdateOrderStatus`, `RecalculateOrderTotals`,
  `GenerateOrderInsight`) with one `execute()` method, wrapped in a transaction
  where it touches more than one table.
- Validation is always a Form Request; reads go through API Resources; queries
  eager-load to keep the detail page free of N+1s (there is a query-count test).

### Frontend

- **React 19 + TypeScript**, with **Wayfinder** generating typed route helpers —
  no hand-written URL strings anywhere.
- **One design system, applied once.** A shared `DataTable` primitive carries the
  table metrics (40px header row, 72px body rows, 12px uppercase headers, edge
  padding) and a shared `TableToolbar` carries the filter row (underline tabs
  with counts + search). Orders, order items, products and categories all render
  through them, so the four tables cannot drift apart.
- **Typography does work.** Plus Jakarta Sans for the interface, Geist Mono with
  `tabular-nums` for every piece of data — order numbers, SKUs, money, dates,
  counts — so numeric columns line up digit for digit. Both are self-hosted via
  the Vite fonts plugin (no CDN request at runtime).
- Radii, control heights and button sizes come from one scale
  (`--radius: 0.75rem`; inputs and selects `h-10`, toolbar controls `h-9`,
  compact controls `h-8`; CTAs `h-10 px-5`).
- Filter state is URL state, so the back button and a pasted link both reproduce
  exactly what the operator was looking at.

### Data model

```
customers ──< orders ──< order_items      (snapshot of what was sold)
                   └──< order_activities  (who changed what, when)
categories ──< products                   (the catalogue the POS sells from)
```

---

## The AI feature

`POST /orders/{order}/insight` builds a snapshot of the order — status, age,
totals, items, address completeness, activity timeline — and asks a model for
strict JSON:

```json
{
  "summary": "2–3 sentence recap of what happened on this order",
  "next_actions": ["…"],
  "missing_info": ["…"],
  "draft_reply": "customer-ready support message"
}
```

One call covers all four things an operator wants: **summarise the activity,
suggest next actions, detect incomplete information, and draft a support
response.**

**Three providers, one contract.** `InsightProvider` declares `generate()` and
`models()`; `ClaudeProvider`, `OpenAiProvider` and `GeminiProvider` each
implement it against their own JSON-schema-constrained API. Adding a fourth
means writing one class.

**Configurable from the app, not just the .env.** *Settings → AI assistant*
lets an operator pick the provider, paste an API key and choose a model.
**Refresh models** calls the provider's own catalogue endpoint and turns the
field into a dropdown of exactly what that account may use, so nobody types a
model identifier from memory. Keys are stored encrypted and never sent back to
the browser — the page only learns whether one exists. Saved settings win over
`.env`, which remains the fallback for a fresh install.

**It degrades rather than breaks.** No key, a timeout, a 500 or an unparseable
body all fall through to a deterministic brief built from the order's own state
(status-driven next actions, missing phone/email/address detection, a templated
reply), tagged `"source": "fallback"`. The result is cached on the order so
reopening it is free; a **Regenerate** button forces a fresh call.

### AI tooling used to build this

- **Claude Code** (Opus) wrote the application against a spec kept in
  `.claude/specs/`, drove the browser to verify each screen, and ran the test and
  static-analysis gates after every change.
- **Laravel Boost MCP** supplied version-correct Laravel/Inertia/Pest
  documentation and read-only database access for verifying seeded data.
- The shipped runtime feature calls the **Anthropic Messages API** by default,
  with OpenAI and Gemini as drop-in alternatives.

---

## Tests

85 Pest tests, feature-first:

| File | Covers |
| --- | --- |
| `OrderIndexTest` | Search across order number and customer, status filter, date range, invalid range rejected, summary and tab counts matching the filtered set. |
| `OrderShowTest` | Detail props, legal-transition list, no N+1. |
| `OrderStatusUpdateTest` | Legal move persists and writes an audit row with the right actor; illegal move returns 422 and changes nothing. |
| `OrderCreateTest` | POS sale for an existing customer, walk-in customer creation, catalogue pricing overriding a tampered request, flat vs free shipping, validation, guests blocked. |
| `GenerateOrderInsightTest` | Claude/OpenAI/Gemini responses parsed through the same contract, insight cached until refresh, provider outage and missing key both falling back. |
| `AiSettingsTest` | Settings never leak the stored key, saving encrypts it and leaves exactly one provider active, a blank key field keeps the existing one, the saved provider is the one that runs, and model refresh handles success, no-key and outage. |
| `CatalogueTest` | Product listing with category and sales, add/edit, duplicate SKU rejected, hidden products excluded from the POS, category CRUD, non-empty category delete refused. |
| `DashboardTest` | Headline metrics, trend length, status split, cancelled orders never counting as revenue. |
| `OrderStatusEnumTest` | The full transition matrix and terminal states. |

---

## Deploying

The app is a standard Laravel + Vite deployment; it has been verified against
Laravel Cloud and works the same on Forge or any PHP host:

1. Provision MySQL and set `DB_*`, plus `APP_KEY` (`php artisan key:generate`).
2. Build step: `composer install --no-dev -o && npm ci && npm run build`.
3. Release step: `php artisan migrate --seed --force`.
4. Optional: set `AI_PROVIDER` and the matching API key. Leave them unset and the
   insight feature still works via its deterministic fallback.

---

## Known limitations

- **No frontend test runner.** Coverage is server-side plus browser verification
  by hand; Pest 4 browser tests (Playwright) would be the next addition.
- **Single tenant, single role.** Every authenticated user is an operator —
  there are no policies or per-store scoping yet.
- **The AI response is not streamed**, so a real model call blocks for a few
  seconds behind a skeleton.
- **Orders cannot be edited after creation** — only their status moves. Line-item
  edits, partial refunds and returns are out of scope.
- **No stock or inventory tracking**; the POS will happily sell the same product
  forever.
- **Product images are URLs**, not uploads — there is no media library.
- Money is single-currency (USD) and tax is a flat rate, not a jurisdiction
  lookup.

## If it were going further

- Per-store scoping with policies, and roles (operator vs manager) for who may
  cancel or refund.
- Stream the AI insight token by token, and let the operator send the drafted
  reply straight from the panel.
- Inventory: stock levels, low-stock warnings on the dashboard, and the POS
  refusing to oversell.
- Bulk actions on the order table (advance several orders at once), CSV export,
  and saved filter views.
- Webhooks or a queued job pulling orders in from the storefront instead of the
  counter being the only source.
