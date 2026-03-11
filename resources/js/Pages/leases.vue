<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import axios from 'axios';
import { 
    Loader2, X, CreditCard, Calendar, Clock, UploadCloud, CheckCircle2 
} from 'lucide-vue-next';

// 1. Define Props: This makes 'leases', 'cars', and 'drivers' reactive.
// When the database changes, Inertia sends new props and the UI updates.
const props = defineProps<{
    leases: any[];
    cars: any[];
    drivers: any[];
    filters: {
        search?: string;
        status?: string;
    };
}>();

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
const today = new Date().toISOString().split('T')[0];
const formattedToday = formatDate(today);
// ---------- Filters ----------
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');

let searchTimeout: ReturnType<typeof setTimeout>;
watch([search, statusFilter], ([newSearch, newStatus]) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/leases', { 
            search: newSearch, 
            status: newStatus 
        }, { preserveState: true, preserveScroll: true });
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

// ---------- Payment Modal ----------
const showPaymentModal = ref(false);
const selectedLease = ref<any>(null);
const paymentForm = useForm({
    lease_id: '',
    amount: '',
    paid_date: new Date().toISOString().split('T')[0],
    due_date: '',
    proof: null as File | null,
    remarks: '',
});

const openPaymentModal = (lease: any) => {
    selectedLease.value = lease;
    paymentForm.lease_id = lease.id;
    const today = new Date();
    paymentForm.paid_date = today.toISOString().split('T')[0];
    // Last day of current month
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate();
    const dueDate = new Date(today.getFullYear(), today.getMonth(), lastDay);
    paymentForm.due_date = dueDate.toISOString().split('T')[0];
    paymentForm.amount = lease.monthly_payment || '';
    showPaymentModal.value = true;
};

const handlePaymentProofChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        paymentForm.proof = file;
        paymentForm.receipt_name = file.name;
    }
};

const submitPayment = () => {
    paymentForm.post('/admin/payments/record', {
        preserveScroll: true,
        onSuccess: () => {
            showPaymentModal.value = false;
            paymentForm.reset();
        },
    });
};
</script>

