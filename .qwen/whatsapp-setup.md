# WhatsApp Auto-Send Setup for POS

## Problem Solved
WhatsApp receipts were not being sent automatically after POS payments because:
1. WA Engine (WhatsApp service) was not running
2. Queue worker needs to process messages

## Solution Applied

### 1. WA Engine Service
**Status:** ✅ Running on `http://localhost:3000`

**Start Command:**
```bash
cd /Users/alfanrlyan/Desktop/laravel/Lorikeet/wa-engine
node server.js > /tmp/wa-engine.log 2>&1 &
```

**Health Check:**
```bash
curl http://localhost:3000/health
# Returns: {"status":"ok","service":"wa-engine"}
```

### 2. Queue Worker
**Status:** ⚠️ Needs to run continuously

**Start Queue Worker:**
```bash
cd /Users/alfanrlyan/Desktop/laravel/Lorikeet
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

### 3. Automatic Processing (Recommended)

Add to crontab (`crontab -e`):
```bash
# Process WhatsApp messages every minute
* * * * * cd /Users/alfanrlyan/Desktop/laravel/Lorikeet && php artisan whatsapp:process >> /tmp/whatsapp.log 2>&1

# Keep queue worker running
* * * * * cd /Users/alfanrlyan/Desktop/laravel/Lorikeet && php artisan queue:work --sleep=3 --tries=3 --stop-when-empty >> /tmp/queue.log 2>&1
```

## Testing WhatsApp Send from POS

1. **Go to POS page**
2. **Add items to cart**
3. **Enter customer WhatsApp number** (e.g., `081234567890`)
4. **Click "Find"** (optional)
5. **Click "Pay"**
6. **Complete payment**
7. **Wait 1-2 minutes** - WhatsApp receipt will be sent automatically

## Manual Send (If Needed)

```bash
# Process all pending WhatsApp messages
cd /Users/alfanrlyan/Desktop/laravel/Lorikeet
php artisan whatsapp:process
```

## Monitoring

**Check pending messages:**
```bash
php artisan tinker --execute="echo 'Pending: ' . \App\Models\MessageQueue::where('status', 'pending')->count();"
```

**Check WA Engine status:**
```bash
curl http://localhost:3000/health
```

**View WA Engine logs:**
```bash
tail -f /tmp/wa-engine.log
```

## Troubleshooting

### WhatsApp not sending?

1. **Check WA Engine is running:**
   ```bash
   curl http://localhost:3000/health
   ```

2. **Check queue has messages:**
   ```bash
   php artisan tinker --execute="echo \App\Models\MessageQueue::where('status', 'pending')->count();"
   ```

3. **Process manually:**
   ```bash
   php artisan whatsapp:process
   ```

### QR Code needed?

If WhatsApp is not connected:
```bash
curl -H "X-API-KEY: wa-engine-secret-key-2026" \
  http://localhost:3000/api/session/qr
```

Then scan QR code with WhatsApp mobile app.

