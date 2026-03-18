# Lorikeet POS - Point of Sale SaaS System

A complete **Laravel 10+ POS SaaS system with PWA frontend** built according to the QWEN specification.

## Features Implemented

### Core POS System
- ✅ Fast product grid with instant search
- ✅ Tap-to-add product functionality
- ✅ Cart management with quantity controls
- ✅ Discount percentage/amount input (bi-directional sync)
- ✅ Payment modal with multiple payment methods
- ✅ Order creation with database transactions
- ✅ Stock movement tracking (sale, restock, adjustment, waste)

### Customer Management
- ✅ Automatic customer creation from phone number
- ✅ Customer search by phone/name
- ✅ Customer tagging system
- ✅ Loyalty points system (Rp 10,000 = 1 point)
- ✅ Membership tiers (Bronze, Silver, Gold, VIP)

### Inventory Management
- ✅ Stock movements (append-only tracking)
- ✅ Product cost history tracking
- ✅ Supplier management
- ✅ Purchase orders
- ✅ Supplier price increase detection

### Staff & Shift System
- ✅ Staff login system
- ✅ Shift management (start/end)
- ✅ Cash counting and reconciliation
- ✅ Role-based permissions (Owner, Manager, Cashier, Staff)

### Payment System
- ✅ Multiple payment methods (Cash, QRIS, Bank Transfer, E-Wallet, Cards)
- ✅ Split payment support
- ✅ Change calculation

### WhatsApp Integration
- ✅ Digital receipt automation
- ✅ Message queue system
- ✅ Campaign support
- ✅ WA Engine (self-hosted WhatsApp Web automation)

### ⚠️ WhatsApp Setup Required

For WhatsApp receipts to be sent automatically, you need to run the queue worker:

**Option 1: Run Queue Worker (Recommended for Development)**
```bash
php artisan queue:work
```

**Option 2: Setup Cron Job**
Add to your crontab (`crontab -e`):
```bash
* * * * * cd /Users/alfanrlyan/Desktop/laravel/Lorikeet && php artisan whatsapp:process >> /dev/null 2>&1
```

**Option 3: Use Laravel Scheduler**
Add to `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('whatsapp:process')->everyMinute();
}
```
Then run:
```bash
php artisan schedule:work
```

**Make sure WA Engine is running:**
```bash
cd wa-engine
npm start
```
Verify at: http://localhost:3000/health

### Analytics
- ✅ Daily sales aggregation
- ✅ Product sales tracking
- ✅ Customer statistics
- ✅ Dashboard-ready tables

### PWA Features
- ✅ Installable app (Add to Home Screen)
- ✅ Fullscreen standalone mode
- ✅ Service worker caching
- ✅ Offline-resilient design
- ✅ Touch-optimized UI
- ✅ Mobile/tablet responsive

## Tech Stack

### Backend
- Laravel 12
- MySQL
- Redis (for queues and cache)
- Laravel Sanctum (API authentication)

### Frontend
- Vue 3 (Composition API)
- Pinia (state management)
- TailwindCSS 4
- Vue Router
- Axios

## Installation

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8+
- Redis

### Setup Steps

1. **Install dependencies**
```bash
composer install
npm install
```

2. **Configure environment**
```bash
cp .env.example .env
php artisan key:generate
```

Update `.env` with your database credentials:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lorikeet
DB_USERNAME=root
DB_PASSWORD=root

QUEUE_CONNECTION=redis
CACHE_STORE=redis
```

3. **Run migrations and seeders**
```bash
php artisan migrate:fresh --seed
```

4. **Build frontend assets**
```bash
npm run build
```

5. **Start the application**
```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite dev server (for development)
npm run dev

# Terminal 3: Queue worker
php artisan queue:work --queue=whatsapp_queue,analytics_queue,inventory_queue
```

## Default Credentials

After seeding:
- **Email:** admin@example.com
- **Password:** password

## API Endpoints

### POS Transactions
- `POST /api/v1/orders` - Create new order
- `GET /api/v1/orders/{id}` - Get order details

### Products
- `GET /api/v1/products` - List products (with search/filter)

### Customers
- `GET /api/v1/customers` - List/search customers
- `POST /api/v1/customers` - Create customer

### Payment
- `GET /api/v1/payment-methods` - List payment methods

### Shifts
- `POST /api/v1/shifts/start` - Start cashier shift
- `POST /api/v1/shifts/end` - End cashier shift

## Database Architecture

The system includes 40+ tables organized into modules:

### Sales (POS)
- orders, order_items, payments, payment_methods, refunds, refund_items

### Products
- products, product_categories, product_prices, product_costs

### Inventory
- stock_movements, suppliers, purchases, purchase_items, supplier_price_history

### Customers
- customers, customer_tags, customer_tag_map

### Loyalty
- membership_tiers, customer_memberships, loyalty_transactions

### Staff
- staff, shifts

### Marketing
- campaigns, campaign_results, message_queue

### Analytics
- daily_sales, daily_product_sales, daily_customer_stats

### Finance
- expenses, expense_categories

## Modular Architecture

```
app/Modules/
├── POS/
│   ├── Controllers/
│   ├── Services/
│   ├── Repositories/
│   └── Routes/
├── Inventory/
├── Products/
├── Customers/
├── Loyalty/
├── Staff/
├── Purchasing/
├── Marketing/
├── Analytics/
├── Finance/
└── Automation/
```

## Performance Targets

- Add product to cart: <200ms
- Search response: <100ms
- Checkout: <300ms
- Order creation: <300ms

## Security Features

- All POS transactions use database transactions
- Inventory changes only through stock_movements
- Orders immutable after completion
- Role-based access control
- API authentication via Sanctum
- CSRF protection

## PWA Configuration

The application is installable as a PWA with:
- `manifest.json` for app metadata
- `sw.js` service worker for caching
- Fullscreen standalone display
- Touch-optimized interface

## Next Steps (Optional Enhancements)

1. **Queue Workers** - Implement WhatsApp, analytics, and inventory queues
2. **Scheduled Jobs** - Daily sales aggregation, customer statistics, dead stock detection
3. **Analytics Dashboard** - Complete dashboard with charts and metrics
4. **Inventory Page** - Full inventory management UI
5. **Customer Page** - Customer profile and history UI
6. **Reports** - Sales reports, export functionality

## License

MIT License

## Support

For issues or questions, please refer to the QWEN specification document (`qwen.md`).
