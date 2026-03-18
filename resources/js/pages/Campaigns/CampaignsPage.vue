<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900">Campaigns</h1>
      <ButtonPrimary @click="openCreateModal">
        <Megaphone class="w-4 h-4" />
        Create Campaign
      </ButtonPrimary>
    </div>

    <DataTable ref="tableRef" :columns="columns" :fetch-data="fetchCampaigns" search-placeholder="Search campaigns..." empty-message="No campaigns found">
      <template #cell-name="{ item }">
        <div class="font-medium text-gray-900">{{ item.name }}</div>
        <div class="text-xs text-gray-500 capitalize">{{ item.type }}</div>
      </template>

      <template #cell-dates="{ item }">
        <div class="text-sm">
          <div class="text-gray-600">{{ formatDate(item.start_date) }}</div>
          <div class="text-xs text-gray-400">to {{ formatDate(item.end_date) }}</div>
        </div>
      </template>

      <template #cell-recipients="{ item }">
        <div class="text-sm">
          <div class="font-medium">{{ item.recipients_count || 0 }} recipients</div>
        </div>
      </template>

      <template #actions="{ item }">
        <div class="flex justify-end gap-2">
          <ButtonIcon @click="openRecipientsModal(item)" title="Manage Recipients">
            <Users class="w-5 h-5" />
          </ButtonIcon>
          <ButtonIcon @click="openSendModal(item)" title="Send Campaign" :disabled="!item.recipients_count || item.recipients_count === 0">
            <Send class="w-5 h-5" />
          </ButtonIcon>
          <ButtonIcon variant="primary" @click="openEditModal(item)" title="Edit Campaign">
            <Edit class="w-5 h-5" />
          </ButtonIcon>
          <ButtonIcon variant="danger" @click="confirmDelete(item)" title="Delete Campaign">
            <Trash class="w-5 h-5" />
          </ButtonIcon>
        </div>
      </template>
    </DataTable>

    <!-- Campaign Modal -->
    <FormModal v-model="showModal" :title="isEdit ? 'Edit Campaign' : 'Create Campaign'" @submit="handleSubmit" submit-text="Save">
      <template #default="{ loading }">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Campaign Name *</label>
            <input v-model="form.name" type="text" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" placeholder="e.g., Holiday Promo" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Type *</label>
            <select v-model="form.type" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
              <option value="whatsapp">WhatsApp</option>
              <option value="email">Email</option>
              <option value="sms">SMS</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Start Date *</label>
              <input v-model="form.start_date" type="date" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">End Date *</label>
              <input v-model="form.end_date" type="date" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" />
            </div>
          </div>
        </div>
      </template>
    </FormModal>

    <!-- Recipients Modal -->
    <div v-if="showRecipientsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="showRecipientsModal = false">
      <div class="dark:bg-gray-800 bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
        <div class="p-6 border-b flex justify-between items-center">
          <h2 class="text-xl font-bold text-gray-900">Manage Recipients - {{ selectedCampaign?.name }}</h2>
          <button @click="showRecipientsModal = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
        <div class="flex-1 overflow-y-auto p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Add by Customer Segment</label>
            <div class="flex gap-2">
              <select v-model="selectedSegment" class="flex-1 border border-gray-300 rounded-lg px-4 py-2">
                <option value="all">All Customers</option>
                <option value="vip">VIP Customers</option>
                <option value="inactive">Inactive (30+ days)</option>
              </select>
              <ButtonPrimary @click="addBySegment" :loading="addingSegment">Add Segment</ButtonPrimary>
            </div>
            <p class="text-xs text-gray-500 mt-1">This will add all customers from the selected segment to the campaign.</p>
          </div>
          
          <div class="border-t pt-4">
            <h3 class="font-medium text-gray-900 mb-2">Current Recipients ({{ recipients.length }})</h3>
            <div v-if="recipients.length === 0" class="text-center py-8 text-gray-500">No recipients yet</div>
            <div v-else class="space-y-2 max-h-60 overflow-y-auto">
              <div v-for="customer in recipients" :key="customer.id" class="flex items-center justify-between p-2 bg-gray-50 rounded">
                <div>
                  <div class="font-medium">{{ customer.name }}</div>
                  <div class="text-xs text-gray-500">{{ customer.phone }}</div>
                </div>
                <ButtonIcon variant="danger" size="sm" @click="removeRecipient(customer.id)">
                  <Trash class="w-4 h-4" />
                </ButtonIcon>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Send Campaign Modal -->
    <div v-if="showSendModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="showSendModal = false">
      <div class="dark:bg-gray-800 bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
        <div class="p-6 border-b flex justify-between items-center">
          <h2 class="text-xl font-bold text-gray-900">Send Campaign - {{ selectedCampaign?.name }}</h2>
          <button @click="showSendModal = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
        <div class="flex-1 overflow-y-auto p-6 space-y-4">
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start gap-3">
              <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div class="text-sm text-blue-800">
                <p class="font-medium mb-1">Campaign Details</p>
                <p>Type: <strong class="capitalize">{{ selectedCampaign?.type }}</strong></p>
                <p>Recipients: <strong>{{ recipientsCount }}</strong> customers will receive this message</p>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Message *</label>
            <textarea v-model="sendMessage" rows="6" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" placeholder="Hi {name}, check out our {campaign} promotion!"></textarea>
            <p class="text-xs text-gray-500 mt-1">
              Use <code class="bg-gray-100 px-1 rounded">{name}</code> for customer name and <code class="bg-gray-100 px-1 rounded">{campaign}</code> for campaign name.
            </p>
          </div>

          <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="font-medium text-gray-900 mb-2">Preview:</h4>
            <div class="text-sm text-gray-700 whitespace-pre-wrap">{{ previewMessage }}</div>
          </div>
        </div>
        <div class="p-6 border-t flex justify-end gap-3">
          <ButtonSecondary @click="showSendModal = false">Cancel</ButtonSecondary>
          <ButtonPrimary @click="sendCampaign" :loading="sending">
            <Send class="w-4 h-4" />
            Send to {{ recipientsCount }} Recipients
          </ButtonPrimary>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import axios from 'axios';
