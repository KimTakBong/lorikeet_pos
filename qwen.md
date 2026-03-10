# QWEN.md

# Project Vision

Build a **Business Operating System for Small Businesses (UMKM)**.

The platform combines multiple essential business tools into one system:

* POS (Point of Sale)
* Inventory & Restock Tracking
* Supplier Management
* Expense Tracking
* Customer CRM
* Loyalty & Membership
* WhatsApp Automation
* Marketing Campaigns
* Business Analytics
* Staff Management
* Central Data Sync

Each business runs its own **single‑tenant Laravel application** while optionally syncing data to a **central analytics server**.

---

# Core Architecture

Client Application Stack:

* Laravel (main system)
* MySQL / PostgreSQL
* Queue Worker
* Cron Jobs
* WhatsApp Bot Service

Deployment options:

* Railway
* Render
* Fly.io
* VPS

Landing page can be deployed separately (ex: Vercel).

---

# User Roles

Roles inside the system:

Owner
Manager
Cashier
Staff

Permissions control:

* POS access
* Reports
* Campaign sending
* Inventory control
* Refund authorization

---

# Staff Login & Shift System

Cashiers must login before starting transactions.

Flow:

Login → Start Shift → POS Transactions → End Shift → Cash Count

## Shift Summary

System calculates:

* total sales
* payment breakdown
* expected cash
* actual cash
* difference

Example:

Sales: Rp 2.100.000
Cash: Rp 1.300.000
QRIS: Rp 800.000

Cash Difference: -20.000

Purpose:

* prevent fraud
* monitor cashier performance

---

# POS (Point of Sale)

Primary interface used by cashiers.

## Features

* product search
* quick add to cart
* quantity control
* customer phone input
* multiple payment methods
* order completion

## POS Flow

Select Product → Add to Cart → Select Customer → Choose Payment → Create Order

After order completion:

* stock updated
* customer stats updated
* WhatsApp receipt sent

---

# Payment Methods

System supports multiple payment methods.

Examples:

* Cash
* QRIS
* Bank Transfer
* E‑Wallet
* Debit Card
* Credit Card

## Table: payment_methods

Fields:

id
name
is_active

Orders store:

payment_method_id

---

# Products

## Table: products

Fields:

id
name
sku
price
cost_price
stock
category_id
created_at

Stock automatically decreases when orders are created.

---

# Inventory Management

System manages stock and restocking.

## Stock Deduction

When product sold:

product.stock -= quantity

## Stock Opname

Manual inventory correction.

## Table: stock_adjustments

Fields:

id
product_id
adjustment
reason
created_at

---

# Supplier Management

## Table: suppliers

id
name
phone
address
notes
created_at

---

# Restock / Purchasing

Businesses record product restocking.

Flow:

Create Purchase Order → Add Purchase Items → Update Stock

## Table: purchase_orders

id
supplier_id
invoice_number
total_cost
purchase_date
created_at

## Table: purchase_items

id
purchase_order_id
product_id
quantity
cost_price
subtotal

---

# Cost Price History

Track product cost changes over time.

## Table: product_cost_history

id
product_id
cost_price
purchase_item_id
recorded_at

Example:

Jan: 70.000
Feb: 75.000
Mar: 82.000

---

# Supplier Price Increase Detection

System detects when supplier cost increases.

Example:

Coffee Beans

Feb: 75.000
Mar: 82.000

Increase: +7.000

Alerts displayed on dashboard.

---

# Orders

## Table: orders

id
order_number
customer_id
total_amount
payment_method_id
cashier_id
status
receipt_sent
receipt_sent_at
created_at

## Table: order_items

id
order_id
product_id
price
quantity
subtotal

---

# Refund / Void Transaction

System allows order cancellation or refund.

## Table: order_refunds

id
order_id
processed_by
reason
created_at

Refunds are logged for auditing.

---

# Customer CRM

Customers are created automatically when a phone number is entered in POS.

## Table: customers

id
name
phone
email
total_spent
total_orders
last_purchase_at
membership_tier
points
marketing_consent
created_at

---

# Customer Tagging

Owners can tag customers.

Examples:

VIP
Regular
Wholesale
Coffee Lover

Tags enable marketing segmentation.

---

# Loyalty & Membership

Customers earn points from purchases.

Example rule:

Rp 10.000 = 1 point

## Membership Tiers

Bronze
Silver
Gold
VIP

Example thresholds:

Bronze: 0 – 500k
Silver: 500k – 2m
Gold: 2m – 5m
VIP: 5m+

## Table: loyalty_transactions

id
customer_id
order_id
points
type
created_at

Types:

earn
redeem
adjustment

---

# Loyalty Settings Page

Business owners configure:

* point rules
* reward rules
* membership tiers

Example:

200 points = free coffee

---

# WhatsApp Integration

WhatsApp bot enables communication with customers.

Capabilities:

* send digital receipts
* send campaigns
* send daily reports

Bot implementation options:

* Baileys (Node.js)
* WhatsApp API provider

---

# WhatsApp Digital Receipt

Receipts are automatically sent after transactions.

Flow:

Order Completed → Generate Receipt → Queue Job → Send WhatsApp

Example message:

TOKO KOPI NUSANTARA

Invoice: INV‑10231

Latte x2
50.000

Croissant x1
25.000

TOTAL
75.000

Thank you for your purchase.

---

# Marketing Campaigns

Owners can send WhatsApp promotions.

Campaign creation flow:

Create Campaign → Select Segment → Preview Recipients → Send

Segments may include:

* All customers
* VIP customers
* Inactive customers
* Tagged customers

