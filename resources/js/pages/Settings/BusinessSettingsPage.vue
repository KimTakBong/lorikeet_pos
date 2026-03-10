<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-3">
      <button @click="$router.push('/settings')" class="p-2 hover:bg-gray-100 rounded-lg">
        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Business Settings</h1>
        <p class="text-gray-500 mt-1">Configure your business profile and receipt settings</p>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <!-- Form -->
    <div v-else class="max-w-2xl">
      <form @submit.prevent="saveSettings" class="space-y-6">
        <!-- Store Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Store Name *
          </label>
          <input
            v-model="form.store_name"
            type="text"
            required
            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="e.g., TOKO KOPI NUSANTARA"
          />
          <p class="text-xs text-gray-500 mt-1">Displayed on WhatsApp receipts and invoices</p>
        </div>

        <!-- Address -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Address
          </label>
          <textarea
            v-model="form.address"
            rows="3"
            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Street address, city, postal code"
          ></textarea>
        </div>

        <!-- Phone -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Phone Number
          </label>
          <input
            v-model="form.phone"
            type="text"
            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="e.g., +62 812 3456 7890"
          />
        </div>

        <!-- Tax Number -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Tax Number (NPWP)
          </label>
          <input
            v-model="form.tax_number"
            type="text"
            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="e.g., 01.234.567.8-901.000"
          />
        </div>

        <!-- Preview -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
          <h3 class="font-semibold text-gray-900 mb-3">Receipt Preview</h3>
          <div class="bg-white border border-dashed border-gray-300 rounded p-4 font-mono text-sm">
            <p class="text-center font-bold mb-2">*{{ form.store_name || 'STORE NAME' }}*</p>
            <p class="text-center text-gray-600 mb-4">Invoice: INV-20260310-00001</p>
            <div class="border-t border-b border-dashed border-gray-300 py-2 mb-2">
              <p class="flex justify-between"><span>Item x1</span><span>25.000</span></p>
            </div>
            <p class="flex justify-between font-bold"><span>TOTAL:</span><span>25.000</span></p>
            <p class="text-center text-gray-600 mt-4">Thank you for your purchase!</p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-3 pt-4">
          <ButtonSecondary type="button" @click="$router.push('/settings')" class="flex-1">
            Cancel
          </ButtonSecondary>
          <ButtonPrimary type="submit" :loading="saving" class="flex-1">
            Save Settings
          </ButtonPrimary>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import ButtonPrimary from '../../components/ui/ButtonPrimary.vue';
import ButtonSecondary from '../../components/ui/ButtonSecondary.vue';
import AlertService from '../../components/alerts/AlertService.js';

const loading = ref(true);
const saving = ref(false);

const form = reactive({
  store_name: '',
  address: '',
  phone: '',
  tax_number: ''
});

const token = localStorage.getItem('token');

onMounted(async () => {
  await loadSettings();
});

async function loadSettings() {
  try {
    loading.value = true;
    const response = await axios.get('/api/settings', {
      headers: { Authorization: `Bearer ${token}` }
    });
    
    const data = response.data.data || {};
    form.store_name = data['business.store_name'] || '';
    form.address = data['business.address'] || '';
    form.phone = data['business.phone'] || '';
    form.tax_number = data['business.tax_number'] || '';
  } catch (e) {
    console.error('Failed to load settings:', e);
    AlertService.error('Failed to load settings');
  } finally {
    loading.value = false;
  }
}

async function saveSettings() {
  try {
    saving.value = true;
    
    const payload = {
      settings: {
        'business.store_name': form.store_name,
        'business.address': form.address,
        'business.phone': form.phone,
        'business.tax_number': form.tax_number
      }
    };

    await axios.post('/api/settings', payload, {
      headers: { Authorization: `Bearer ${token}` }
    });

    AlertService.success('Business settings saved successfully!');
  } catch (e) {
    console.error('Failed to save settings:', e);
    AlertService.error('Failed to save settings');
  } finally {
    saving.value = false;
  }
}
</script>
