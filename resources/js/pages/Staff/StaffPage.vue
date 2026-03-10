<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Staff Management</h1>
        <p class="text-gray-500 mt-1">Manage staff accounts and shifts</p>
      </div>
      <div class="flex gap-2">
        <ButtonPrimary @click="openStartShiftModal" class="bg-green-600 hover:bg-green-700">
          <Play class="w-4 h-4" />
          Start Shift
        </ButtonPrimary>
        <ButtonPrimary @click="openEndShiftModal" class="bg-red-600 hover:bg-red-700" :disabled="activeShifts.length === 0">
          <Clock class="w-4 h-4" />
          End Shift
        </ButtonPrimary>
        <ButtonSecondary @click="showShiftsModal = true">
          <Clock class="w-4 h-4" />
          View Shifts
        </ButtonSecondary>
        <ButtonPrimary @click="openCreateModal">
          <Plus class="w-4 h-4" />
          Add Staff
        </ButtonPrimary>
      </div>
    </div>

    <!-- Active Shifts Banner -->
    <div v-if="activeShifts.length > 0" class="bg-green-50 border border-green-200 rounded-xl p-4">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
          <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
          <h3 class="font-semibold text-green-900">Active Shifts Today</h3>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="shift in activeShifts" :key="shift.id" class="bg-white rounded-lg p-4 border border-green-200">
          <div class="flex justify-between items-start mb-2">
            <div>
              <div class="font-medium">{{ shift.staff.name }}</div>
              <div class="text-sm text-gray-500">Started: {{ formatDateTime(shift.start_time) }}</div>
            </div>
            <ButtonSecondary size="sm" @click="openEndShiftModal(shift)" class="text-red-600 hover:text-red-700">
              End Shift
            </ButtonSecondary>
          </div>
          <div class="text-sm text-green-600">Opening: Rp {{ formatPrice(shift.opening_cash) }}</div>
        </div>
      </div>
    </div>

    <!-- Staff Table -->
    <DataTable ref="tableRef" :columns="columns" :fetch-data="fetchStaff" search-placeholder="Search staff..." empty-message="No staff found">
      <template #cell-name="{ item }">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold">
            {{ item.name.charAt(0) }}
          </div>
          <div>
            <div class="font-medium">{{ item.name }}</div>
            <div class="text-xs text-gray-500">{{ item.email }}</div>
          </div>
        </div>
      </template>

      <template #cell-role="{ item }">
        <span :class="roleBadgeClass(item.role)" class="px-2 py-1 text-xs font-medium rounded-full capitalize">
          {{ item.role }}
        </span>
      </template>

      <template #cell-is_active="{ item }">
        <span :class="item.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 py-1 text-xs font-medium rounded-full">
          {{ item.is_active ? 'Active' : 'Inactive' }}
        </span>
      </template>

      <template #actions="{ item }">
        <div class="flex justify-end gap-2">
          <ButtonIcon variant="primary" @click="openEditModal(item)" title="Edit Staff">
            <Edit class="w-5 h-5" />
          </ButtonIcon>
          <ButtonIcon variant="danger" @click="confirmDelete(item)" title="Delete Staff">
            <Trash class="w-5 h-5" />
          </ButtonIcon>
        </div>
      </template>
    </DataTable>

    <!-- Staff Modal -->
    <FormModal v-model="showModal" :title="isEdit ? 'Edit Staff' : 'Add Staff'" @submit="handleSubmit" submit-text="Save">
      <template #default="{ loading }">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
            <input v-model="form.name" type="text" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" placeholder="Full name" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
            <input v-model="form.email" type="email" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" placeholder="email@example.com" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Password <span v-if="!isEdit">*</span></label>
            <input v-model="form.password" :type="isEdit ? 'password' : 'password'" :required="!isEdit" :placeholder="isEdit ? 'Leave blank to keep current' : 'Minimum 6 characters'" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
            <select v-model="form.role" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
              <option value="owner">Owner</option>
              <option value="manager">Manager</option>
              <option value="cashier">Cashier</option>
              <option value="staff">Staff</option>
            </select>
          </div>
          <div>
            <label class="flex items-center">
              <input v-model="form.is_active" type="checkbox" class="w-4 h-4 text-indigo-600 rounded" />
              <span class="ml-2 text-sm text-gray-700">Active</span>
            </label>
          </div>
        </div>
      </template>
    </FormModal>

    <!-- Shifts Modal -->
    <div v-if="showShiftsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="showShiftsModal = false">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
        <div class="p-6 border-b flex justify-between items-center">
          <h2 class="text-xl font-bold text-gray-900">Shift History</h2>
          <button @click="showShiftsModal = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
          <DataTable ref="shiftsTableRef" :columns="shiftColumns" :fetch-data="fetchShifts" search-placeholder="" empty-message="No shifts found" />
        </div>
      </div>
    </div>

    <!-- Start Shift Modal -->
    <FormModal v-model="showStartShiftModal" title="Start New Shift" @submit="startShift" submit-text="Start Shift">
      <template #default>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Staff *</label>
            <select v-model="startShiftForm.staff_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
              <option value="">Select Staff</option>
              <option v-for="staff in staffList" :key="staff.id" :value="staff.id">
                {{ staff.name }} ({{ staff.role }})
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Opening Cash (Rp) *</label>
            <input
              :value="formatNumber(startShiftForm.opening_cash)"
              @input="startShiftForm.opening_cash = ($event.target.value || '').toString().replace(/[^0-9]/g, '')"
              @blur="startShiftForm.opening_cash = formatNumber(parseNumber(startShiftForm.opening_cash || 0))"
              type="text"
              required
              min="0"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
              placeholder="e.g., 1.000.000"
            />
            <p class="text-xs text-gray-500 mt-1">Initial cash in the drawer at shift start</p>
          </div>
          <div v-if="activeShifts.length > 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
            <div class="flex items-start gap-2">
              <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              <div class="text-sm text-yellow-800">
                <p class="font-medium">Active Shifts: {{ activeShifts.length }}</p>
                <p>{{ activeShifts.map(s => s.staff.name).join(', ') }}</p>
              </div>
            </div>
          </div>
        </div>
      </template>
    </FormModal>

    <!-- End Shift Modal -->
    <FormModal v-model="showEndShiftModal" title="End Shift" @submit="endShift" submit-text="End Shift">
      <template #default>
        <div class="space-y-4">
          <div v-if="activeShifts.length === 0" class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
            <p class="text-red-800 font-medium">No active shifts to end</p>
          </div>
          <div v-else>
            <label class="block text-sm font-medium text-gray-700 mb-2">Shift *</label>
            <select v-model="endShiftForm.shift_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500">
              <option value="">Select Shift to End</option>
              <option v-for="shift in activeShifts" :key="shift.id" :value="shift.id">
                {{ shift.staff.name }} - Started {{ formatDateTime(shift.start_time) }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Closing Cash (Rp) *</label>
            <input
              :value="formatNumber(endShiftForm.closing_cash)"
              @input="endShiftForm.closing_cash = ($event.target.value || '').toString().replace(/[^0-9]/g, '')"
              @blur="endShiftForm.closing_cash = formatNumber(parseNumber(endShiftForm.closing_cash || 0))"
              type="text"
              required
              min="0"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500"
              placeholder="e.g., 2.500.000"
            />
            <p class="text-xs text-gray-500 mt-1">Actual cash count in the drawer at shift end</p>
          </div>
          <div v-if="selectedShiftInfo" class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h4 class="font-medium text-gray-900 mb-3">Shift Summary</h4>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-600">Opening Cash:</span>
                <span class="font-medium">Rp {{ formatPrice(selectedShiftInfo.opening_cash) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Expected Cash:</span>
                <span class="font-medium">Rp {{ formatPrice(expectedCash) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Closing Cash:</span>
                <span class="font-medium">Rp {{ formatPrice(parseNumber(endShiftForm.closing_cash)) }}</span>
              </div>
              <div class="border-t pt-2 flex justify-between font-medium" :class="cashDifference >= 0 ? 'text-green-600' : 'text-red-600'">
                <span>Difference:</span>
                <span>{{ cashDifference >= 0 ? '+' : '' }}Rp {{ formatPrice(cashDifference) }}</span>
              </div>
            </div>
          </div>
        </div>
      </template>
    </FormModal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import DataTable from '../../components/tables/DataTable.vue';
import FormModal from '../../components/forms/FormModal.vue';
import ButtonPrimary from '../../components/ui/ButtonPrimary.vue';
import ButtonSecondary from '../../components/ui/ButtonSecondary.vue';
import ButtonIcon from '../../components/ui/ButtonIcon.vue';
import AlertService from '../../components/alerts/AlertService.js';
import { Plus, Edit, Trash, Clock, Play } from 'lucide-vue-next';

const tableRef = ref(null);
const shiftsTableRef = ref(null);
const showModal = ref(false);
const showShiftsModal = ref(false);
const showStartShiftModal = ref(false);
const showEndShiftModal = ref(false);
const isEdit = ref(false);
const selectedStaff = ref(null);
const selectedShift = ref(null);
const activeShifts = ref([]);
const staffList = ref([]);

const startShiftForm = reactive({
  staff_id: '',
  opening_cash: ''
});

const endShiftForm = reactive({
  shift_id: '',
  closing_cash: ''
});

const selectedShiftInfo = computed(() => {
  if (!endShiftForm.shift_id) return null;
  return activeShifts.value.find(s => s.id === parseInt(endShiftForm.shift_id)) || null;
});

const expectedCash = ref(0);

const cashDifference = computed(() => {
  return parseNumber(endShiftForm.closing_cash) - expectedCash.value;
});

async function loadExpectedCash(shiftId) {
  try {
    // Fetch expected cash from orders in this shift
    const response = await axios.get(`/api/v1/shifts/${shiftId}/summary`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    expectedCash.value = response.data.data?.expected_cash || 0;
  } catch (e) {
    console.error('Failed to load expected cash:', e);
    expectedCash.value = 0;
  }
}

function formatNumber(num) {
  if (num === null || num === undefined || num === '') return '';
  return new Intl.NumberFormat('id-ID').format(num);
}

function parseNumber(str) {
  if (!str) return 0;
  return parseInt(str.toString().replace(/[^0-9]/g, ''), 10) || 0;
}

const columns = {
  name: { label: 'Staff', sortable: true },
  role: { label: 'Role', sortable: true },
  is_active: { label: 'Status', sortable: false }
};

const shiftColumns = {
  staff_name: { label: 'Staff', sortable: true },
  start_time: { label: 'Start', sortable: true },
  end_time: { label: 'End', sortable: false },
  duration: { label: 'Duration', sortable: false },
  cash: { label: 'Cash', sortable: false }
};

const form = reactive({
  name: '',
  email: '',
  password: '',
  role: 'cashier',
  is_active: true
});

const token = localStorage.getItem('token');

async function fetchStaff(params) {
  const response = await axios.get('/api/v1/staff', { 
    headers: { Authorization: `Bearer ${token}` }, 
    params 
  });
  
  const result = response.data;
  const paginator = result.data;
  const staff = paginator.data || [];
  
  return { 
    data: staff, 
    current_page: paginator.current_page || 1, 
    last_page: paginator.last_page || 1, 
    from: paginator.from || 1, 
    to: paginator.to || staff.length, 
    total: paginator.total || staff.length 
  };
}

async function fetchShifts(params) {
  const response = await axios.get('/api/v1/shifts', {
    headers: { Authorization: `Bearer ${token}` },
    params
  });

  const result = response.data;
  const paginator = result.data;
  const shifts = paginator.data || [];

  const formattedShifts = shifts.map(shift => ({
    ...shift,
    staff_name: shift.staff?.name || '-',
    start_time: formatDateTime(shift.start_time),
    end_time: shift.end_time ? formatDateTime(shift.end_time) : '-',
    duration: shift.end_time ? formatDuration(shift.start_time, shift.end_time) : 'Active',
    cash: `Open: ${formatPrice(shift.opening_cash)} | Close: ${formatPrice(shift.closing_cash || 0)} | Diff: ${formatPrice(shift.cash_difference || 0)}`
  }));

  return {
    data: formattedShifts,
    current_page: paginator.current_page || 1,
    last_page: paginator.last_page || 1,
    from: paginator.from || 1,
    to: paginator.to || shifts.length,
    total: paginator.total || shifts.length
  };
}

async function loadActiveShifts() {
  try {
    const response = await axios.get('/api/v1/shifts/active', {
      headers: { Authorization: `Bearer ${token}` }
    });
    activeShifts.value = response.data.data || [];
  } catch (e) {
    console.error('Failed to load active shifts:', e);
  }
}

async function loadStaffList() {
  try {
    const response = await axios.get('/api/v1/staff', {
      headers: { Authorization: `Bearer ${token}` }
    });
    const result = response.data.data;
    staffList.value = result.data || [];
    
    // Auto-select first staff if only one
    if (staffList.value.length === 1 && !startShiftForm.staff_id) {
      startShiftForm.staff_id = staffList.value[0].id;
    }
  } catch (e) {
    console.error('Failed to load staff:', e);
  }
}

function openStartShiftModal() {
  startShiftForm.staff_id = '';
  startShiftForm.opening_cash = 0;
  loadStaffList();
  showStartShiftModal.value = true;
}

async function startShift({ setLoading, setError }) {
  try {
    const payload = {
      staff_id: parseInt(startShiftForm.staff_id),
      opening_cash: parseNumber(startShiftForm.opening_cash)
    };
    
    await axios.post('/api/v1/shifts/start', payload, {
      headers: { Authorization: `Bearer ${token}` }
    });
    
    AlertService.success('Shift started successfully!');
    showStartShiftModal.value = false;
    loadActiveShifts();
  } catch (err) {
    setError(err.response?.data?.message || 'Failed to start shift');
  } finally {
    setLoading(false);
  }
}

function openEndShiftModal() {
  endShiftForm.shift_id = '';
  endShiftForm.closing_cash = '';
  expectedCash.value = 0;
  showEndShiftModal.value = true;
}

// Watch for shift selection change
watch(() => endShiftForm.shift_id, async (newShiftId) => {
  if (newShiftId) {
    await loadExpectedCash(newShiftId);
  } else {
    expectedCash.value = 0;
  }
});

async function endShift({ setLoading, setError }) {
  if (!endShiftForm.shift_id) {
    setError('Please select a shift');
    return;
  }

  try {
    const payload = {
      shift_id: parseInt(endShiftForm.shift_id),
      closing_cash: parseNumber(endShiftForm.closing_cash)
    };

    await axios.post(`/api/v1/shifts/${payload.shift_id}/end`, payload, {
      headers: { Authorization: `Bearer ${token}` }
    });

    AlertService.success('Shift ended successfully!');
    showEndShiftModal.value = false;
    loadActiveShifts();
  } catch (err) {
    setError(err.response?.data?.message || 'Failed to end shift');
  } finally {
    setLoading(false);
  }
}

function roleBadgeClass(role) {
  const classes = {
    owner: 'bg-purple-100 text-purple-800',
    manager: 'bg-blue-100 text-blue-800',
    cashier: 'bg-green-100 text-green-800',
    staff: 'bg-gray-100 text-gray-800'
  };
  return classes[role] || classes.staff;
}

function openCreateModal() {
  isEdit.value = false;
  selectedStaff.value = null;
  Object.assign(form, { name: '', email: '', password: '', role: 'cashier', is_active: true });
  showModal.value = true;
}

function openEditModal(staff) {
  isEdit.value = true;
  selectedStaff.value = staff;
  Object.assign(form, {
    name: staff.name,
    email: staff.email,
    password: '',
    role: staff.role,
    is_active: staff.is_active
  });
  showModal.value = true;
}

async function handleSubmit({ setLoading, setError }) {
  try {
    const payload = { ...form };
    
    // Don't send empty password on edit
    if (isEdit.value && !payload.password) {
      delete payload.password;
    }
    
    if (isEdit.value) {
      await axios.put(`/api/v1/staff/${selectedStaff.value.id}`, payload, {
        headers: { Authorization: `Bearer ${token}` }
      });
      AlertService.success('Staff updated successfully');
    } else {
      await axios.post('/api/v1/staff', payload, {
        headers: { Authorization: `Bearer ${token}` }
      });
      AlertService.success('Staff created successfully');
    }
    showModal.value = false;
    tableRef.value?.refresh();
  } catch (err) {
    setError(err.response?.data?.message || 'Failed to save staff');
  } finally {
    setLoading(false);
  }
}

async function confirmDelete(staff) {
  if (!await AlertService.deleteConfirm(`"${staff.name}"`)) return;
  try {
    await axios.delete(`/api/v1/staff/${staff.id}`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    AlertService.success('Staff deleted successfully');
    tableRef.value?.refresh();
  } catch (e) {
    AlertService.error('Failed to delete staff');
  }
}

function formatPrice(price) {
  return new Intl.NumberFormat('id-ID').format(price);
}

function formatDateTime(dateStr) {
  if (!dateStr) return '-';
  const date = new Date(dateStr);
  // Format: "10 Mar, 08:30" (day month, hour:minute)
  return date.toLocaleString('id-ID', { 
    day: 'numeric', 
    month: 'short', 
    hour: '2-digit', 
    minute: '2-digit' 
  });
}

function formatDuration(startStr, endStr) {
  if (!startStr || !endStr) return '-';
  const start = new Date(startStr);
  const end = new Date(endStr);
  const diffMs = end - start;
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
  const diffMinutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
  
  if (diffHours >= 24) {
    const days = Math.floor(diffHours / 24);
    const remainingHours = diffHours % 24;
    return `${days}d ${remainingHours}h ${diffMinutes}m`;
  }
  return `${diffHours}h ${diffMinutes}m`;
}

function calculateDifference() {
  if (!selectedShift.value || !endShiftForm.closing_cash) return 0;
  const closing = parseNumber(endShiftForm.closing_cash);
  const opening = parseNumber(selectedShift.value.opening_cash);
  return closing - opening;
}

onMounted(() => {
  loadActiveShifts();
});
</script>