---

# Campaign Performance Tracking

System measures campaign effectiveness.

Metrics:

Recipients
Customers who purchased
Revenue generated

Example:

Recipients: 120
Customers purchased: 18
Revenue: Rp 850.000

---

# Follow‑Up Recommendations

System suggests customers to follow up.

Example triggers:

* customer inactive for 14 days
* high value customer

Displayed in dashboard with manual send option.

---

# Sales Insights

Analytics generated from sales data.

Examples:

Top Product Today
Top Product This Week
Slow Moving Products

Example:

Top Product: Latte
Sold: 48
Revenue: Rp 1.200.000

---

# Dead Stock Warning

Detect products not sold for long periods.

Example:

Croissant
Last sold: 12 days ago

Owners may create promotions or discounts.

---

# Customer Purchase Analytics

System analyzes buying behavior.

Examples:

Most purchased products
Frequent customers
Customer favorite categories

---

# Staff Performance

Track staff sales activity.

Example dashboard:

Andi
Orders: 43
Revenue: Rp 2.100.000

Sari
Orders: 28
Revenue: Rp 1.200.000

---

# Expense Tracking

Business operational expenses.

## Table: expenses

id
title
category
amount
expense_date
notes
created_at

Categories:

rent
salary
utilities
supplies
inventory_purchase

---

# Profit Dashboard

System calculates real profit.

Metrics:

Sales
Cost of Goods Sold
Expenses
Profit

Example:

Sales: Rp 2.100.000
COGS: Rp 900.000
Expenses: Rp 400.000

Profit: Rp 800.000

---

# Daily Business Report via WhatsApp

Owners receive automated reports.

Schedule example:

22:00 every day

Message example:

Sales: Rp 2.100.000
Orders: 43
Top Product: Latte
New Customers: 6
Profit: Rp 800.000

Feature can be enabled or disabled.

---

# Data Sync to Central Server

Client apps periodically sync data.

Sync interval:

Every 5–10 minutes

Example command:

php artisan sync:central

Data synced:

* customers
* orders
* products
* expenses
* campaigns

Records include a synced_at field.

Only unsynced records are transmitted.

---

# Scalability Strategy

Key practices:

Database indexing for:

phone
customer_id
product_id
created_at

Queues used for:

WhatsApp messages
campaign broadcasts
receipt sending
report generation

---

# Product Vision

The system evolves into a **complete operating system for small businesses**.

Core pillars:

Sales (POS)
Inventory
Customer CRM
Marketing
Analytics
Finance

Goal:

Help small businesses:

* increase sales
* retain customers
* understand their business data
* automate marketing

---

## Mobile Strategy (PWA)

The system will be delivered as a **Progressive Web App (PWA)** so merchants can install it on phones and tablets without needing Play Store / App Store distribution.

### Goals

* Single codebase (Web + Mobile)
* Fast deployment and updates
* Installable like a native app
* Optimized for POS usage on phones and tablets

### PWA Requirements

#### 1. Fullscreen App Mode

The application must support **standalone fullscreen mode** when installed.

Requirements:

* Use Web App Manifest (`display: standalone`)
* Hide browser UI when launched from home screen
* POS screen must feel like a native application

Example behavior:

* No browser address bar
* Fullscreen interface
* Dedicated POS layout

#### 2. Mobile & Tablet Optimized UI

The UI must be responsive and optimized for:

* Smartphones
* Tablets
* Touchscreen POS terminals

Guidelines:

* Large tap targets
* Responsive product grid
* Fast product search
* Minimal scrolling for cashier workflow

Layouts to support:

* Mobile portrait
* Mobile landscape
* Tablet portrait
* Tablet landscape

#### 3. Touch Optimized Interface

The POS interface must be fully **touch optimized**.

Requirements:

* Large product buttons
* One‑tap product add to cart
* Swipe gestures where appropriate
* Numeric keypad optimized for touch
* Quick quantity adjustment

Target interaction time:

* Adding product to cart: < 200ms

#### 4. Installable App

Users should be able to install the system from the browser:

"Add to Home Screen"

Once installed:

* Opens as standalone app
* Launch icon on device
* Works like a native POS application

#### 5. Performance Targets

POS performance requirements:

* Initial load < 2s
* Product search < 300ms
* Add item to cart < 200ms

Caching strategy:

* Service Worker
* Static asset caching
* Product list caching

#### 6. Offline‑Resilient Design (Recommended)

To avoid lost sales during unstable internet:

* Orders temporarily cached locally
* Auto sync when connection returns

Technologies:

* Service Worker
* IndexedDB / Local Storage
* Background sync

This ensures the POS can continue operating even when internet connectivity is unstable.

---

---

## Database Architecture

The system must use a **modular relational database architecture** designed to support POS, inventory, CRM, marketing, and analytics.

Estimated size: **~50+ tables**.

Design principles:

* Append‑only transactional records
* Historical data must never be overwritten
* Stock must be tracked using movement logs
* Orders become immutable after completion

### Core Sales (POS)

Tables:

orders
order_items
payments
payment_methods
order_discounts
order_taxes
refunds
refund_items

orders

id
invoice_number
customer_id
staff_id
shift_id

subtotal
discount_total
tax_total
grand_total

payment_status
order_status

created_at

order_items

id
order_id
product_id
product_name

price
cost
quantity
total

created_at

Note:

Product name and price are stored to preserve **historical accuracy** even if products are edited later.

### Product System

Tables:

products
product_categories
product_variants
product_prices
product_costs
product_bundles
bundle_items

products

