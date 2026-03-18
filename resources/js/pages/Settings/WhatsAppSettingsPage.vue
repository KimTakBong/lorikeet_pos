<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-white">WhatsApp Settings</h1>
      <p class="text-gray-400 mt-1">Manage your WhatsApp connection and campaigns</p>
    </div>

    <!-- Connection Status -->
    <div class="bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-700">
      <h2 class="text-lg font-semibold text-white mb-4">Connection Status</h2>
      
      <div class="flex items-center justify-between p-4 bg-gray-700/50 rounded-lg">
        <div class="flex items-center gap-3">
          <div :class="[
            'w-3 h-3 rounded-full',
            status === 'connected' ? 'bg-green-500 animate-pulse' : 
            status === 'qr_required' ? 'bg-yellow-500' : 'bg-red-500'
          ]"></div>
          <div>
            <div class="font-medium text-white capitalize">{{ statusText }}</div>
            <div class="text-sm text-gray-400">{{ statusDescription }}</div>
          </div>
        </div>
        
        <ButtonSecondary @click="checkStatus" :loading="checkingStatus">
          Refresh
        </ButtonSecondary>
      </div>
    </div>

    <!-- QR Code Section -->
    <div v-if="status === 'qr_required' && qrCode" class="bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-700">
      <h2 class="text-lg font-semibold text-white mb-4">Scan QR Code</h2>
      
      <div class="flex flex-col items-center">
        <img :src="qrCode" alt="WhatsApp QR Code" class="w-64 h-64 border-4 border-gray-600 rounded-lg" />
        <p class="text-sm text-gray-400 mt-4 text-center">
          Open WhatsApp on your phone → Settings → Linked Devices → Link a Device<br/>
          Then scan this QR code
        </p>
        <ButtonSecondary @click="loadQRCode" class="mt-4">
          Refresh QR
        </ButtonSecondary>
      </div>
    </div>

    <!-- Already Connected -->
    <div v-if="status === 'connected'" class="bg-emerald-900/30 border border-emerald-700/50 rounded-xl p-6">
      <div class="flex items-start gap-3">
        <svg class="w-6 h-6 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
          <h3 class="font-semibold text-emerald-300">WhatsApp Connected</h3>
          <p class="text-sm text-emerald-400 mt-1">
            Your WhatsApp is connected and ready to send messages.
          </p>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-700">
      <h2 class="text-lg font-semibold text-white mb-4">Actions</h2>
      
      <div class="flex gap-3">
        <ButtonDanger @click="confirmLogout" :loading="actionLoading">
          Logout
        </ButtonDanger>
        
        <ButtonSecondary @click="confirmDestroy" :loading="actionLoading">
          Destroy Session
        </ButtonSecondary>
      </div>
      
      <p class="text-xs text-gray-400 mt-2">
        Logout: Sign out but keep session (can reconnect without QR)<br/>
        Destroy: Delete session completely (will need to scan QR again)
      </p>
    </div>

    <!-- Test Message -->
    <div class="bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-700">
      <h2 class="text-lg font-semibold text-white mb-4">Test Connection</h2>
      
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-300 mb-2">Phone Number</label>
          <input v-model="testPhone" type="tel" placeholder="628123456789" class="input-modern" />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-300 mb-2">Message</label>
          <textarea v-model="testMessage" rows="3" placeholder="Test message" class="input-modern"></textarea>
        </div>
        
        <ButtonPrimary @click="sendTestMessage" :disabled="status !== 'connected'" :loading="sending">
          Send Test Message
        </ButtonPrimary>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import ButtonPrimary from '../../components/ui/ButtonPrimary.vue';
import ButtonSecondary from '../../components/ui/ButtonSecondary.vue';
import ButtonDanger from '../../components/ui/ButtonDanger.vue';
import AlertService from '../../components/alerts/AlertService.js';

const status = ref('disconnected');
const qrCode = ref(null);
const checkingStatus = ref(false);
const actionLoading = ref(false);
const sending = ref(false);
const testPhone = ref('');
const testMessage = ref('Hello from WhatsApp Engine!');

const statusText = computed(() => {
  switch (status.value) {
    case 'connected': return 'Connected';
    case 'qr_required': return 'Scan Required';
    default: return 'Disconnected';
  }
});

const statusDescription = computed(() => {
  switch (status.value) {
    case 'connected': return 'WhatsApp is connected and ready to send messages';
    case 'qr_required': return 'Scan QR code to connect your WhatsApp';
    default: return 'WhatsApp engine is not connected';
  }
});

const waEngineUrl = 'http://localhost:3000';
const apiKey = 'wa-engine-secret-key-2026';

async function checkStatus() {
  checkingStatus.value = true;
  try {
    const response = await axios.get(`${waEngineUrl}/api/session/status`, {
      headers: { 'X-API-KEY': apiKey }
    });
    status.value = response.data.data?.status || 'disconnected';
    
    if (status.value === 'qr_required') {
      await loadQRCode();
    }
  } catch (error) {
    console.error('Status check failed:', error);
    status.value = 'disconnected';
  } finally {
    checkingStatus.value = false;
  }
}

async function loadQRCode() {
  try {
    const response = await axios.get(`${waEngineUrl}/api/session/qr`, {
      headers: { 'X-API-KEY': apiKey }
    });
    qrCode.value = response.data.data?.qrCode || null;
  } catch (error) {
    console.error('QR load failed:', error);
    qrCode.value = null;
  }
}

async function confirmLogout() {
  if (!await AlertService.confirm('Are you sure you want to logout?')) return;
  
  actionLoading.value = true;
  try {
    await axios.post(`${waEngineUrl}/api/session/logout`, {}, {
      headers: { 'X-API-KEY': apiKey }
    });
    AlertService.success('Logged out successfully');
    await checkStatus();
  } catch (error) {
    AlertService.error('Logout failed: ' + error.message);
  } finally {
    actionLoading.value = false;
  }
}

async function confirmDestroy() {
  if (!await AlertService.confirm('Are you sure? This will delete the session and you need to scan QR again.')) return;
  
  actionLoading.value = true;
  try {
    await axios.post(`${waEngineUrl}/api/session/destroy`, {}, {
      headers: { 'X-API-KEY': apiKey }
    });
    AlertService.success('Session destroyed');
    await checkStatus();
  } catch (error) {
    AlertService.error('Destroy failed: ' + error.message);
  } finally {
    actionLoading.value = false;
  }
}

async function sendTestMessage() {
  if (!testPhone.value) {
    AlertService.error('Please enter a phone number');
    return;
  }
  
  sending.value = true;
  try {
    await axios.post(`${waEngineUrl}/api/messages/send`, {
      phone: testPhone.value,
      message: testMessage.value
    }, {
      headers: { 'X-API-KEY': apiKey }
    });
    AlertService.success('Test message sent!');
  } catch (error) {
    AlertService.error('Failed: ' + (error.response?.data?.error || error.message));
  } finally {
    sending.value = false;
  }
}

onMounted(() => {
  checkStatus();
  const interval = setInterval(checkStatus, 5000);
  return () => clearInterval(interval);
});
</script>
