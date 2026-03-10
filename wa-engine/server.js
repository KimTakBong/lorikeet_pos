const express = require('express');
const cors = require('cors');
const WhatsAppService = require('./services/whatsappService');
const messageRoutes = require('./routes/messageRoutes');
const sessionRoutes = require('./routes/sessionRoutes');
const webhookRoutes = require('./routes/webhookRoutes');

const app = express();
const PORT = process.env.PORT || 3000;
const API_KEY = process.env.API_KEY || 'wa-engine-secret-key-2026';

// Middleware
app.use(cors());
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true }));

// API Key middleware
const authenticateAPI = (req, res, next) => {
  const apiKey = req.headers['x-api-key'];
  if (apiKey && apiKey === API_KEY) {
    return next();
  }
  return res.status(401).json({ success: false, error: 'Unauthorized' });
};

// Initialize WhatsApp service
const waService = new WhatsAppService({
  sessionPath: './sessions',
  webhookUrl: process.env.LARAVEL_WEBHOOK_URL || 'http://localhost:8000/api/whatsapp/webhook',
  messageDelay: parseInt(process.env.MESSAGE_DELAY) || 10000, // 10 seconds default
});

// Make waService available to routes
app.set('waService', waService);
app.set('API_KEY', API_KEY);

// Routes
app.use('/api/messages', authenticateAPI, messageRoutes);
app.use('/api/session', authenticateAPI, sessionRoutes);
app.use('/api/webhook', webhookRoutes);

// Health check
app.get('/health', (req, res) => {
  res.json({ status: 'ok', service: 'wa-engine' });
});

// Start server
app.listen(PORT, '0.0.0.0', async () => {
  console.log(`🚀 WA Engine running on port ${PORT}`);
  console.log(`📱 Initializing WhatsApp service...`);
  
  try {
    await waService.initialize();
    console.log('✅ WhatsApp service initialized');
  } catch (error) {
    console.error('❌ Failed to initialize WhatsApp service:', error.message);
  }
});

// Graceful shutdown
process.on('SIGINT', async () => {
  console.log('\n🛑 Shutting down...');
  await waService.cleanup();
  process.exit(0);
});