id
name
sku
category_id
is_active
created_at

product_prices

id
product_id
price
effective_from

This allows historical price tracking.

### Inventory System

Inventory must use **movement-based tracking** instead of a single stock column.

Tables:

stocks
stock_movements
stock_adjustments
stock_opnames

stock_movements

id
product_id

type
(restock | sale | adjustment | waste)

quantity
reference_id
reference_type

created_at

Example references:

sale → order_id
restock → purchase_id

This ensures full audit capability.

### Purchasing / Supplier

Tables:

suppliers
purchases
purchase_items
supplier_price_history

purchases

id
supplier_id
invoice_number
total_cost
created_at

purchase_items

id
purchase_id
product_id
quantity
cost_price
total

supplier_price_history

id
product_id
supplier_id
price
recorded_at

Used for **supplier price increase detection**.

### Customer CRM

Tables:

customers
customer_tags
customer_tag_map
customer_points
customer_transactions

customers

id
name
phone
email
birthday
created_at

customer_points

id
customer_id
points
source
order_id
created_at

### Loyalty & Membership

Tables:

membership_tiers
customer_memberships
points_history

membership_tiers

id
name
min_spend
points_multiplier

### Staff / Cashier System

Tables:

staff
staff_roles
staff_permissions
shifts
shift_transactions

shifts

id
staff_id
start_time
end_time

opening_cash
closing_cash
expected_cash
cash_difference

### Payment System

Tables:

payment_methods
payments

payments

id
order_id
payment_method_id
amount
paid_at

Supports **split payments**.

### Expense Tracking

Tables:

expenses
expense_categories

expenses

id
category_id
amount
description
expense_date

### Marketing / Campaign

Tables:

campaigns
campaign_messages
campaign_targets
campaign_results

campaigns

id
name
type
start_date
end_date

campaign_results

id
campaign_id
customer_id
order_id
revenue

Used for **promo performance tracking**.

### Analytics Helper Tables

To optimize dashboards:

daily_sales
daily_product_sales
daily_customer_stats

These tables store **pre-aggregated metrics** for fast analytics queries.

### WhatsApp Automation

Tables:

whatsapp_logs
message_queue
automation_rules

message_queue

id
customer_id
phone
message
status
scheduled_at
sent_at

Used by the queue worker for reliable message delivery.

### Recommended Laravel Modular Structure

Modules/

POS
Inventory
Products
Customers
Loyalty
Staff
Purchasing
Marketing
Analytics
Finance
Automation

This modular structure ensures long‑term scalability of the system.

---

## POS UX Flow (High Speed Cashier Design)

The POS interface must be optimized so a cashier can complete most transactions in **≤3 primary actions** and typically **≤5 seconds**.

### Core Principles

* Minimal clicks / taps
* No page reloads
* Touch‑optimized interface
* Instant feedback (<200ms)
* Single screen workflow

### Main POS Layout

The POS screen should be divided into three functional areas.

PRODUCT GRID (left)
Displays products as large touchable buttons.

CART PANEL (right)
Displays selected items, quantities, and totals.

ACTION BAR (bottom)
Contains:

Search
Customer
Discount
Pay button

### Product Selection Flow

Typical transaction flow:

1. Tap product
2. Tap additional product (optional)
3. Tap "Pay"
4. Confirm payment

Example transaction:

Tap Latte
Tap Croissant
Tap Pay
Tap Confirm

Total interactions: **4 taps**.

### Product Grid Requirements

Products must be displayed as large touch‑friendly cards.

Each card shows:

Product name
Price
Color category (optional)

Tapping a product:

Adds it directly to the cart.

Tapping again:

Increases quantity.

No popup dialogs should appear during this action.

### Fast Product Search

Search must return results instantly.

Requirements:

Search latency <100ms
Supports partial keyword matching
Keyboard and touchscreen friendly

Example:

Typing "lat" instantly shows "Latte".

### Cart Interaction

Cart items can be tapped to open quick actions.

Available actions:

Increase quantity
Decrease quantity
Remove item
Apply item discount

### Quick Payment Flow

Pressing "Pay" opens a payment modal.

The modal shows:

Total amount
Payment methods
Cash input field
Change calculation

Supported quick buttons for cash payment:

Exact payment
50k
100k
200k

System automatically calculates change.

### Customer Selection (Optional)

Cashier can optionally attach a customer.

Customer lookup supports:

Phone number
Customer name

Transactions can still proceed without a customer.

### Hold / Park Order

The POS must support **holding temporary orders**.

Use cases:

Cafe customers ordering multiple items
Customers adding more items later

Cashier action:

Press "Hold"

System saves order as temporary.

Example hold IDs:

H12
H13
H14

Cashier can later resume the order.

### Resume Held Orders

Cashier selects "Hold Orders".

List of pending orders appears.

Selecting one restores the cart.

### Performance Targets

Add product to cart: <200ms
Search response: <100ms
Checkout open: <300ms

No page reloads are allowed.

All POS interactions must operate in a **single page application environment**.

---

## POS Discount Input System

Each transaction must support **instant manual discount entry** at the POS level to support in-store promotions.

### UI Behavior

The POS checkout screen must provide **two synchronized input fields**:

1. Discount Percentage (%)
2. Discount Nominal (Currency)

Both inputs must be **bi-directionally synchronized using JavaScript**.

### Example Scenario

Order Total:

100,000

Cashier enters:

15%

System automatically updates:

Discount Nominal → 15,000

If cashier instead enters:

15,000

System automatically updates:

Discount Percentage → 15%

### UI Logic

