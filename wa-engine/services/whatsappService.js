const { Client, LocalAuth } = require('whatsapp-web.js');
const QRCode = require('qrcode');
const axios = require('axios');
const fs = require('fs');
const path = require('path');

class WhatsAppService {
  constructor(options = {}) {
    this.sessionPath = options.sessionPath || './sessions';
    this.webhookUrl = options.webhookUrl || 'http://localhost:8000/api/whatsapp/webhook';
    this.messageDelay = options.messageDelay || 10000;
    
    this.client = null;
    this.qrCode = null;
    this.status = 'disconnected'; // disconnected, qr_required, connected
    this.messageQueue = [];
    this.isProcessing = false;
    this.needsRestart = false;
    this.isRestarting = false;
    
    // Ensure session directory exists
    if (!fs.existsSync(this.sessionPath)) {
      fs.mkdirSync(this.sessionPath, { recursive: true });
    }
  }

  async initialize() {
    return new Promise((resolve, reject) => {
      try {
        this.client = new Client({
          authStrategy: new LocalAuth({
            dataPath: this.sessionPath,
          }),
          puppeteer: {
            headless: true,
            args: [
              '--no-sandbox',
              '--disable-setuid-sandbox',
              '--disable-dev-shm-usage',
              '--disable-accelerated-2d-canvas',
              '--no-first-run',
              '--no-zygote',
              '--disable-gpu'
            ],
          },
        });

        this.setupEventListeners();
        
        this.client.initialize().catch(err => {
          console.error('Client initialization error:', err);
          reject(err);
        });

        // Set timeout for initialization
        setTimeout(() => {
          if (this.status === 'connected') {
            resolve();
          }
        }, 30000);

      } catch (error) {
        reject(error);
      }
    });
  }

  setupEventListeners() {
    // QR Code event
    this.client.on('qr', async (qr) => {
      console.log('📱 QR Code received');
      this.qrCode = qr;
      this.status = 'qr_required';
      
      // Generate QR as data URL for frontend
      try {
        const qrDataUrl = await QRCode.toDataURL(qr);
        this.qrCodeDataUrl = qrDataUrl;
      } catch (err) {
        console.error('QR generation error:', err);
      }
    });

    // Ready event (connected)
    this.client.on('ready', () => {
      console.log('✅ WhatsApp is ready!');
      this.status = 'connected';
      this.qrCode = null;
      this.qrCodeDataUrl = null;
      this.processMessageQueue();
    });

    // Authentication success
    this.client.on('authenticated', () => {
      console.log('🔐 Authenticated');
    });

    // Disconnected
    this.client.on('disconnected', (reason) => {
      console.log('📴 Disconnected:', reason);
      this.status = 'disconnected';
      this.qrCode = null;
      this.qrCodeDataUrl = null;
      
      // Auto reconnect
      setTimeout(() => {
        console.log('🔄 Attempting to reconnect...');
        this.client.initialize();
      }, 5000);
    });

    // Incoming messages
    this.client.on('message', async (message) => {
      console.log('📨 Message received:', message.body);
      await this.handleIncomingMessage(message);
    });

    // Loading screen
    this.client.on('loading_screen', (percent, message) => {
      console.log(`⏳ Loading: ${percent}% - ${message}`);
    });

    // Auth failure
    this.client.on('auth_failure', (msg) => {
      console.error('❌ Authentication failure:', msg);
      this.status = 'disconnected';
    });

    // Disconnected (additional handler for page errors)
    this.client.on('disconnected', (reason) => {
      console.log('📴 Disconnected:', reason);
      this.status = 'disconnected';
      this.qrCode = null;
      this.qrCodeDataUrl = null;
    });

    // Start health check
    this.startHealthCheck();
  }

  startHealthCheck() {
    // Check client health every 60 seconds
    setInterval(async () => {
      if (this.status === 'connected' && this.client) {
        try {
          // Check if we can access the page
          const page = this.client.pupPage;
          if (page && page.isClosed) {
            console.log('⚠️ Page closed detected, restarting client...');
            this.needsRestart = true;
          }
        } catch (err) {
          console.log('⚠️ Health check failed, may need restart:', err.message);
          this.needsRestart = true;
        }
      }
    }, 60000);
  }

  async handleIncomingMessage(message) {
    try {
      const webhookData = {
        phone: message.from.split('@')[0],
        message: message.body,
        timestamp: Date.now(),
        pushName: message._data?.pushName || 'Unknown',
        isGroup: message.from.includes('@g.us'),
      };

      // Send to Laravel webhook
      await axios.post(this.webhookUrl, webhookData, {
        headers: { 'Content-Type': 'application/json' },
        timeout: 5000,
      }).catch(err => {
        console.error('Webhook error:', err.message);
      });

    } catch (error) {
      console.error('Error handling incoming message:', error);
    }
  }

  async sendMessage(phone, message) {
    return new Promise((resolve, reject) => {
      if (this.status !== 'connected') {
        return reject(new Error('WhatsApp not connected'));
      }

      // Format phone number
      const formattedPhone = this.formatPhoneNumber(phone);
      
      // Add to queue
      this.messageQueue.push({
        phone: formattedPhone,
        message,
        resolve,
        reject,
        attempts: 0,
        createdAt: Date.now(),
      });

      // Process queue if not already processing
      if (!this.isProcessing) {
        this.processMessageQueue();
      }
    });
  }

