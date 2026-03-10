const express = require('express');
const router = express.Router();
const sessionController = require('../controllers/sessionController');

// GET /api/session/status
router.get('/status', sessionController.getSessionStatus);

// GET /api/session/qr
router.get('/qr', sessionController.getQRCode);

// POST /api/session/logout
router.post('/logout', sessionController.logout);

// POST /api/session/destroy
router.post('/destroy', sessionController.destroySession);

module.exports = router;