When `discount_percent` changes:

```
discount_nominal = subtotal * (discount_percent / 100)
```

When `discount_nominal` changes:

```
discount_percent = discount_nominal / subtotal * 100
```

### Validation Rules

* Discount cannot exceed subtotal
* Maximum discount percent configurable
* Values automatically rounded to currency precision

### Database Storage

Discount values are stored in the order record:

```
orders

subtotal
discount_total
discount_percent
```

This ensures:

* consistent reporting
* accurate promotion analysis

### Purpose

This allows cashiers to quickly apply:

* in-store promotions
* manager authorized discounts
* price adjustments

without needing a predefined promo configuration.

---

## API Contract Layer (AI Generation Safe Specification)

This section defines strict API contracts so AI code generators can reliably build backend and frontend without ambiguity.

All APIs follow REST conventions.

Base URL

/api/v1

Response format

{
"success": true,
"data": {},
"message": "optional"
}

Error format

{
"success": false,
"error": "ERROR_CODE",
"message": "Human readable message"
}

Authentication

Bearer Token (Laravel Sanctum)

Authorization header:

Authorization: Bearer {token}

---

## POS Transaction APIs

### Create Order

POST /api/v1/orders

Request

{
"customer_id": "optional",
"staff_id": "required",
"shift_id": "required",
"items": [
{
"product_id": 1,
"quantity": 2,
"price": 25000
}
],
"discount_percent": 0,
"discount_amount": 0,
"tax_amount": 0,
"payments": [
{
"payment_method_id": 1,
"amount": 50000
}
]
}

Response

{
"success": true,
"data": {
"order_id": 10231,
"invoice_number": "INV-10231",
"grand_total": 50000
}
}

---

### Get Order

GET /api/v1/orders/{id}

Returns full order including items and payments.

---

### Refund Order

POST /api/v1/orders/{id}/refund

Request

{
"reason": "Wrong order",
"items": [
{
"order_item_id": 12,
"quantity": 1
}
]
}

---

## Product APIs

### List Products

GET /api/v1/products

Query params

search=
category_id=
page=

Response returns product list optimized for POS grid.

---

### Create Product

POST /api/v1/products

{
"name": "Latte",
"sku": "LATTE01",
"category_id": 1,
"price": 25000,
"cost": 12000
}

---

## Inventory APIs

### Restock Product

POST /api/v1/inventory/restock

{
"supplier_id": 1,
"items": [
{
"product_id": 1,
"quantity": 20,
"cost_price": 12000
}
]
}

Creates purchase record and stock movement.

---

### Stock Movements

GET /api/v1/inventory/movements

Filters:

product_id
start_date
end_date

---

## Customer APIs

### Search Customers

GET /api/v1/customers

Query:

phone=
name=

---

### Create Customer

POST /api/v1/customers

{
"name": "John",
"phone": "08123456789",
"email": "optional"
}

---

## Loyalty APIs

### Customer Points

GET /api/v1/customers/{id}/points

Returns current loyalty points.

---

## Staff & Shift APIs

### Start Shift

POST /api/v1/shifts/start

{
"staff_id": 1,
"opening_cash": 500000
}

---

### End Shift

POST /api/v1/shifts/end

{
"shift_id": 12,
"closing_cash": 1200000
}

System calculates expected cash and difference.

---

## Campaign APIs

### Create Campaign

POST /api/v1/campaigns

{
"name": "Coffee Promo",
"type": "whatsapp",
"start_date": "2026-01-01",
"end_date": "2026-01-10"
}

---

## Analytics APIs

### Dashboard Summary

GET /api/v1/analytics/dashboard

Returns

* today_sales
* monthly_sales
* top_products
* payment_breakdown

---

## POS Performance Requirements

To ensure reliable POS operation:

Order creation must complete in <300ms.

Inventory deduction must be atomic using database transactions.

Example logic

BEGIN TRANSACTION

Insert order
Insert order_items
Insert payments
Insert stock_movements

COMMIT

If any step fails → ROLLBACK.

---

## AI Generation Constraints

To reduce errors during AI code generation:

1. Backend must be Laravel 10+.
2. API responses must always follow the defined response schema.
3. All POS operations must use database transactions.
4. Inventory must only change through stock_movements.
5. Orders cannot be edited after payment is completed.
6. All timestamps must use UTC.
7. All monetary values stored as integers (cents) to avoid floating point errors.

---

---

## QWEN GENERATION MODE (Full Application Build Instructions)

This section is designed so an AI coding agent (such as Qwen) can generate the entire application with minimal ambiguity.

The system must be implemented as a **Laravel + PWA POS SaaS platform**.

Primary stack:

Backend: Laravel 10+
Frontend: Vue 3
State management: Pinia
UI: TailwindCSS
Auth: Laravel Sanctum
Queue: Redis
Database: MySQL
PWA: Service Worker + Web App Manifest

The frontend must operate as a **Single Page Application (SPA)**.

---

## Project Folder Structure

The Laravel project must follow a modular architecture.

app/

Modules/

POS/
Inventory/
Products/
Customers/
Loyalty/
Staff/
Purchasing/
Marketing/
Analytics/
Finance/
Automation/

Each module must contain:

Controllers
Services
Repositories
Models
Routes
DTOs
Policies

Example module structure:

Modules/POS/

Controllers/
Services/
Repositories/
Models/
Routes/
DTOs/
Policies/

---

## Frontend Component Architecture

Frontend must use Vue 3 with a clear component hierarchy.

src/

components/

