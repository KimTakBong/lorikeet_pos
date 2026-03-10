# WhatsApp Engine Setup Guide

## Overview

The system now includes a **self-hosted WhatsApp automation engine** that uses WhatsApp Web to send and receive messages without third-party APIs.

## Architecture

```
┌─────────────────┐      HTTP API      ┌─────────────────┐
│   Laravel App   │ ◄────────────────► │   WA Engine     │
│   (Dashboard)   │                    │   (Node.js)     │
│                 │                    │  Port: 3000     │
└─────────────────┘                    └─────────────────┘
        │                                       │
        │                                       │
        ▼                                       ▼
  message_queue                         whatsapp-web.js
  (pending/sent)                        (WhatsApp Web)
```

## Installation

### Step 1: Install WA Engine Dependencies

```bash
cd /Users/alfanrlyan/Desktop/laravel/Lorikeet/wa-engine
npm install
```

### Step 2: Configure WA Engine

```bash
cd wa-engine
cp .env.example .env
nano .env
```

Edit `.env`:
```env
PORT=3000
API_KEY=wa-engine-secret-key-2026
LARAVEL_WEBHOOK_URL=http://localhost:8000/api/whatsapp/webhook
MESSAGE_DELAY=10000
```

### Step 3: Start WA Engine

```bash
# Development
npm run dev

# Production
npm start
```

### Step 4: Verify WA Engine is Running

```bash
curl http://localhost:3000/health
# Should return: {"status":"ok","service":"wa-engine"}
```

## WhatsApp Login (QR Code)

### Option 1: Via API

```bash
# Check status
curl -H "X-API-KEY: wa-engine-secret-key-2026" \
  http://localhost:3000/api/session/status

# Get QR code (returns base64 image)
curl -H "X-API-KEY: wa-engine-secret-key-2026" \
  http://localhost:3000/api/session/qr
```

### Option 2: Via Laravel Dashboard (Coming Soon)

A frontend UI will be added to scan QR code directly from the dashboard.

## Sending Campaign Messages

### Step 1: Create Campaign in Laravel

1. Go to `/campaigns`
2. Create campaign
3. Add recipients
4. Compose message
5. Click "Send"

### Step 2: Process Messages

```bash
# Laravel will queue messages to message_queue table
# Process them with:
php artisan whatsapp:process
```

The command will:
1. Fetch pending messages from `message_queue`
2. Send each message to WA Engine API
3. Update status to `sent` or `failed`

## Running in Production

### Using PM2 (Recommended)

```bash
# Install PM2 globally
npm install -g pm2

# Start WA Engine
cd wa-engine
pm2 start ecosystem.config.js

# Save PM2 configuration
pm2 save

# Setup PM2 to start on boot
pm2 startup
```

### Using Supervisor

Create `/etc/supervisor/conf.d/wa-engine.conf`:

```ini
[program:wa-engine]
directory=/path/to/wa-engine
command=npm start
autostart=true
autorestart=true
stderr_logfile=/var/log/wa-engine.err.log
stdout_logfile=/var/log/wa-engine.out.log
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start wa-engine
```

## Troubleshooting

### WA Engine Won't Start

```bash
# Check if port 3000 is in use
lsof -i :3000

# Kill process if needed
kill -9 <PID>

# Restart WA Engine
npm start
```

### QR Code Not Appearing

```bash
# Check session status
curl -H "X-API-KEY: wa-engine-secret-key-2026" \
  http://localhost:3000/api/session/status

# If status is "connected", logout first
curl -X POST \
  -H "X-API-KEY: wa-engine-secret-key-2026" \
  http://localhost:3000/api/session/logout

# Or destroy session completely
curl -X POST \
  -H "X-API-KEY: wa-engine-secret-key-2026" \
  http://localhost:3000/api/session/destroy
```

### Messages Not Sending

1. Check WA Engine logs
2. Verify WhatsApp is connected: `GET /api/session/status`
3. Check message_queue table for failed messages
4. Verify API key matches in both Laravel and WA Engine

## Security

- All WA Engine endpoints require `X-API-KEY` header
- Change default API_KEY in production
- Use HTTPS in production
- Firewall port 3000 to only allow Laravel server

## Rate Limiting

Default: 10 seconds between messages

To change:
```env
MESSAGE_DELAY=5000  # 5 seconds
```

⚠️ **Warning**: Too fast may cause WhatsApp bans!

## Session Persistence

Sessions are stored in `wa-engine/sessions/` directory.

- Sessions persist across restarts
- No need to scan QR every time
- Backup this directory for disaster recovery

## Incoming Messages

When a customer replies:

1. WA Engine receives the message
2. Sends webhook to Laravel: `POST /api/whatsapp/webhook`
3. Laravel logs the message
4. (Future) Auto-reply logic can be implemented

## API Reference

### WA Engine Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/health` | Health check |
| GET | `/api/session/status` | Get connection status |
| GET | `/api/session/qr` | Get QR code |
| POST | `/api/session/logout` | Logout |
| POST | `/api/session/destroy` | Destroy session |
| POST | `/api/messages/send` | Send message |
| POST | `/api/messages/send-bulk` | Send bulk |
| GET | `/api/messages/queue-status` | Queue status |

### Laravel Commands

| Command | Description |
|---------|-------------|
| `php artisan whatsapp:process` | Process pending messages |

## Next Steps

1. ✅ WA Engine setup
2. ✅ Laravel integration
3. ⏳ Dashboard UI for QR login
4. ⏳ Auto-reply configuration
5. ⏳ Message templates
6. ⏳ Campaign analytics
