# WA Engine - Self-Hosted WhatsApp Automation

A Node.js microservice for WhatsApp automation using whatsapp-web.js.

## Features

- ✅ QR Code login for WhatsApp Web
- ✅ Persistent sessions (no need to scan QR every time)
- ✅ Send messages with rate limiting
- ✅ Receive messages via webhook
- ✅ Auto reconnect on disconnect
- ✅ Message queue with retry logic

## Installation

```bash
cd wa-engine
npm install
cp .env.example .env
```

## Configuration

Edit `.env`:

```env
PORT=3000
API_KEY=your-secret-api-key
LARAVEL_WEBHOOK_URL=http://localhost:8000/api/whatsapp/webhook
MESSAGE_DELAY=10000
```

## Running

```bash
# Development
npm run dev

# Production
npm start
```

## API Endpoints

### Session Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/session/status` | Get connection status |
| GET | `/api/session/qr` | Get QR code for login |
| POST | `/api/session/logout` | Logout from WhatsApp |
| POST | `/api/session/destroy` | Destroy session |

### Messages

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/messages/send` | Send single message |
| POST | `/api/messages/send-bulk` | Send bulk messages |
| GET | `/api/messages/queue-status` | Get queue status |

### Webhook

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/webhook/incoming` | Receive incoming messages |

## Usage Examples

### Check Status

```bash
curl -H "X-API-KEY: your-api-key" \
  http://localhost:3000/api/session/status
```

### Get QR Code

```bash
curl -H "X-API-KEY: your-api-key" \
  http://localhost:3000/api/session/qr
```

### Send Message

```bash
curl -X POST \
  -H "X-API-KEY: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{"phone":"628123456789","message":"Hello!"}' \
  http://localhost:3000/api/messages/send
```

## Security

All endpoints require `X-API-KEY` header for authentication.

## Rate Limiting

Default delay between messages: 10 seconds (configurable via `MESSAGE_DELAY`)

This helps prevent WhatsApp bans from sending too many messages.