POS/
ProductGrid.vue
CartPanel.vue
PaymentModal.vue
DiscountModal.vue
CustomerSelector.vue
HoldOrdersModal.vue

Inventory/
StockTable.vue
RestockForm.vue

Customers/
CustomerSearch.vue
CustomerProfile.vue

Dashboard/
SalesChart.vue
TopProducts.vue
PaymentBreakdown.vue

Pages/

POSPage.vue
InventoryPage.vue
CustomersPage.vue
AnalyticsPage.vue

POSPage.vue must orchestrate the entire POS interaction.

---

## POS State Management

Pinia stores required:

useCartStore
useProductStore
useCustomerStore
usePaymentStore

Cart store must manage:

items
subtotal
discountPercent
discountAmount
tax
grandTotal

All calculations must be reactive.

---

## Queue Workers

Queue system required for background tasks.

Queues:

whatsapp_queue
analytics_queue
inventory_queue

Workers must process:

WhatsApp message sending
Campaign broadcasting
Daily analytics aggregation
Inventory alerts

---

## Scheduled Jobs (Cron)

Laravel scheduler must run the following jobs.

Daily sales aggregation
Customer statistics aggregation
Dead stock detection
Supplier price change alerts
Send daily WhatsApp business report

Example schedule:

Every 5 minutes → process message queue
Daily midnight → aggregate analytics

---

## Deployment Architecture

Recommended infrastructure:

Application server

Nginx
PHP-FPM
Laravel

Database

MySQL

Cache & Queue

Redis

Storage

S3 compatible storage (optional)

Basic architecture:

Client (PWA)

↓

Nginx

↓

Laravel API

↓

MySQL

↓

Redis Queue

---

## Performance Targets

POS interaction latency: <200ms
Order creation: <300ms
Search response: <100ms

System must support:

10 concurrent cashier sessions
without stock inconsistency.

All inventory deductions must use database transactions.

---

## Security Requirements

Authentication via Laravel Sanctum.

Role-based access control required.

Roles:

Owner
Manager
Cashier

Permissions must control:

Refund operations
Inventory adjustments
Campaign management
Analytics access

---

## Code Generation Rules

To reduce AI generation errors:

1. Follow Laravel naming conventions.
2. Use repository pattern for data access.
3. Use service layer for business logic.
4. Never place business logic in controllers.
5. All financial calculations must use integers.
6. Always wrap POS transactions in database transactions.
7. Inventory updates must only occur through stock_movements.

---

## Expected Output From AI Code Generator

When generating the system, AI should produce:

* Laravel migrations
* Eloquent models
* API controllers
* Service classes
* Repository classes
* Vue components
* Pinia stores
* API client layer
* Queue jobs
* Scheduled tasks

The result should be a **fully functional POS SaaS application** with PWA support.

---

---

## QWEN DATABASE MASTER SPEC (Production POS Schema)

This section defines the **authoritative database schema** so AI code generators can safely create Laravel migrations without ambiguity.

General rules:

* Primary keys: `id BIGINT UNSIGNED AUTO_INCREMENT`
* All tables must include `created_at` and `updated_at`
* Monetary values stored as **BIGINT (integer cents)**
* Foreign keys must use indexed columns
* Use `softDeletes()` where records should be recoverable

---

### customers

id BIGINT PK
name VARCHAR(150)
phone VARCHAR(30) INDEX
email VARCHAR(150) NULL
birthday DATE NULL
created_at
updated_at

---

### customer_tags

id BIGINT PK
name VARCHAR(100)
created_at
updated_at

---

### customer_tag_map

id BIGINT PK
customer_id BIGINT FK
customer_tag_id BIGINT FK
created_at
updated_at

---

### products

id BIGINT PK
name VARCHAR(150)
sku VARCHAR(80) UNIQUE
category_id BIGINT INDEX
is_active BOOLEAN DEFAULT TRUE
created_at
updated_at

---

### product_categories

id BIGINT PK
name VARCHAR(120)
created_at
updated_at

---

### product_prices

id BIGINT PK
product_id BIGINT FK
price BIGINT
effective_from DATETIME
created_at
updated_at

---

### product_costs

id BIGINT PK
product_id BIGINT FK
cost BIGINT
effective_from DATETIME
created_at
updated_at

---

### orders

id BIGINT PK
invoice_number VARCHAR(60) UNIQUE
customer_id BIGINT NULL
staff_id BIGINT
shift_id BIGINT
subtotal BIGINT
discount_total BIGINT
discount_percent INT

tax_total BIGINT
grand_total BIGINT
payment_status VARCHAR(50)
order_status VARCHAR(50)
created_at
updated_at

INDEX(customer_id)
INDEX(staff_id)

---

### order_items

id BIGINT PK
order_id BIGINT FK
product_id BIGINT
product_name VARCHAR(150)
price BIGINT
cost BIGINT
quantity INT
total BIGINT
created_at
updated_at

INDEX(order_id)

---

### payments

id BIGINT PK
order_id BIGINT FK
payment_method_id BIGINT
amount BIGINT
paid_at DATETIME
created_at
updated_at

---

### payment_methods

id BIGINT PK
name VARCHAR(100)
is_active BOOLEAN
created_at
updated_at

---

### refunds

id BIGINT PK
order_id BIGINT
staff_id BIGINT
reason TEXT
created_at
updated_at

---

### refund_items

id BIGINT PK
refund_id BIGINT
order_item_id BIGINT
quantity INT
amount BIGINT
created_at
updated_at

---

### stock_movements

id BIGINT PK
product_id BIGINT
movement_type VARCHAR(50)

(reference values: sale, restock, adjustment, waste)

