const sendMessage = async (req, res) => {
  try {
    const { phone, message, type = 'text', image_url, caption } = req.body;

    if (!phone) {
      return res.status(400).json({ success: false, error: 'Phone is required' });
    }
    if (type === 'image' && !image_url) {
      return res.status(400).json({ success: false, error: 'image_url required for image' });
    }
    if (type !== 'image' && !message) {
      return res.status(400).json({ success: false, error: 'message is required' });
    }

    const waService = req.app.get('waService');

    // Respond immediately, send in background
    waService.sendMessage(phone, message, { type, image_url, caption })
      .then(result => console.log(`✓ Sent to ${phone}`))
      .catch(err => console.error(`✗ Failed to ${phone}:`, err.message));

    res.json({ success: true, message: 'Message queued for delivery' });
  } catch (error) {
    console.error('Send message error:', error);
    res.status(500).json({ success: false, error: error.message });
  }
};

const sendBulkMessages = async (req, res) => {
  try {
    const { messages } = req.body;

    if (!Array.isArray(messages) || messages.length === 0) {
      return res.status(400).json({
        success: false,
        error: 'Messages array is required',
      });
    }

    const waService = req.app.get('waService');
    const results = [];

    for (const msg of messages) {
      try {
        const result = await waService.sendMessage(msg.phone, msg.message);
        results.push({ phone: msg.phone, success: true });
      } catch (error) {
        results.push({ phone: msg.phone, success: false, error: error.message });
      }
    }

    res.json({
      success: true,
      total: messages.length,
      results,
    });
  } catch (error) {
    console.error('Bulk send error:', error);
    res.status(500).json({
      success: false,
      error: error.message || 'Failed to send bulk messages',
    });
  }
};

const getQueueStatus = async (req, res) => {
  try {
    const waService = req.app.get('waService');
    
    res.json({
      success: true,
      queueLength: waService.messageQueue.length,
      isProcessing: waService.isProcessing,
      status: waService.status,
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message,
    });
  }
};

module.exports = {
  sendMessage,
  sendBulkMessages,
  getQueueStatus,
};
