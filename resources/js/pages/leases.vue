<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import axios from 'axios';

const page = usePage<any>();
const props = page.props as any;

// ---------- Helpers ----------
const formatDate = (dateString: string | null) => {
    if (!dateString) return '—';
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric',
    });
};

const formatCurrency = (amount: number | string | null): string => {
    const num = Number(amount);
    return `RM ${(isNaN(num) ? 0 : num).toFixed(2)}`;
};

// ---------- Search ----------
const search = ref(props.filters?.search || '');
let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/leases', { search: value }, { preserveState: true, preserveScroll: true });
    }, 300);
});

// ---------- Create Lease Modal ----------
const showCreateModal = ref(false);
const createForm = useForm({
    car_id: '',
    driver_id: '',
    start_date: '',
    end_date: '',
    monthly_payment: '',
    down_payment: '',
    status: 'active',
});
const submitCreate = () => {
    createForm.post('/admin/leases', {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

// ---------- Edit Lease ----------
const showEditModal = ref(false);
const editingLease = ref<any>(null);
const editProcessing = ref(false);
const editLease = (lease: any) => {
    editingLease.value = { ...lease, errors: {} };
    showEditModal.value = true;
};
const updateLease = () => {
    editProcessing.value = true;
    router.put(`/admin/leases/${editingLease.value.id}`, editingLease.value, {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            editingLease.value = null;
            editProcessing.value = false;
        },
        onError: (errors) => {
            editingLease.value.errors = errors;
            editProcessing.value = false;
        },
    });
};
const deleteLease = (id: number) => {
    if (confirm('Delete this lease?')) {
        router.delete(`/admin/leases/${id}`, { preserveScroll: true });
    }
};

// ---------- Statement Modal ----------
const showStatementModal = ref(false);
const selectedLeaseForStatement = ref<any>(null);
const statementData = ref<any>(null);
const loadingStatement = ref(false);

const openStatementModal = async (lease: any) => {
    selectedLeaseForStatement.value = lease;
    showStatementModal.value = true;
    loadingStatement.value = true;
    try {
        const response = await axios.get(`/api/lease/${lease.id}/statement`);
        statementData.value = response.data;
    } catch (error) {
        console.error('Failed to load statement', error);
    } finally {
        loadingStatement.value = false;
    }
};

const closeStatementModal = () => {
    showStatementModal.value = false;
    statementData.value = null;
    selectedLeaseForStatement.value = null;
};
</script>

<template>
    <AppSidebarLayout>
        <div class="p-6 md:p-8 bg-slate-50 min-h-screen">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Leases</h1>
                    <p class="text-sm text-slate-500 mt-1">Manage active and past vehicle leases.</p>
                </div>
                <button @click="showCreateModal = true" class="inline-flex items-center bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg font-medium text-sm shadow-sm transition-colors">
                    <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    New Lease
                </button>
            </div>

            <!-- Search -->
            <div class="mb-6 flex items-center">
                <div class="relative w-full md:w-96">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input v-model="search" type="text" placeholder="Search by vehicle or driver..." class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-full text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm bg-white" />
                </div>
            </div>

            <!-- Leases Table (without Payment Status column) -->
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50/80 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Vehicle</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Driver</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Start Date</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">End Date</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Monthly Payment</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="lease in props.leases" :key="lease.id" class="hover:bg-slate-50/50 transition-colors group">
                               <td class="px-6 py-4 align-top">
    <div class="font-semibold text-slate-900">{{ lease.car?.license_plate || '—' }}</div>
    <div class="text-xs text-slate-500">{{ lease.car?.make || '' }} {{ lease.car?.model || '' }}</div>
</td>
<td class="px-6 py-4 align-top">
    <div class="font-medium text-slate-900">{{ lease.driver?.name || '—' }}</div>
    <div class="text-xs text-slate-500">ID: {{ lease.driver?.driver_license || '—' }}</div>
</td>
                                <td class="px-6 py-4 align-top text-sm">{{ formatDate(lease.start_date) }}</td>
                                <td class="px-6 py-4 align-top text-sm">{{ formatDate(lease.end_date) }}</td>
                                <td class="px-6 py-4 align-top text-sm">RM{{ lease.monthly_payment }}</td>
                                <td class="px-6 py-4 align-top text-right w-1 whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <!-- Statement button -->
                                        <button @click="openStatementModal(lease)" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-md" title="Statement">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </button>
                                        <!-- Edit button -->
                                        <button @click="editLease(lease)" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-md" title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <!-- Delete button -->
                                        <button @click="deleteLease(lease.id)" class="p-1.5 text-red-600 hover:bg-red-50 rounded-md" title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!props.leases?.length">
                                <td colspan="6" class="px-6 py-8 text-center text-slate-500">No leases found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppSidebarLayout>

    <!-- Create Lease Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showCreateModal = false">
        <div class="fixed inset-0 bg-black/50 transition-opacity" @click="showCreateModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-xl shadow-xl overflow-hidden">
                <form @submit.prevent="submitCreate">
                    <div class="px-6 py-4">
                        <h3 class="text-xl font-semibold text-slate-900 mb-2">Create New Lease</h3>
                        <p class="text-sm text-slate-500 mb-4">Assign a vehicle to a driver.</p>

                        <div class="space-y-4">
                            <!-- Vehicle -->
                            <div>
                                <label class="modal-label">Vehicle <span class="text-red-500">*</span></label>
                                <select v-model="createForm.car_id" class="modal-input" required>
                                    <option value="">Select vehicle</option>
                                    <option v-for="car in props.cars" :key="car.id" :value="car.id">
                                        {{ car.license_plate }} – {{ car.year }} {{ car.make }} {{ car.model }}
                                    </option>
                                </select>
                                <p v-if="createForm.errors.car_id" class="text-sm text-red-600 mt-1">{{ createForm.errors.car_id }}</p>
                            </div>

                            <!-- Driver -->
                            <div>
                                <label class="modal-label">Driver <span class="text-red-500">*</span></label>
                                <select v-model="createForm.driver_id" class="modal-input" required>
                                    <option value="">Select driver</option>
                                    <option v-for="driver in props.drivers" :key="driver.id" :value="driver.id">
                                        {{ driver.name }} ({{ driver.driver_license }})
                                    </option>
                                </select>
                                <p v-if="createForm.errors.driver_id" class="text-sm text-red-600 mt-1">{{ createForm.errors.driver_id }}</p>
                            </div>

                            <!-- Start and End Date -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="modal-label">Start Date <span class="text-red-500">*</span></label>
                                    <input type="date" v-model="createForm.start_date" class="modal-input" required />
                                    <p v-if="createForm.errors.start_date" class="text-sm text-red-600 mt-1">{{ createForm.errors.start_date }}</p>
                                </div>
                                <div>
                                    <label class="modal-label">End Date</label>
                                    <input type="date" v-model="createForm.end_date" class="modal-input" />
                                    <p v-if="createForm.errors.end_date" class="text-sm text-red-600 mt-1">{{ createForm.errors.end_date }}</p>
                                </div>
                            </div>

                            <!-- Monthly Payment and Down Payment -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="modal-label">Monthly Payment (RM) <span class="text-red-500">*</span></label>
                                    <input type="number" step="0.01" min="0" v-model="createForm.monthly_payment" class="modal-input" required />
                                    <p v-if="createForm.errors.monthly_payment" class="text-sm text-red-600 mt-1">{{ createForm.errors.monthly_payment }}</p>
                                </div>
                                <div>
                                    <label class="modal-label">Down Payment (RM)</label>
                                    <input type="number" step="0.01" min="0" v-model="createForm.down_payment" class="modal-input" placeholder="0.00" />
                                    <p v-if="createForm.errors.down_payment" class="text-sm text-red-600 mt-1">{{ createForm.errors.down_payment }}</p>
                                </div>
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="modal-label">Status</label>
                                <select v-model="createForm.status" class="modal-input">
                                    <option value="active">Active</option>
                                    <option value="pending">Pending</option>
                                    <option value="ended">Ended</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-3 flex justify-end gap-3 border-t border-slate-200">
                        <button type="button" @click="showCreateModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 bg-white hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="createForm.processing" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm disabled:opacity-50">
                            {{ createForm.processing ? 'Saving...' : 'Create Lease' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Lease Modal -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showEditModal = false">
        <div class="fixed inset-0 bg-black/50 transition-opacity" @click="showEditModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-xl shadow-xl overflow-hidden">
                <form @submit.prevent="updateLease">
                    <div class="px-6 py-4">
                        <h3 class="text-xl font-semibold text-slate-900 mb-2">Edit Lease</h3>
                        <p class="text-sm text-slate-500 mb-4">
                            {{ editingLease?.car?.license_plate }} – {{ editingLease?.driver?.name }}
                        </p>

                        <div class="space-y-4">
                            <!-- Start and End Date -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="modal-label">Start Date <span class="text-red-500">*</span></label>
                                    <input type="date" v-model="editingLease.start_date" class="modal-input" required />
                                    <p v-if="editingLease.errors?.start_date" class="text-sm text-red-600 mt-1">{{ editingLease.errors.start_date }}</p>
                                </div>
                                <div>
                                    <label class="modal-label">End Date</label>
                                    <input type="date" v-model="editingLease.end_date" class="modal-input" />
                                    <p v-if="editingLease.errors?.end_date" class="text-sm text-red-600 mt-1">{{ editingLease.errors.end_date }}</p>
                                </div>
                            </div>

                            <!-- Monthly Payment and Down Payment -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="modal-label">Monthly Payment (RM) <span class="text-red-500">*</span></label>
                                    <input type="number" step="0.01" min="0" v-model="editingLease.monthly_payment" class="modal-input" required />
                                    <p v-if="editingLease.errors?.monthly_payment" class="text-sm text-red-600 mt-1">{{ editingLease.errors.monthly_payment }}</p>
                                </div>
                                <div>
                                    <label class="modal-label">Down Payment (RM)</label>
                                    <input type="number" step="0.01" min="0" v-model="editingLease.down_payment" class="modal-input" placeholder="0.00" />
                                    <p v-if="editingLease.errors?.down_payment" class="text-sm text-red-600 mt-1">{{ editingLease.errors.down_payment }}</p>
                                </div>
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="modal-label">Status</label>
                                <select v-model="editingLease.status" class="modal-input">
                                    <option value="active">Active</option>
                                    <option value="pending">Pending</option>
                                    <option value="ended">Ended</option>
                                </select>
                                <p v-if="editingLease.errors?.status" class="text-sm text-red-600 mt-1">{{ editingLease.errors.status }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-3 flex justify-end gap-3 border-t border-slate-200">
                        <button type="button" @click="showEditModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 bg-white hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="editProcessing" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm disabled:opacity-50">
                            {{ editProcessing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Statement Modal -->
    <div v-if="showStatementModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeStatementModal">
        <div class="fixed inset-0 bg-black/50 transition-opacity" @click="closeStatementModal"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-4xl bg-white rounded-xl shadow-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h3 class="text-xl font-semibold text-slate-900">Lease Statement</h3>
                    <button @click="closeStatementModal" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                    <div v-if="loadingStatement" class="text-center py-8">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-600 border-t-transparent"></div>
                        <p class="mt-2 text-slate-500">Loading statement...</p>
                    </div>

                    <div v-else-if="statementData" class="space-y-6">
                        <!-- Lease Summary -->
                        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                            <h4 class="font-semibold text-slate-900 mb-3">Lease Details</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <span class="text-slate-500 block">Car</span>
                                    <span class="font-medium">{{ statementData.lease.car.license_plate }} ({{ statementData.lease.car.make }} {{ statementData.lease.car.model }})</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block">Driver</span>
                                    <span class="font-medium">{{ statementData.lease.driver.name }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block">Start Date</span>
                                    <span class="font-medium">{{ formatDate(statementData.lease.start_date) }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block">End Date</span>
                                    <span class="font-medium">{{ formatDate(statementData.lease.end_date) }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block">Monthly Payment</span>
                                    <span class="font-medium">{{ formatCurrency(statementData.lease.monthly_payment) }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block">Down Payment</span>
                                    <span class="font-medium">{{ formatCurrency(statementData.down_payment) }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block">Total Lease Value</span>
                                    <span class="font-medium">{{ formatCurrency(statementData.total_lease_value) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Payment History -->
                        <div>
                            <h4 class="font-semibold text-slate-900 mb-3">Payment History</h4>
                            <div class="border border-slate-200 rounded-lg overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Paid Date</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Amount</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Due Date</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Timeliness</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="pmt in statementData.payments" :key="pmt.id">
                                            <td class="px-4 py-2">{{ formatDate(pmt.paid_date) }}</td>
                                            <td class="px-4 py-2">{{ formatCurrency(pmt.amount) }}</td>
                                            <td class="px-4 py-2">{{ formatDate(pmt.due_date) }}</td>
                                            <td class="px-4 py-2">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                    :class="{
                                                        'bg-emerald-100 text-emerald-700': pmt.timeliness === 'early',
                                                        'bg-blue-100 text-blue-700': pmt.timeliness === 'on-time',
                                                        'bg-red-100 text-red-700': pmt.timeliness === 'late'
                                                    }">
                                                    {{ pmt.timeliness }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr v-if="!statementData.payments.length">
                                            <td colspan="4" class="px-4 py-4 text-center text-slate-500">No payments recorded.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Outstanding Balance -->
                        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 flex justify-between items-center">
                            <span class="font-semibold text-slate-900">Outstanding Balance</span>
                            <span class="text-xl font-bold text-indigo-600">{{ formatCurrency(statementData.outstanding) }}</span>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3 border-t border-slate-200 flex justify-end">
                    <button @click="closeStatementModal" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 bg-white hover:bg-slate-50">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@reference "tailwindcss";
.modal-input { @apply mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border outline-none transition-colors; }
.modal-label { @apply block text-sm font-medium text-slate-700 mb-1; }
</style>