quantity INT
reference_id BIGINT NULL
reference_type VARCHAR(100) NULL
created_at
updated_at

INDEX(product_id)

---

### suppliers

id BIGINT PK
name VARCHAR(150)
phone VARCHAR(50)
created_at
updated_at

---

### purchases

id BIGINT PK
supplier_id BIGINT
invoice_number VARCHAR(100)
total_cost BIGINT
created_at
updated_at

---

### purchase_items

id BIGINT PK
purchase_id BIGINT
product_id BIGINT
quantity INT
cost_price BIGINT
total BIGINT
created_at
updated_at

---

### supplier_price_history

id BIGINT PK
supplier_id BIGINT
product_id BIGINT
price BIGINT
recorded_at DATETIME
created_at
updated_at

---

### staff

id BIGINT PK
name VARCHAR(150)
email VARCHAR(150)
password VARCHAR(255)
role VARCHAR(50)
is_active BOOLEAN
created_at
updated_at

---

### shifts

id BIGINT PK
staff_id BIGINT
start_time DATETIME
end_time DATETIME NULL
opening_cash BIGINT
closing_cash BIGINT NULL
expected_cash BIGINT NULL
cash_difference BIGINT NULL
created_at
updated_at

---

### expenses

id BIGINT PK
category_id BIGINT
amount BIGINT
description TEXT
expense_date DATE
created_at
updated_at

---

### expense_categories

id BIGINT PK
name VARCHAR(120)
created_at
updated_at

---

### campaigns

id BIGINT PK
name VARCHAR(150)
type VARCHAR(50)
start_date DATE
end_date DATE
created_at
updated_at

---

### campaign_results

id BIGINT PK
campaign_id BIGINT
customer_id BIGINT
order_id BIGINT
revenue BIGINT
created_at
updated_at

---

### message_queue

id BIGINT PK
customer_id BIGINT
phone VARCHAR(50)
message TEXT
status VARCHAR(40)
scheduled_at DATETIME
sent_at DATETIME NULL
created_at
updated_at

---

### analytics tables

Pre-aggregated tables for fast dashboard queries.

#### daily_sales

id BIGINT PK
date DATE
revenue BIGINT
orders_count INT
created_at
updated_at

#### daily_product_sales

id BIGINT PK
product_id BIGINT
sale_date DATE
quantity INT
revenue BIGINT
created_at
updated_at

#### daily_customer_stats

id BIGINT PK
customer_id BIGINT
stat_date DATE
orders_count INT
revenue BIGINT
created_at
updated_at

---

---

## UI NAVIGATION & PAGE STRUCTURE SPEC

This section defines the **complete frontend page architecture** so AI generators understand routing, layouts, and navigation behavior.

The application operates in **two interface modes**:

1. Admin Dashboard Mode
2. POS Fullscreen Mode

---

### Front Page

Route:

/

The front page must NOT contain marketing content.

It must automatically redirect to:

/login

---

### Authentication Pages

/login

Optional:

/register

After successful login, redirect user to:

/dashboard

---

## ADMIN DASHBOARD LAYOUT

All admin pages must use a **Dashboard Layout**.

Layout structure:

Top Navbar
Left Sidebar
Main Content Area

Sidebar contains the main navigation menu.

---

### Sidebar Menu

Dashboard
POS
Orders
Products
Inventory
Purchasing
Customers
Campaigns
Expenses
Analytics
Staff
Settings
Logout

---

### Dashboard Page

Route:

/dashboard

Dashboard widgets must include:

Today's sales
Total orders
Top selling products
Low stock alerts
Recent transactions

---

### POS Page

Route:

/pos

IMPORTANT:

The POS page must run in **FULLSCREEN MODE without sidebar**.

Reason: cashier interface must remain distraction free.

Layout structure:

Left side: Product Grid
Right side: Cart Panel
Bottom: Checkout Actions

The POS page must load instantly and operate as a SPA view.

---

### Products Page

Route:

/products

Functions:

Create product
Edit product
Delete product
Assign category
Set price
Set cost

---

### Inventory Page

Route:

/inventory

Functions:

View stock levels
Restock products
Stock movement history
Stock adjustments

---

### Orders Page

Route:

/orders

Functions:

View transactions
Search orders
Filter by date
Refund order

---

### Customers Page

Route:

/customers

Functions:

Search customers
View purchase history
View customer lifetime value
Add or edit customer

---

### Campaigns Page

Route:

/campaigns

Functions:

Create marketing campaigns
Send WhatsApp broadcast
Track campaign results

---

### Expenses Page

Route:

/expenses

Functions:

Record expenses
Categorize expenses
View expense history

---

### Analytics Page

Route:

/analytics

Functions:

Sales charts
Revenue analytics
Top products
Customer insights

---

### Staff Page

Route:

/staff

Functions:

Create cashier accounts
Manage roles
View staff activity

---

### Settings Page

Route:

/settings

Functions:

Business profile
Tax configuration
Payment methods
Receipt settings

---

## FRONTEND ROUTER STRUCTURE

Vue Router must implement the following routes:

/login
/dashboard
/pos
/products
/inventory
/orders
/customers
/campaigns
/expenses
/analytics
/staff
/settings

All routes except `/pos` must use **AdminLayout**.

---

## LAYOUT COMPONENTS

Admin layout component:

components/layouts/AdminLayout.vue

Structure:

Sidebar
Navbar
RouterView

POS layout component:

components/layouts/POSLayout.vue

Structure:

ProductGrid
CartPanel
ActionBar

---

## MOBILE UI REQUIREMENTS

