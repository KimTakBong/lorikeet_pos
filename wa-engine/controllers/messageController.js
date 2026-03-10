const sendMessage = async (req, res) => {
  try {
    const { phone, message } = req.body;

    if (!phone || !message) {
      return res.status(400).json({
        success: false,
        error: 'Phone and message are required',
      });
    }

    const waService = req.app.get('waService');
    const result = await waService.sendMessage(phone, message);

    res.json(result);
  } catch (error) {
    console.error('Send message error:', error);
    res.status(500).json({
      success: false,
      error: error.message || 'Failed to send message',
    });
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
