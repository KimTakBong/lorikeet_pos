const handleIncomingMessage = async (req, res) => {
  try {
    const { phone, message, timestamp, pushName, isGroup } = req.body;

    console.log('📨 Incoming webhook:', { phone, message, timestamp });

    // Acknowledge receipt
    res.json({
      success: true,
      message: 'Webhook received',
    });

    // TODO: Add auto-reply logic here if needed
    // For now, just log the message

  } catch (error) {
    console.error('Webhook handler error:', error);
    res.status(500).json({
      success: false,
      error: error.message,
    });
  }
};

module.exports = {
  handleIncomingMessage,
};
