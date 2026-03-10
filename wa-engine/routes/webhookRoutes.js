const express = require('express');
const router = express.Router();
const webhookController = require('../controllers/webhookController');

// POST /api/webhook/incoming
router.post('/incoming', webhookController.handleIncomingMessage);

module.exports = router;
