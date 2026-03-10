const getSessionStatus = async (req, res) => {
  try {
    const waService = req.app.get('waService');
    const status = waService.getStatus();

    res.json({
      success: true,
      data: status,
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message,
    });
  }
};

const getQRCode = async (req, res) => {
  try {
    const waService = req.app.get('waService');
    const qrCode = waService.getQRCode();

    if (!qrCode) {
      return res.json({
        success: true,
        data: null,
        message: 'No QR code available. Session may already be authenticated.',
      });
    }

    res.json({
      success: true,
      data: {
        qrCode,
        format: 'data-url',
      },
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message,
    });
  }
};

const logout = async (req, res) => {
  try {
    const waService = req.app.get('waService');
    const result = await waService.logout();

    res.json(result);
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message,
    });
  }
};

const destroySession = async (req, res) => {
  try {
    const waService = req.app.get('waService');
    const result = await waService.destroySession();

    res.json(result);
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message,
    });
  }
};

module.exports = {
  getSessionStatus,
  getQRCode,
  logout,
  destroySession,
};