import DataTable from '../../components/tables/DataTable.vue';
import FormModal from '../../components/forms/FormModal.vue';
import ButtonPrimary from '../../components/ui/ButtonPrimary.vue';
import ButtonSecondary from '../../components/ui/ButtonSecondary.vue';
import ButtonIcon from '../../components/ui/ButtonIcon.vue';
import AlertService from '../../components/alerts/AlertService.js';
import { Megaphone, Edit, Trash, Users, Send } from 'lucide-vue-next';

const tableRef = ref(null);
const showModal = ref(false);
const showRecipientsModal = ref(false);
const showSendModal = ref(false);
const isEdit = ref(false);
const selectedCampaign = ref(null);
const selectedSegment = ref('all');
const addingSegment = ref(false);
const sending = ref(false);
const recipients = ref([]);
const recipientsCount = ref(0);
const sendMessage = ref('');

const columns = {
  name: { label: 'Campaign', sortable: true },
  dates: { label: 'Duration', sortable: false },
  recipients: { label: 'Recipients', sortable: false }
};

const form = reactive({
  name: '',
  type: 'whatsapp',
  start_date: '',
  end_date: ''
});

const previewMessage = computed(() => {
  if (!sendMessage.value) return '';
  return sendMessage.value
    .replace(/{name}/g, 'John Doe')
    .replace(/{campaign}/g, selectedCampaign.value?.name || 'Campaign');
});

async function fetchCampaigns(params) {
  const token = localStorage.getItem('token');
  
  try {
    const response = await axios.get('/api/v1/campaigns', { 
      headers: { Authorization: `Bearer ${token}` }, 
      params 
    });
    
    const result = response.data;
    const paginator = result.data;
    const campaigns = paginator.data || [];
    
    return { 
      data: campaigns, 
      current_page: paginator.current_page || 1, 
      last_page: paginator.last_page || 1, 
      from: paginator.from || 1, 
      to: paginator.to || campaigns.length, 
      total: paginator.total || campaigns.length 
    };
  } catch (error) {
    console.error('Failed to fetch campaigns:', error);
    return { data: [], current_page: 1, last_page: 1, from: 0, to: 0, total: 0 };
  }
}

function openCreateModal() {
  isEdit.value = false;
  selectedCampaign.value = null;
  Object.assign(form, { name: '', type: 'whatsapp', start_date: '', end_date: '' });
  showModal.value = true;
}

function openEditModal(campaign) {
  isEdit.value = true;
  selectedCampaign.value = campaign;
  Object.assign(form, {
    name: campaign.name,
    type: campaign.type,
    start_date: campaign.start_date?.split('T')[0] || '',
    end_date: campaign.end_date?.split('T')[0] || ''
  });
  showModal.value = true;
}