Dashboard layout must support responsive sidebar.

On mobile:

Sidebar collapses into a drawer.

POS interface must be optimized for:

Touch interaction
Tablet devices
Large buttons
Fast tap response

---

## DESIGN SYSTEM (UI CONSISTENCY RULES)

To maintain UI consistency across the system:

Primary color: Indigo or Blue tone

Buttons:

Primary button
Secondary button
Danger button

Cards must use rounded corners and soft shadows.

Tables must support:

Sorting
Pagination
Search

Spacing must follow Tailwind spacing scale.

Icons should use a consistent icon library such as Heroicons or Lucide.

All UI must remain **clean, minimal, and optimized for business dashboards**.

---

End of QWEN SUPER SPECIFICATION

---

# GLOBAL UI/UX & INTERACTION RULES (MANDATORY)

These rules apply to **ALL modules and ALL pages** in the system.

## Data Tables

All list views must use **advanced datatables with lazy pagination (server-side pagination)**.

Requirements:

* Server-side pagination
* Lazy loading data from API
* Search
* Column sorting
* Page size selector
* Loading indicator

Tables that MUST follow this rule:

* Products
* Inventory
* Orders
* Customers
* Expenses
* Campaigns
* Staff
* Stock Movements

Frontend should request data using query parameters:

page
per_page
search
sort_by
sort_direction

Backend must return:

{
data: [],
current_page: 1,
per_page: 20,
total: 200
}

## AJAX Only Interaction

ALL user interactions must be handled using **AJAX (no full page reload)**.

Actions that must use AJAX:

* Create
* Update
* Delete
* Search
* Pagination
* Filters
* Status changes
* POS actions

Use Axios for requests.

Example pattern:

* Submit form via AJAX
* Show loading state
* Handle success response
* Show success alert
* Update UI without refresh

## Beautiful Alert System

DO NOT use default HTML alerts.

Use a modern alert library such as:

SweetAlert2

All actions must display visual feedback.

Examples:

Success:
"Product successfully created"

Error:
"Something went wrong"

Delete confirmation must show:

"Are you sure you want to delete this item?"

## UI Buttons

All buttons must use **modern styled components**.

Button requirements:

* Rounded corners
* Icon support
* Hover animation
* Loading state

Primary actions:

Add
Save
Create
Checkout

Secondary actions:

Edit
View
Details

Danger actions:

Delete
Cancel
Refund

Buttons must be consistent across the entire application.

Use icon library:

Lucide Icons

Examples:

Add Product → Plus icon
Delete → Trash icon
Edit → Pencil icon

## Visual Consistency

All UI components must follow a **clean admin panel design**:

* Soft shadows
* Rounded cards
* Consistent spacing
* Modern typography

Tables must be placed inside **card containers**.

Forms must be:

* Well spaced
* Mobile friendly
* Touch optimized

## Loading States

All async actions must include loading states.

Examples:

* Table loading skeleton
* Button loading spinner
* POS transaction loading indicator

## Mobile Optimization

Because the system is a **PWA**, the UI must be:

* Touch friendly
* Responsive
* Optimized for small screens

Minimum touch target:

44px height buttons.

POS interface must prioritize:

* Large buttons
* Fast interaction
* Minimal typing

## Error Handling

All AJAX responses must return standardized responses:

Success:

{
success: true,
message: "Operation successful",
data: {}
}

Error:

{
success: false,
message: "Error description"
}

Frontend must show alerts based on this response.

## UX Principle

The entire system must prioritize:

Speed
Clarity
Minimal clicks

Especially for the POS workflow.

Target POS flow:

Select product → Checkout → Payment → Done

Maximum **3 clicks** for a standard transaction.

---

---

# REUSABLE COMPONENT ARCHITECTURE (CRITICAL)

To avoid duplicated code and inconsistent UI, the frontend must use **reusable components** across the entire application.

The system must NOT create separate implementations for tables, modals, buttons, or alerts.

Instead, implement a shared component library.

## Core Reusable Components

The following components MUST exist in the frontend architecture.

### DataTable Component

Component:

<DataTable />

Responsibilities:

* Server-side pagination
* Search input
* Sorting
* Page size selector
* Loading state
* Empty state

Props:

endpoint
columns
searchable
sortable
perPage

Example usage:

<DataTable endpoint="/api/products" :columns="columns" />

All list pages must use this component.

Pages required to use DataTable:

* Products
* Inventory
* Orders
* Customers
* Expenses
* Campaigns
* Staff

### FormModal Component

Component:

<FormModal />

Responsibilities:

* Create forms
* Edit forms
* AJAX submit
* Loading state
* Validation error display

Props:

* title
* endpoint
* method
* fields

Forms should open inside modals for faster UX.

### ConfirmDelete Component

Component:

<ConfirmDelete />

Responsibilities:

* Delete confirmation popup
* SweetAlert2 integration
* AJAX delete request

Example usage:

<ConfirmDelete endpoint="/api/products/1" />

### Alert Service

Centralized alert system.

Create a global service:

AlertService

Functions:

success(message)
error(message)
confirm(message)

Must internally use SweetAlert2.

### Button Components

Buttons must use reusable components.

Required buttons:

<ButtonPrimary />
<ButtonSecondary />
<ButtonDanger />
<ButtonIcon />

Features:

* Loading state
* Icon support
* Consistent styling

Example:

<ButtonPrimary icon="plus">Add Product</ButtonPrimary>

### Loading Components

Create reusable loading components:

<LoadingSpinner />
<TableSkeleton />
<PageLoader />

