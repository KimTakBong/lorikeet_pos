# Deploy Lorikeet to Render with TiDB Cloud

## Prerequisites

1. **TiDB Cloud Account** - Sign up at https://tidbcloud.com
2. **Render Account** - Sign up at https://render.com
3. **GitHub Repo** - Push your code to GitHub

## Step 1: Setup TiDB Cloud

### Create Serverless Tier (Free)

1. Login ke https://tidbcloud.com
2. Click **"New Cluster"**
3. Pilih **"Serverless"** (Free tier)
4. Pilih region: **AWS - ap-southeast-1 (Singapore)**
5. Click **"Create"**

### Get Connection String

1. Click cluster yang baru dibuat
2. Click **"Connect"**
3. Pilih **"General Connection"**
4. Copy connection details:
   - Host: `gateway01.ap-southeast-1.prod.aws.tidbcloud.com`
   - Port: `4000`
   - Username: `xxxx.root`
   - Password: `xxxx`
   - Database: `lorikeet`

### Download SSL Certificate

1. Di halaman Connect, download **SSL CA Certificate**
2. Save sebagai `tidb-ca.pem` di root project

## Step 2: Prepare Laravel App

### Update Database Config

Edit `config/database.php` - add SSL option:

```php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'laravel'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'unix_socket' => env('DB_SOCKET', ''),
    'charset' => env('DB_CHARSET', 'utf8mb4'),
    'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => null,
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
    ]) : [],
],
```

### Create SSL Certificate for Render

Di project root, buat file `tidb-ca.pem` (copy dari TiDB Cloud)

## Step 3: Setup Render

### Create Web Service

1. Login ke https://render.com
2. Click **"New+"** → **"Web Service"**
3. Connect GitHub repo **Lorikeet**
4. Configure:
   - **Name**: `lorikeet`
   - **Region**: `Singapore`
   - **Branch**: `main`
   - **Root Directory**: (leave blank)
   - **Runtime**: `PHP`
   - **Build Command**:
     ```bash
     composer install --no-dev --optimize-autoloader --no-scripts && npm install && npm run build
     ```
   - **Start Command**:
     ```bash
     vendor/bin/heroku-php-apache2 public/
     ```
   - **Instance Type**: `Free`

### Environment Variables

Add these environment variables:

```bash
# App
APP_NAME=Lorikeet
APP_ENV=production
APP_DEBUG=false
APP_URL=https://lorikeet.onrender.com
APP_KEY=base64:VpXfKNEkSNUucFANd8UMYWn/5weTXYqtG4EIcgmVW14=

# Database (TiDB Cloud)
DB_CONNECTION=mysql
DB_HOST=gateway01.ap-southeast-1.prod.aws.tidbcloud.com
DB_PORT=4000
DB_DATABASE=lorikeet
DB_USERNAME=<your-tidb-username>
DB_PASSWORD=<your-tidb-password>
MYSQL_ATTR_SSL_CA=/etc/tidb-ca.pem

# Session & Cache
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database

# Logging
LOG_CHANNEL=errorlog
LOG_LEVEL=info

# Security
TRUST_PROXIES=*
```

### Upload SSL Certificate

1. Di Render dashboard → **Settings** → **Files**
2. Upload `tidb-ca.pem` ke `/etc/tidb-ca.pem`

## Step 4: Run Migrations

Setelah deploy selesai:

1. Go to **Render Dashboard** → Your service
2. Click **Shell** tab
3. Run:
   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   ```

## Step 5: Setup Queue Worker (Optional)

Untuk WhatsApp automation:

1. **New+** → **Worker**
2. Configure:
   - **Name**: `lorikeet-worker`
   - **Region**: `Singapore`
   - **Build Command**: `composer install --no-dev --optimize-autoloader`
   - **Start Command**: `php artisan queue:work --sleep=3 --tries=3 --timeout=0`
3. Add same environment variables

## Troubleshooting

### Connection Failed

- Check TiDB cluster is **active**
- Verify SSL certificate path
- Check firewall rules di TiDB Cloud (allow all IPs)

### Migration Failed

```bash
# Connect via SSH dan run:
php artisan migrate:status
php artisan migrate:rollback
php artisan migrate --force
```

### Logs

```bash
# Di Render dashboard → Logs
# Or via CLI:
render logs -f
```

## Cost Estimate

| Service | Cost |
|---------|------|
| Render Web (Free) | $0/mo |
| Render Worker (Free) | $0/mo |
| TiDB Cloud Serverless | $0/mo (up to 5GB storage, 50M req/mo) |
| **Total** | **$0/mo** |

## Notes

- Render free tier **sleeps** after 15 minutes idle
- First request after sleep takes ~30 seconds to wake up
- TiDB serverless is **production-ready** for small apps
