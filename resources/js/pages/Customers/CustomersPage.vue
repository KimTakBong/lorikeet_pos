<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900">Customers</h1>
      <ButtonPrimary @click="openCreateModal">
        <UserPlus class="w-4 h-4" />
        Add Customer
      </ButtonPrimary>
    </div>

    <DataTable ref="tableRef" :columns="columns" :fetch-data="fetchCustomers" search-placeholder="Search customers..." empty-message="No customers found">
      <template #cell-name="{ item }">
        <div class="font-medium text-gray-900">{{ item.name }}</div>
        <div class="text-xs text-gray-500">{{ item.phone }}</div>
        <div v-if="item.email" class="text-xs text-gray-400">{{ item.email }}</div>
      </template>

      <template #cell-membership="{ item }">
        <div v-if="item.membership">
          <div class="text-sm font-medium">{{ item.membership.tier?.name || 'Member' }}</div>
          <div class="text-xs text-gray-500">{{ item.membership.current_points || 0 }} pts</div>
        </div>
        <div v-else class="text-gray-400 text-xs">-</div>
      </template>

      <template #cell-stats="{ item }">
        <div class="text-sm">
          <div class="font-medium">Rp {{ formatPrice(item._stats?.total_spent || 0) }}</div>
          <div class="text-xs text-gray-500">{{ item._stats?.total_orders || 0 }} orders</div>
        </div>
      </template>

      <template #actions="{ item }">
        <div class="flex justify-end gap-2">
          <ButtonIcon variant="primary" @click="openEditModal(item)" title="Edit Customer">
            <Edit class="w-5 h-5" />
          </ButtonIcon>
          <ButtonIcon variant="danger" @click="confirmDelete(item)" title="Delete Customer">
            <Trash class="w-5 h-5" />
          </ButtonIcon>
        </div>
      </template>
    </DataTable>

    <!-- Customer Modal -->
    <FormModal v-model="showModal" :title="isEdit ? 'Edit Customer' : 'Create Customer'" @submit="handleSubmit" submit-text="Save">
      <template #default="{ loading }">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
            <input v-model="form.name" type="text" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" placeholder="e.g., John Doe" />
          </div>
          <div>
            <PhoneInput 
              v-model="form.phone" 
              @update:valid="form.phoneValid = $event"
              label="Phone Number *" 
              placeholder="Phone number"
              :required="true"
              :default-country="'62'"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
            <input v-model="form.email" type="email" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" placeholder="e.g., john@example.com" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Birthday</label>
            <input v-model="form.birthday" type="date" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" />
          </div>
        </div>
      </template>
    </FormModal>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';
import DataTable from '../../components/tables/DataTable.vue';
import FormModal from '../../components/forms/FormModal.vue';
import PhoneInput from '../../components/forms/PhoneInput.vue';
import ButtonPrimary from '../../components/ui/ButtonPrimary.vue';
import ButtonIcon from '../../components/ui/ButtonIcon.vue';
import AlertService from '../../components/alerts/AlertService.js';
import { UserPlus, Edit, Trash } from 'lucide-vue-next';

const tableRef = ref(null);
const showModal = ref(false);
const isEdit = ref(false);
const selectedCustomer = ref(null);

const columns = {
  name: { label: 'Customer', sortable: true },
  membership: { label: 'Membership', sortable: false },
  stats: { label: 'Total Spent', sortable: false }
};

const form = reactive({
  name: '',
  phone: '',
  phoneValid: false,
  email: '',
  birthday: ''
});

async function fetchCustomers(params) {
  const token = localStorage.getItem('token');
  
  try {
    const response = await axios.get('/api/v1/customers', { 
      headers: { Authorization: `Bearer ${token}` }, 
      params 
    });
    
    const result = response.data;
    let customers = [];
    let paginator = { current_page: 1, last_page: 1, from: 1, to: 0, total: 0 };
    
    if (result.data && Array.isArray(result.data)) {
      if (result.data.current_page) {
        paginator = result.data;
        customers = paginator.data || [];
      } else {
        customers = result.data;
        paginator = { current_page: 1, last_page: 1, from: 1, to: customers.length, total: customers.length };
      }
    }
    
    const customersWithStats = customers.map(customer => ({
      ...customer,
      _stats: { total_spent: 0, total_orders: 0, last_purchase_at: null }
    }));
    
    return { data: customersWithStats, current_page: paginator.current_page, last_page: paginator.last_page, from: paginator.from, to: paginator.to, total: paginator.total };
  } catch (error) {
    console.error('Failed to fetch customers:', error);
    return { data: [], current_page: 1, last_page: 1, from: 0, to: 0, total: 0 };
  }
}

function openCreateModal() {
  isEdit.value = false;
  selectedCustomer.value = null;
  Object.assign(form, { name: '', phone: '', email: '', birthday: '' });
  showModal.value = true;
}

function openEditModal(customer) {
  isEdit.value = true;
  selectedCustomer.value = customer;
  Object.assign(form, { name: customer.name, phone: customer.phone, email: customer.email || '', birthday: customer.birthday || '' });
  showModal.value = true;
}

async function handleSubmit({ setLoading, setError }) {
  // Validate phone first
  if (!form.phone || !form.phoneValid) {
    setError('Please enter a valid phone number with country code (e.g., 628123456789)');
    return;
  }
  
  // Extra check: phone must not start with 0
  if (form.phone.startsWith('0')) {
    setError('Phone number cannot start with 0. Use country code instead (e.g., 62 for Indonesia).');
    return;
  }
  
  try {
    const token = localStorage.getItem('token');
    const payload = { 
      name: form.name,
      phone: form.phone,
      email: form.email || null,
      birthday: form.birthday || null
    };
    
    if (isEdit.value) {
      await axios.put(`/api/v1/customers/${selectedCustomer.value.id}`, payload, { headers: { Authorization: `Bearer ${token}` } });
      AlertService.success('Customer updated successfully');
    } else {
      await axios.post('/api/v1/customers', payload, { headers: { Authorization: `Bearer ${token}` } });
      AlertService.success('Customer created successfully');
    }
    showModal.value = false;
    tableRef.value?.refresh();
  } catch (err) {
    setError(err.response?.data?.message || 'Failed to save customer');
  } finally { setLoading(false); }
}

async function confirmDelete(customer) {
  if (!await AlertService.deleteConfirm(`"${customer.name}"`)) return;
  try {
    const token = localStorage.getItem('token');
    await axios.delete(`/api/v1/customers/${customer.id}`, { headers: { Authorization: `Bearer ${token}` } });
    AlertService.success('Customer deleted successfully');
    tableRef.value?.refresh();
  } catch (e) { AlertService.error('Failed to delete customer'); }
}

function formatPrice(price) { return new Intl.NumberFormat('id-ID').format(price); }
</script>