  async processMessageQueue() {
    if (this.isProcessing || this.messageQueue.length === 0 || this.status !== 'connected') {
      return;
    }

    this.isProcessing = true;

    while (this.messageQueue.length > 0) {
      const job = this.messageQueue.shift();

      try {
        await this.sendWhatsAppMessage(job.phone, job.message);
        job.resolve({ success: true, phone: job.phone });
      } catch (error) {
        console.error('Send message error:', error);

        // Check if it's a detached frame error - requires client restart
        const isDetachedError = error.message.includes('detached') || 
                                error.message.includes('Target closed') ||
                                error.message.includes('Session closed');

        if (isDetachedError) {
          // Put job back and attempt client restart
          this.messageQueue.unshift(job);
          
          console.log('🔄 Attempting to restart WhatsApp client...');
          const restartSuccess = await this.restartClient();
          
          if (!restartSuccess) {
            // If restart fails, fail the job
            job.reject({ success: false, error: 'Failed to restart WhatsApp client' });
            continue;
          }
          
          // Retry immediately after restart
          continue;
        }

        // Retry logic for other errors
        if (job.attempts < 3) {
          job.attempts++;
          this.messageQueue.push(job);
        } else {
          job.reject({ success: false, error: error.message });
        }
      }

      // Rate limiting delay
      if (this.messageQueue.length > 0) {
        const delay = this.messageDelay + Math.random() * 5000; // Add random 0-5s
        console.log(`⏱️ Waiting ${Math.round(delay/1000)}s before next message...`);
        await this.sleep(delay);
      }
    }

    this.isProcessing = false;
  }

  async sendWhatsAppMessage(phone, message) {
    try {
      // Create chat ID
      const chatId = `${phone}@c.us`;

      // Check if client is ready
      if (!this.client || !this.client.info) {
        throw new Error('WhatsApp client not initialized');
      }

      // Send message with timeout
      const sendPromise = this.client.sendMessage(chatId, message);
      const timeoutPromise = new Promise((_, reject) => 
        setTimeout(() => reject(new Error('Message send timeout')), 30000)
      );

      await Promise.race([sendPromise, timeoutPromise]);

      console.log(`✅ Message sent to ${phone}`);
      return { success: true };
    } catch (error) {
      // Check if it's a detached frame error
      const isDetachedError = error.message.includes('detached') || 
                              error.message.includes('Target closed') ||
                              error.message.includes('Session closed');

      if (isDetachedError) {
        console.error(`🔄 Detached frame error for ${phone}, attempting recovery...`);
        // Mark client for restart
        this.needsRestart = true;
      }

      console.error(`Failed to send to ${phone}:`, error.message);
      throw error;
    }
  }

  formatPhoneNumber(phone) {
    // Remove all non-numeric characters
    let cleaned = phone.replace(/\D/g, '');
    
    // Remove leading zeros
    cleaned = cleaned.replace(/^0+/, '');
    
    // Add country code if missing (default to Indonesia +62)
    if (!cleaned.startsWith('62')) {
      cleaned = '62' + cleaned;
    }
    
    return cleaned;
  }

  getQRCode() {
    return this.qrCodeDataUrl;
  }

  getStatus() {
    return {
      status: this.status,
      hasQR: !!this.qrCodeDataUrl,
    };
  }

  async logout() {
    try {
      await this.client.logout();
      this.status = 'disconnected';
      this.qrCode = null;
      this.qrCodeDataUrl = null;
      return { success: true };
    } catch (error) {
      return { success: false, error: error.message };
    }
  }

  async destroySession() {
    try {
      await this.client.destroy();
      // Clear session files
      const sessionDir = path.join(__dirname, '..', this.sessionPath);
      if (fs.existsSync(sessionDir)) {
        fs.rmSync(sessionDir, { recursive: true, force: true });
        fs.mkdirSync(sessionDir, { recursive: true });
      }
      this.status = 'disconnected';
      this.qrCode = null;
      this.qrCodeDataUrl = null;
      return { success: true };
    } catch (error) {
      return { success: false, error: error.message };
    }
  }

  sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  async restartClient() {
    if (this.isRestarting) {
      console.log('⏳ Already restarting, waiting...');
      await this.sleep(5000);
      return this.status === 'connected';
    }

    this.isRestarting = true;
    this.needsRestart = false;

    try {
      console.log('🔄 Restarting WhatsApp client...');
      
      // Stop current client
      if (this.client) {
        try {
          await this.client.destroy();
        } catch (err) {
          console.error('Error destroying client:', err.message);
        }
        this.client = null;
      }

      // Wait a moment
      await this.sleep(2000);

      // Reinitialize
      this.status = 'disconnected';
      await this.initialize();

      // Wait for connection
      for (let i = 0; i < 30; i++) {
        if (this.status === 'connected') {
          console.log('✅ Client restarted successfully');
          this.isRestarting = false;
          return true;
        }
        await this.sleep(1000);
      }

      console.error('❌ Client restart timeout');
      this.isRestarting = false;
      return false;
    } catch (error) {
      console.error('❌ Client restart failed:', error.message);
      this.isRestarting = false;
      return false;
    }
  }

  async cleanup() {
    console.log('Cleaning up...');
    if (this.client) {
      await this.client.destroy();
    }
  }
}

module.exports = WhatsAppService;