Used during API calls.

### POS Cart Components

POS requires specialized reusable components.

Required components:

<ProductGrid />
<CartPanel />
<CartItem />
<CheckoutModal />
<PaymentModal />

These components must be optimized for:

* touch input
* fast rendering
* minimal clicks

## Component Folder Structure

Frontend must organize components as follows:

components/

ui/
ButtonPrimary.vue
ButtonDanger.vue
LoadingSpinner.vue

forms/
FormModal.vue

alerts/
AlertService.js
ConfirmDelete.vue

tables/
DataTable.vue

pos/
ProductGrid.vue
CartPanel.vue
CartItem.vue
CheckoutModal.vue
PaymentModal.vue

## Benefits of This Architecture

This reusable architecture ensures:

* Less duplicated code
* Faster development
* Consistent UI
* Easier maintenance
* Lower AI generation errors

All future features must reuse these components instead of creating new implementations.

---

---

# KEYBOARD UX & BUTTON VISIBILITY RULES

These rules apply to the entire application to improve usability and speed, especially for POS usage.

## ESC Key Behavior (Global)

The application must implement a **global keyboard listener** for the ESC key.

Behavior:

* When the ESC key is pressed, the system must close the currently open modal.
* If multiple modals exist, only the **top-most modal** should close.
* If no modal is open, the ESC key should do nothing.

Implementation rules:

* The listener must be registered globally.
* It must work across all pages and components.
* It must support all modal components including:

FormModal
CheckoutModal
PaymentModal
Any future modal component

Example logic:

listen for keydown event
if key === "Escape"
find active modal
close modal

This improves speed for cashier workflows.

## Button Visibility & Clarity

All buttons must be **clearly visible and distinguishable**.

Requirements:

* High contrast with background
* Clear label text
* Adequate padding
* Minimum height: 44px (touch friendly)

Buttons must NEVER appear:

* too small
* faded
* hidden in low contrast colors

## Button Hierarchy

Buttons must visually communicate priority.

Primary actions:

Create
Save
Checkout
Confirm

Use:

ButtonPrimary

Secondary actions:

Edit
View
Details

Use:

ButtonSecondary

Danger actions:

Delete
Cancel
Refund

Use:

ButtonDanger

## Button Icons

Buttons should include icons when possible to improve recognition speed.

Examples:

Add → plus icon
Edit → pencil icon
Delete → trash icon
Checkout → credit-card icon

Icons must use the Lucide icon library.

## Button Loading State

When an action is processing:

* Button must show loading spinner
* Button must be temporarily disabled

Example:

"Saving..."

## Accessibility & Speed

The UI must prioritize:

* fast visual recognition
* large touch targets
* minimal cognitive load

This is especially important for:

POS transactions
Cashier workflows
Mobile device usage

---

---

# PRICE INPUT FORMAT RULE

All inputs related to **money, price, totals, payments, discounts, and costs** must automatically use **number formatting in the UI using JavaScript**.

This rule ensures that users always see formatted currency values when typing.

## Fields That Must Use Number Formatting

The following inputs must automatically format numbers:

* product price
* product cost
* discount nominal
* order total
* payment amount
* expense amount
* restock cost

## UI Behavior

When the user types numbers, the input must automatically display formatted values.

Example:

User types:

10000

Input automatically displays:

10,000

Another example:

1000000

Displays:

1,000,000

## Implementation Rules

* Formatting must happen **on key input using JavaScript**.
* Only numeric values are allowed.
* Thousand separators must be applied automatically.
* The user must not manually type commas.

## Backend Data Handling

When submitting forms:

* formatted values must be **converted back to raw numbers** before sending to the backend.

Example:

Displayed in UI:

1,500,000

Value sent to API:

1500000

## POS Specific Behavior

In the POS interface, the following inputs must always be formatted:

* discount nominal
* payment amount
* order total

This ensures cashiers can easily read numbers during transactions.

## UX Requirement

Number formatting must:

* update instantly while typing
* never block input
* maintain cursor position

## Reusable Utility

Create a reusable JavaScript utility for this:

formatCurrency()

Example usage:

formatCurrency(inputElement)

All price-related inputs must use this utility instead of implementing formatting separately.

---

---

# SEARCHABLE SELECT INPUT RULE

All select boxes in the application must support **search inside the dropdown** to improve usability, especially when datasets become large.

## Requirement

Every select field must include an **input search field** inside the dropdown.

Users must be able to:

* type to filter options
* quickly find items
* navigate large option lists

## Fields That Must Use Searchable Select

This rule applies to all select inputs including:

* product category
* product selection in POS
* customer selection
* staff selection
* campaign selection
* supplier selection
* payment method selection

## UX Behavior

When a user clicks a select field:

1. dropdown opens
2. search input appears at the top
3. typing filters options instantly

Example flow:

click select → type "cof" → results show "Coffee"

## Implementation Rule

Use a **searchable select component** instead of native HTML select.

Do NOT use plain:

<select>

Instead implement a reusable component:

<SearchSelect />

## Reusable Component

Create a shared component:

components/forms/SearchSelect.vue

Features:

* searchable options
* keyboard navigation
* AJAX option loading (optional for large datasets)
* clear selected value
* placeholder support

Props example:

endpoint
options
placeholder
value
labelField
valueField

## Performance Consideration

If options exceed **100 items**, the component should support:

* async loading
* server-side search

Example:

/api/products?search=coffee

## UX Benefits

Searchable selects significantly improve speed for:

* POS cashier workflows
* product management
* customer selection

This is mandatory across the entire application.

---
