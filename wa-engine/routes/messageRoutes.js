const express = require('express');
const router = express.Router();
const messageController = require('../controllers/messageController');

// POST /api/messages/send
router.post('/send', messageController.sendMessage);

// POST /api/messages/send-bulk
router.post('/send-bulk', messageController.sendBulkMessages);

// GET /api/messages/queue-status
router.get('/queue-status', messageController.getQueueStatus);

module.exports = router;
