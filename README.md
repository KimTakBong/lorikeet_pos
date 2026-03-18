# Lorikeet POS

Complete POS (Point of Sale) system for small businesses (UMKM). Built with Laravel 12 + Vue 3 SPA + TailwindCSS 4.

## Tech Stack

- **Backend:** Laravel 12, MySQL, Laravel Sanctum
- **Frontend:** Vue 3 (Composition API), Pinia, TailwindCSS 4, Vue Router
- **WhatsApp:** Node.js + whatsapp-web.js (self-hosted WA Engine)
- **PWA:** Service Worker, offline-ready, installable

---

## Features

### 💰 POS (Point of Sale)
- Fast product grid with instant search and category filter
- Tap-to-add to cart, tap again to increase quantity
- Bi-directional discount input (percent ↔ nominal, synced)
- Multiple payment methods: Cash, QRIS, Bank Transfer, E-Wallet, Debit/Credit Card
- Quick cash buttons: Exact, 50k, 100k
- Change calculation automatic
- Inline start shift (no navigation needed)
- Back to Dashboard button
- Optimistic UI: payment modal closes instantly, order sent in background

### 📦 Products
- CRUD with name, SKU, category, price, cost
- Category management
- Status toggle (Active/Inactive)
- Filter by category and status
- Price/cost history tracking (subquery, no N+1)
- Dark themed filter card with search

### 📊 Inventory
- Stock level overview with current stock per product
- Stock movements log (sale, restock, adjustment, waste, refund)
- Restock from suppliers with purchase orders
- Stock adjustment with reason
- Low stock filter
- Supplier management

### 🧾 Orders
- Order list with search, status filter, date range
- Order detail modal with items, payments, customer
- Refund functionality (with stock return)
- Manual "Send Receipt" button per order (WhatsApp)
- Status badges (completed, refunded)
- Action buttons: Detail, Refund, Receipt

### 👥 Customers
- Auto-create from phone number in POS
- Search by name, phone, email
- Customer profile with order history
- Customer tags (segmentation)
- Loyalty points (Rp 10,000 = 1 point)
- Membership tiers: Bronze → Silver → Gold → VIP

### 📱 WhatsApp Integration
- Auto-send receipt after POS payment (queued)
- Manual receipt send from Orders page
- WA Engine (self-hosted, port 3000)
- Queue-based: all messages queued, processed by scheduler
- Image receipt with store name from settings
- Campaign broadcast support

### 📈 Analytics
- Dashboard: today's sales, orders, top products, payment breakdown
- Daily aggregation via scheduled job
- Sales trend charts
- Top products ranking
- Customer insights

### 💸 Expenses
- CRUD with category
- Date range filter
- Category breakdown summary
- Expense categories management

### 📢 Campaigns
- Create marketing campaigns
- Segment selection: All, VIP, Inactive customers
- Add/remove recipients
- WhatsApp broadcast send
- Campaign result tracking

### 👨‍💼 Staff & Shifts
- Staff CRUD with roles: Owner, Manager, Cashier, Staff
- Shift management: Start/End with cash counting
- Auto-select current user in shift modals
- Active shift banner with real-time status
- Shift summary with cash reconciliation

### ⚙️ Settings
- Business settings: store name, address, phone, email
- Receipt preview
- WhatsApp connection status
- QR code scan for WA pairing
- Session logout/destroy

### 🎨 UI/UX
- Dark theme (permanent, no toggle)
- Slate color palette (modern, consistent)
- Gradient buttons with hover effects
- Pill-shaped status badges
- Loading overlay for DataTable
- Filter cards with header (Orders, Products)
- Toast notifications (SweetAlert2)
- Responsive: mobile, tablet, desktop
- PWA: installable, fullscreen mode

---

## Performance Optimizations

- **Bundle:** Lazy-loaded routes (552KB → 252KB main)
- **API:** Single dashboard endpoint (3 calls → 1)
- **Database:** 14+ indexes on frequently queried columns
- **Products:** JOIN subqueries for price/cost (no N+1)
- **Inventory:** JOIN subqueries for stock levels
- **Analytics:** Cached queries (2 min TTL)
- **Reference data:** Cached categories, suppliers, payment methods (1 hour)
- **WhatsApp:** Queue-only flow (no blocking sendNow)
- **POS:** Optimistic UI (instant payment confirmation)

---

## Installation

```bash
# 1. Install dependencies
composer install
npm install

# 2. Configure environment
cp .env.example .env
php artisan key:generate

# 3. Run migrations
php artisan migrate:fresh --seed

# 4. Build frontend
npm run build

# 5. Start services
php artisan serve              # Laravel (port 8000)
cd wa-engine && npm start      # WA Engine (port 3000)
php artisan schedule:work      # Scheduler (WhatsApp queue)
```

### Default Login
- Email: `admin@example.com`
- Password: `password`

---

## API Routes (58 endpoints)

| Module | Endpoints |
|--------|-----------|
| Auth | login, logout, user |
| Dashboard | dashboard/stats |
| Products | CRUD + categories |
| Orders | list, detail, refund, send-receipt |
| Customers | CRUD + search + stats |
| POS | create order, customers, payment methods |
| Inventory | stock levels, movements, restock, adjust, suppliers |
| Expenses | CRUD + categories + summary |
| Analytics | dashboard, sales trend, top products, payment breakdown |
| Campaigns | CRUD + recipients + send |
| Staff | CRUD |
| Shifts | list, start, end, active, summary |
| Settings | get, update |

---

## Database (40+ tables)

```
Sales:      orders, order_items, payments, payment_methods, refunds, refund_items
Products:   products, product_categories, product_prices, product_costs
Inventory:  stock_movements, suppliers, purchases, purchase_items, supplier_price_history
Customers:  customers, customer_tags, customer_tag_map
Loyalty:    membership_tiers, customer_memberships, loyalty_transactions
Staff:      staff, shifts
Marketing:  campaigns, campaign_results, message_queue
Analytics:  daily_sales, daily_product_sales, daily_customer_stats
Finance:    expenses, expense_categories
```

---

## Architecture

```
app/
├── Console/Commands/        # Artisan commands
├── Http/Controllers/        # Global controllers
├── Models/                  # 32 Eloquent models
├── Modules/                 # 10 feature modules
│   ├── POS/                 # Order creation, payment
│   ├── Products/            # Product CRUD
│   ├── Orders/              # Order management, refund
│   ├── Customers/           # Customer CRM
│   ├── Inventory/           # Stock management
│   ├── Staff/               # Staff & shifts
│   ├── Expenses/            # Expense tracking
│   ├── Analytics/           # Reports & charts
│   ├── Campaigns/           # Marketing
│   └── Loyalty/             # Points & membership
├── Services/                # Shared services
└── Rules/                   # Validation rules

resources/js/
├── components/              # Reusable components
│   ├── POS/                 # CartPanel, ProductGrid, PaymentModal
│   ├── tables/              # DataTable (paginated, sortable)
│   ├── forms/               # FormModal, SearchSelect, CurrencyInput
│   ├── ui/                  # Button components
│   ├── layouts/             # AdminLayout, POSLayout
│   └── alerts/              # AlertService, ConfirmDelete
├── pages/                   # 11 page components
├── stores/                  # Pinia stores
├── router/                  # Vue Router
└── api/                     # Axios client

wa-engine/
├── server.js                # Express server
├── services/                # WhatsApp service
├── controllers/             # API controllers
└── routes/                  # API routes
```

---

## License

MIT