<template>
    <AppSidebarLayout>
        <div class="p-6 md:p-8 bg-slate-50 min-h-screen">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Leases</h1>
                    <p class="text-sm text-slate-500 mt-1">Manage active and past vehicle leases.</p>
                </div>
                <button @click="showCreateModal = true" class="inline-flex items-center bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-lg font-medium text-sm shadow-sm transition-colors">
                    <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    New Lease
                </button>
            </div>

            <div class="mb-6 flex flex-col sm:flex-row gap-4">
                <div class="relative w-full sm:w-96">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input v-model="search" type="text" placeholder="Search by vehicle or driver..." class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-full text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm bg-white" />
                </div>
                <select v-model="statusFilter" class="px-4 py-2.5 border border-slate-200 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="pending">Pending</option>
                    <option value="ended">Ended</option>
                </select>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50/80 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Vehicle</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Driver</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Start Date</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">End Date</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Overdue</th>
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
                                <td class="px-6 py-4 align-top">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium capitalize"
                                        :class="{
                                            'bg-emerald-100 text-emerald-700': lease.status === 'active',
                                            'bg-amber-100 text-amber-700': lease.status === 'pending',
                                            'bg-slate-100 text-slate-700': lease.status === 'ended'
                                        }">
                                        {{ lease.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <span v-if="lease.overdue" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Yes</span>
                                    <span v-else class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">No</span>
                                </td>
                                <td class="px-6 py-4 align-top text-sm">{{ formatCurrency(lease.monthly_payment) }}</td>
                                <td class="px-6 py-4 align-top text-right">
                                   <div class="flex items-center justify-end gap-1.5">
<button 
    @click="openStatementModal(lease)" 
    class="p-2 text-slate-400 hover:text-emerald-700 transition-all active:scale-90" 
    title="View Statement"
>
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
    </svg>
</button>

    <!-- Payment button -->
    <button @click="openPaymentModal(lease)" 
          class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-slate-100 rounded-lg transition-all active:scale-90" 
        title="Record Payment">
        <CreditCard class="w-5 h-5" />
    </button>

    <button @click="editLease(lease)" 
        class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-slate-100 rounded-lg transition-all active:scale-90" 
        title="Edit Lease">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
    </button>

    <button @click="deleteLease(lease.id)" 
         class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-slate-100 rounded-lg transition-all active:scale-90" 
        title="Delete Lease">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
    </button>
</div>
                                </td>
                            </tr>
                            <tr v-if="!props.leases?.length">
                                <td colspan="8" class="px-6 py-8 text-center text-slate-500">No leases found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppSidebarLayout>

    <!-- Create Lease Modal (unchanged) -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showCreateModal = false"></div>

        <div class="relative w-full max-w-2xl bg-white rounded-[2rem] shadow-2xl overflow-hidden transform transition-all animate-in fade-in zoom-in duration-200">
            
            <div class="bg-slate-50 border-b border-slate-100 p-8 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-slate-900 leading-tight">Create New Lease</h3>
                        <p class="text-xs text-slate-400 font-medium mt-1 uppercase tracking-wider">Fleet Assignment Entry</p>
                    </div>
                </div>
                <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submitCreate" class="p-8">
                <div class="space-y-8">
                    
                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <span class="h-1 w-8 bg-emerald-600 rounded-full"></span>
                            <h4 class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Lease Assignment</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-700 ml-1">Vehicle Selection *</label>
                                <div class="relative">
                                    <select v-model="createForm.car_id" 
                                        class="w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm font-semibold text-slate-700 appearance-none" 
                                        required>
                                        <option value="">Choose a vehicle...</option>
                                        <option v-for="car in props.cars" :key="car.id" :value="car.id">
                                            {{ car.license_plate }} – {{ car.make }} {{ car.model }}
                                        </option>
                                    </select>
                                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-700 ml-1">Assigned Driver *</label>
                                <div class="relative">
                                    <select v-model="createForm.driver_id" 
                                        class="w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm font-semibold text-slate-700 appearance-none" 
                                        required>
                                        <option value="">Select a driver...</option>
                                        <option v-for="driver in props.drivers" :key="driver.id" :value="driver.id">
                                            {{ driver.name }}
                                        </option>
                                    </select>
                                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <span class="h-1 w-8 bg-emerald-600 rounded-full"></span>
                            <h4 class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Financial Terms</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-700 ml-1">Monthly Payment *</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-xs">RM</span>
                                    <input v-model="createForm.monthly_payment" type="number" step="0.01"
                                        class="w-full pl-12 pr-4 py-3 bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm font-bold text-slate-900" 
                                        placeholder="0.00" required />
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-700 ml-1">Down Payment</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-xs">RM</span>
                                    <input v-model="createForm.down_payment" type="number" step="0.01"
                                        class="w-full pl-12 pr-4 py-3 bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm font-bold text-slate-900" 
                                        placeholder="0.00" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <span class="h-1 w-8 bg-emerald-600 rounded-full"></span>
                            <h4 class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Duration</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-700 ml-1">Start Date *</label>
                                <input v-model="createForm.start_date" type="date" 
                                    class="w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm font-medium text-slate-900" 
                                    required />
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-700 ml-1">End Date (Optional)</label>
                                <input v-model="createForm.end_date" type="date" 
                                    class="w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm font-medium text-slate-900" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-10 flex flex-wrap gap-4">
                    <button type="button" @click="showCreateModal = false" 
                        class="flex-1 px-6 py-4 border border-slate-200 rounded-2xl text-sm font-bold text-slate-500 hover:bg-slate-50 transition-all">
                        Cancel
                    </button>
                    <button type="submit" :disabled="createForm.processing" 
                        class="flex-[2] px-6 py-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-sm font-bold shadow-lg shadow-emerald-200 transition-all disabled:opacity-50 disabled:scale-95 flex items-center justify-center gap-2">
                        <svg v-if="createForm.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ createForm.processing ? 'Syncing Lease...' : 'Register Lease Agreement' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Lease Modal (unchanged) -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity" @click="showEditModal = false"></div>

        <div class="relative w-full max-w-2xl bg-white rounded-[2.5rem] shadow-2xl overflow-hidden transform transition-all animate-in fade-in zoom-in duration-300">
            
            <div class="bg-slate-900 p-8 text-white relative overflow-hidden">
                <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-emerald-500/20 rounded-full blur-3xl"></div>
                
                <div class="relative z-10 flex items-center justify-between">
                    <div class="flex items-center gap-5">
                        <div class="h-14 w-14 bg-emerald-500 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-500/20">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-black tracking-tight">Edit Lease Contract</h3>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 text-[10px] font-bold uppercase rounded-md border border-emerald-500/30">
                                    {{ editingLease?.car?.license_plate }}
                                </span>
                                <span class="text-slate-400 text-sm font-medium">— {{ editingLease?.driver?.name }}</span>
                            </div>
                        </div>
                    </div>
                    <button @click="showEditModal = false" class="text-slate-500 hover:text-white transition-colors">
                        <X class="h-6 w-6" />
                    </button>
                </div>
            </div>

            <form @submit.prevent="updateLease" class="p-8">
                <div class="space-y-8">
                    
                    <div class="space-y-4">
                        <h4 class="text-[11px] font-black uppercase tracking-[0.2em] text-emerald-600 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                            Duration & Schedule
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-500 ml-1">Start Date *</label>
                                <input type="date" v-model="editingLease.start_date" 
                                    class="w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm font-medium" required />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-500 ml-1">End Date</label>
                                <input type="date" v-model="editingLease.end_date" 
                                    class="w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm font-medium" />
                            </div>
                        </div>
                    </div>

                    <div class="p-6 bg-emerald-50/50 rounded-[1.5rem] border border-emerald-100/50 space-y-6">
                        <div class="grid grid-cols-2 gap-5">
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-emerald-700 ml-1 uppercase tracking-tighter">Monthly Payment (RM) *</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600/50 font-bold text-xs">RM</span>
                                    <input type="number" step="0.01" v-model="editingLease.monthly_payment" 
                                        class="w-full pl-10 pr-4 py-3 bg-white border-emerald-100 rounded-xl focus:ring-2 focus:ring-emerald-500 text-sm font-black text-slate-800" required />
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-emerald-700 ml-1 uppercase tracking-tighter">Down Payment (RM)</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600/50 font-bold text-xs">RM</span>
                                    <input type="number" step="0.01" v-model="editingLease.down_payment" 
                                        class="w-full pl-10 pr-4 py-3 bg-white border-emerald-100 rounded-xl focus:ring-2 focus:ring-emerald-500 text-sm font-black text-slate-800" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 ml-1">Contract Status</label>
                        <div class="relative">
                            <select v-model="editingLease.status" 
                                class="w-full pl-4 pr-10 py-3 bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm font-semibold text-slate-700 appearance-none">
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="ended">Ended</option>
                            </select>
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-10 flex items-center gap-4">
                    <button type="button" @click="showEditModal = false" 
                        class="flex-1 px-6 py-4 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-400 hover:bg-slate-50 transition-all">
                        Cancel
                    </button>
                    <button type="submit" :disabled="editProcessing" 
                        class="flex-[2] px-6 py-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-sm font-black shadow-lg shadow-emerald-200 transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2">
                        <Loader2 v-if="editProcessing" class="h-4 w-4 animate-spin" />
                        {{ editProcessing ? 'Updating Records...' : 'Save Contract Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Statement Modal (updated) -->
    <div v-if="showStatementModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity" @click="closeStatementModal"></div>

        <div class="relative w-full max-w-4xl bg-white rounded-[2.5rem] shadow-2xl overflow-hidden transform transition-all animate-in fade-in zoom-in duration-300">
            
            <div class="bg-slate-900 px-8 py-6 text-white flex justify-between items-center relative overflow-hidden">
                <div class="absolute -left-10 -top-10 w-32 h-32 bg-emerald-500/10 rounded-full blur-3xl"></div>
                
                <div class="relative z-10 flex items-center gap-4">
                    <div class="h-10 w-10 bg-emerald-500 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/20">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-black tracking-tight leading-tight">Lease Statement</h3>
                        <div>
                            <p class="text-xs text-slate-400 font-medium uppercase tracking-widest mt-0.5">
                                Financial & Agreement Details as of Date {{ formattedToday }}
                            </p>
                        </div>  
                    </div>
                </div>

                <button @click="closeStatementModal" class="relative z-10 p-2 hover:bg-white/10 rounded-full transition-colors text-slate-400 hover:text-white outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-8 max-h-[75vh] overflow-y-auto custom-scrollbar">
                
                <div v-if="loadingStatement" class="flex flex-col items-center justify-center py-20">
                    <div class="relative h-16 w-16">
                        <div class="absolute inset-0 border-4 border-emerald-100 rounded-full"></div>
                        <div class="absolute inset-0 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
                    </div>
                    <p class="mt-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Preparing Financial Records...</p>
                </div>

                <div v-else-if="statementData" class="space-y-8">
                    
                    <!-- Row 1: Total Lease Value, Monthly Installment, Initial Deposit -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-emerald-600 p-6 rounded-[2rem] text-white shadow-xl shadow-emerald-200/50 relative overflow-hidden group">
                            <svg class="absolute right-[-5%] bottom-[-5%] opacity-10 group-hover:scale-110 transition-transform duration-500 w-24 h-24" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-100 mb-1">Total Lease Value</p>
                            <h4 class="text-3xl font-black">{{ formatCurrency(statementData.total_lease_value) }}</h4>
                        </div>

                        <div class="bg-slate-50 p-6 rounded-[2rem] border border-slate-100">
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-1">Monthly Installment</p>
                            <h4 class="text-2xl font-black text-slate-800">{{ formatCurrency(statementData.lease.monthly_payment) }}</h4>
                        </div>

                        <div class="bg-slate-50 p-6 rounded-[2rem] border border-slate-100">
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-1">Initial Deposit</p>
                            <h4 class="text-2xl font-black text-slate-800">{{ formatCurrency(statementData.down_payment) }}</h4>
                            <p class="text-[10px] font-bold text-slate-400 mt-1 uppercase">Paid on Commencement</p>
                        </div>
                    </div>

                    <!-- Row 2: Additional Late Charges, Payments Made, Total Payable -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-amber-50 p-6 rounded-[2rem] border border-amber-100">
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-amber-600 mb-1">Additional Late Charges</p>
                            <h4 class="text-2xl font-black text-amber-800">{{ formatCurrency(statementData.additional_late_charges) }}</h4>
                        </div>

                        <div class="bg-emerald-50 p-6 rounded-[2rem] border border-emerald-100">
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-600 mb-1">Payments Made</p>
                            <h4 class="text-2xl font-black text-emerald-800">{{ formatCurrency(statementData.payments_made) }}</h4>
                        </div>

                        <div class="bg-indigo-50 p-6 rounded-[2rem] border border-indigo-100">
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-600 mb-1">Total Payable</p>
                            <h4 class="text-2xl font-black text-indigo-800">{{ formatCurrency(statementData.total_payable) }}</h4>
                        </div>
                    </div>

                    <div class="bg-slate-50/50 border border-slate-100 rounded-[2.5rem] p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="h-6 w-1.5 bg-emerald-500 rounded-full"></div>
                            <h4 class="text-xs font-black uppercase tracking-widest text-slate-800">Lease Agreement & Period</h4>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-y-8 gap-x-4">
                            <div class="space-y-1">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">Assigned Car</span>
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-900 leading-tight uppercase">{{ statementData.lease.car.license_plate }}</span>
                                    <span class="text-[11px] text-slate-500 font-medium italic">{{ statementData.lease.car.make }} {{ statementData.lease.car.model }}</span>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">Primary Driver</span>
                                <span class="block font-bold text-slate-900 leading-tight">{{ statementData.lease.driver.name }}</span>
                            </div>

                            <div class="space-y-1">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">Agreement Start</span>
                                <span class="block font-bold text-slate-900 leading-tight">{{ formatDate(statementData.lease.start_date) }}</span>
                            </div>

                            <div class="space-y-1">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">Agreement End</span>
                                <span class="block font-bold text-slate-900 leading-tight">{{ formatDate(statementData.lease.end_date) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-10 space-y-6">
                        <div class="flex items-center justify-between px-2">
                            <div class="flex items-center gap-3">
                                <div class="h-6 w-1.5 bg-indigo-500 rounded-full"></div>
                                <h4 class="text-xs font-black uppercase tracking-widest text-slate-800">Payment History</h4>
                            </div>
                            <span class="text-[10px] font-bold bg-slate-100 text-slate-500 px-3 py-1 rounded-full uppercase tracking-tighter">
                                {{ statementData.payments.length }} Records Found
                            </span>
                        </div>

                        <div class="bg-white border border-slate-100 rounded-[2rem] overflow-hidden shadow-sm">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50/50 border-b border-slate-100">
                                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Date</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Amount</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Expected Due</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Timeliness</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="pmt in statementData.payments" :key="pmt.id" 
                                        class="hover:bg-slate-50/80 transition-colors group">
                                        
                                        <td class="px-6 py-4">
                                            <span class="text-sm font-bold text-slate-700 group-hover:text-slate-900 transition-colors">
                                                {{ formatDate(pmt.paid_date) }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="text-sm font-black text-slate-900">
                                                {{ formatCurrency(pmt.amount) }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="text-xs font-medium text-slate-500">
                                                {{ formatDate(pmt.due_date) }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 text-right">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter"
                                                :class="{
                                                    'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/10': pmt.timeliness === 'early',
                                                    'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/10': pmt.timeliness === 'on-time',
                                                    'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/10': pmt.timeliness === 'late'
                                                }">
                                                <span class="h-1.5 w-1.5 rounded-full" 
                                                    :class="{
                                                        'bg-emerald-500': pmt.timeliness === 'early',
                                                        'bg-blue-500': pmt.timeliness === 'on-time',
                                                        'bg-rose-500': pmt.timeliness === 'late'
                                                    }"></span>
                                                {{ pmt.timeliness }}
                                            </span>
                                        </td>
                                    </tr>

                                    <tr v-if="!statementData.payments.length">
                                        <td colspan="4" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <div class="p-3 bg-slate-50 rounded-2xl mb-3">
                                                    <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                    </svg>
                                                </div>
                                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">No transactions recorded yet</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Outstanding Balance (now using total_payable) -->
                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 flex justify-between items-center">
                        <span class="font-semibold text-slate-900">Outstanding Balance</span>
                        <span class="text-xl font-bold text-indigo-600">{{ formatCurrency(statementData.total_payable) }}</span>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-slate-50 border-t border-slate-100 flex justify-end">
                <button @click="closeStatementModal" 
                    class="px-10 py-3 bg-white border border-slate-200 rounded-xl text-[11px] font-black uppercase tracking-widest text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition-all shadow-sm active:scale-95">
                    Dismiss Statement
                </button>
            </div>
        </div>
    </div>

    <!-- Payment Modal (with remarks field) -->
    <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showPaymentModal = false"></div>
        <div class="relative w-full max-w-lg bg-white rounded-[2rem] shadow-2xl overflow-hidden transform transition-all animate-in fade-in zoom-in duration-200">
            
            <div class="bg-slate-50 border-b border-slate-100 p-8 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center shadow-sm">
                        <CreditCard class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-slate-900 leading-tight">Record Payment</h3>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="px-2 py-0.5 bg-slate-200 text-slate-700 rounded text-[10px] font-mono font-bold tracking-wider uppercase">
                                {{ selectedLease?.car?.license_plate }}
                            </span>
                            <span class="text-xs text-slate-400 font-medium">{{ selectedLease?.car?.model }}</span>
                        </div>
                    </div>
                </div>
                <button @click="showPaymentModal = false" class="text-slate-400 hover:text-slate-600 transition-colors p-2 hover:bg-white rounded-full shadow-sm">
                    <X class="h-6 w-6" />
                </button>
            </div>

            <form @submit.prevent="submitPayment" class="p-8">
                <div class="space-y-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Amount (RM)</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-sm">RM</span>
                                <input type="number" step="0.01" v-model="paymentForm.amount" 
                                    class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all font-bold text-slate-900" 
                                    placeholder="0.00" required />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Payment Date</label>
                            <div class="relative">
                                <Calendar class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                                <input type="date" v-model="paymentForm.paid_date" 
                                    class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all font-medium text-slate-700" 
                                    required />
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-amber-50 rounded-2xl border border-amber-100 flex items-center justify-between shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="bg-white p-2 rounded-xl shadow-sm">
                                <Clock class="h-5 w-5 text-amber-600" />
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-amber-700 uppercase tracking-tight">Billing Period End</p>
                                <p class="text-sm font-black text-amber-900 leading-none mt-0.5">{{ paymentForm.due_date || 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="text-[10px] font-bold bg-amber-200/50 text-amber-700 px-2 py-1 rounded-lg">SYSTEM GENERATED</div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Proof of Payment</label>
                        <div class="relative group">
                            <input type="file" @change="handlePaymentProofChange" accept=".pdf,.jpg,.jpeg,.png" 
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" />
                            
                            <div :class="[
                                'border-2 border-dashed rounded-2xl p-6 flex flex-col items-center justify-center transition-all duration-300 z-10',
                                paymentForm.receipt_name 
                                    ? 'border-emerald-500 bg-emerald-50 shadow-inner' 
                                    : 'border-slate-200 bg-slate-50 group-hover:border-emerald-400 group-hover:bg-emerald-50/50'
                            ]">
                                
                                <div class="mb-2">
                                    <CheckCircle2 v-if="paymentForm.receipt_name" class="h-10 w-10 text-emerald-600 animate-in zoom-in" />
                                    <UploadCloud v-else class="h-10 w-10 text-slate-300 group-hover:text-emerald-500 transition-colors" />
                                </div>

                                <div class="text-center">
                                    <template v-if="paymentForm.receipt_name">
                                        <p class="text-sm font-bold text-emerald-900 tracking-tight">Receipt Attached</p>
                                        <p class="text-[11px] text-emerald-600 mt-1 font-mono font-bold bg-white px-2 py-0.5 rounded border border-emerald-100 truncate max-w-[200px]">
                                            {{ paymentForm.receipt_name }}
                                        </p>
                                        <p class="text-[9px] text-emerald-400 mt-2 uppercase font-black">Click to replace file</p>
                                    </template>
                                    <template v-else>
                                        <p class="text-sm font-bold text-slate-600">Click or drag receipt here</p>
                                        <p class="text-[10px] text-slate-400 mt-1 uppercase font-bold tracking-tighter">PDF, JPG, or PNG (Max 5MB)</p>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ✨ Remarks field -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Remarks (optional)</label>
                        <textarea 
                            v-model="paymentForm.remarks" 
                            rows="2"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm font-medium resize-none" 
                            placeholder="Add any notes about this payment...">
                        </textarea>
                    </div>
                </div>

                <div class="mt-10 flex flex-wrap gap-4">
                    <button type="button" @click="showPaymentModal = false" 
                        class="flex-1 px-6 py-4 border border-slate-200 rounded-xl text-xs font-black uppercase tracking-widest text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition-all">
                        Cancel
                    </button>
                    <button type="submit" :disabled="paymentForm.processing" 
                        class="flex-[2] px-6 py-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-emerald-200/50 transition-all disabled:opacity-50 disabled:scale-95 flex items-center justify-center gap-3">
                        <Loader2 v-if="paymentForm.processing" class="h-5 w-5 animate-spin" />
                        {{ paymentForm.processing ? 'Recording Transaction...' : 'Complete Payment' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
@reference "tailwindcss";
.modal-input { @apply mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border outline-none transition-colors; }
.modal-label { @apply block text-sm font-medium text-slate-700 mb-1; }
</style>