async function openRecipientsModal(campaign) {
  selectedCampaign.value = campaign;
  showRecipientsModal.value = true;
  await loadRecipients();
}

async function openSendModal(campaign) {
  selectedCampaign.value = campaign;
  showSendModal.value = true;
  sendMessage.value = `Hi {name},\n\nCheck out our {campaign} promotion! Don't miss out on this special offer.\n\nReply STOP to unsubscribe.`;
  await loadRecipientsCount();
}

async function loadRecipients() {
  try {
    const token = localStorage.getItem('token');
    const response = await axios.get(`/api/v1/campaigns/${selectedCampaign.value.id}/recipients`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    recipients.value = response.data.data || [];
    recipientsCount.value = recipients.value.length;
  } catch (e) {
    console.error('Failed to load recipients:', e);
    recipients.value = [];
    recipientsCount.value = 0;
  }
}

async function loadRecipientsCount() {
  try {
    const token = localStorage.getItem('token');
    const response = await axios.get(`/api/v1/campaigns/${selectedCampaign.value.id}/recipients`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    recipientsCount.value = (response.data.data || []).length;
  } catch (e) {
    console.error('Failed to load recipients count:', e);
    recipientsCount.value = 0;
  }
}

async function addBySegment() {
  addingSegment.value = true;
  try {
    const token = localStorage.getItem('token');
    const response = await axios.get('/api/v1/campaign-segments', {
      headers: { Authorization: `Bearer ${token}` },
      params: { segment: selectedSegment.value }
    });
    const customers = response.data.data;
    
    for (const customer of customers) {
      await axios.post(`/api/v1/campaigns/${selectedCampaign.value.id}/recipients`, {
        customer_id: customer.id
      }, {
        headers: { Authorization: `Bearer ${token}` }
      });
    }
    
    AlertService.success(`Added ${customers.length} recipients from ${selectedSegment.value} segment`);
    await loadRecipients();
    tableRef.value?.refresh();
  } catch (err) {
    AlertService.error('Failed to add recipients');
  } finally {
    addingSegment.value = false;
  }
}

async function removeRecipient(customerId) {
  try {
    const token = localStorage.getItem('token');
    await axios.delete(`/api/v1/campaigns/${selectedCampaign.value.id}/recipients/${customerId}`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    await loadRecipients();
    tableRef.value?.refresh();
    AlertService.success('Recipient removed');
  } catch (err) {
    AlertService.error('Failed to remove recipient');
  }
}

async function sendCampaign() {
  if (!sendMessage.value.trim()) {
    AlertService.error('Please enter a message');
    return;
  }
  
  sending.value = true;
  try {
    const token = localStorage.getItem('token');
    const response = await axios.post(`/api/v1/campaigns/${selectedCampaign.value.id}/send`, {
      message: sendMessage.value
    }, {
      headers: { Authorization: `Bearer ${token}` }
    });
    
    AlertService.success(response.data.message);
    showSendModal.value = false;
    tableRef.value?.refresh();
  } catch (err) {
    AlertService.error(err.response?.data?.message || 'Failed to send campaign');
  } finally {
    sending.value = false;
  }
}

async function handleSubmit({ setLoading, setError }) {
  try {
    const token = localStorage.getItem('token');
    if (isEdit.value) {
      await axios.put(`/api/v1/campaigns/${selectedCampaign.value.id}`, form, { headers: { Authorization: `Bearer ${token}` } });
      AlertService.success('Campaign updated successfully');
    } else {
      await axios.post('/api/v1/campaigns', form, { headers: { Authorization: `Bearer ${token}` } });
      AlertService.success('Campaign created successfully');
    }
    showModal.value = false;
    tableRef.value?.refresh();
  } catch (err) {
    setError(err.response?.data?.message || 'Failed to save campaign');
  } finally {
    setLoading(false);
  }
}

async function confirmDelete(campaign) {
  if (!await AlertService.deleteConfirm(`"${campaign.name}"`)) return;
  try {
    const token = localStorage.getItem('token');
    await axios.delete(`/api/v1/campaigns/${campaign.id}`, { headers: { Authorization: `Bearer ${token}` } });
    AlertService.success('Campaign deleted successfully');
    tableRef.value?.refresh();
  } catch (e) {
    AlertService.error('Failed to delete campaign');
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}
</script>
