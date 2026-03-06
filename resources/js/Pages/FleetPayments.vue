<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps<{
    payments: any[];
    filters: {
        search?: string;
        status?: string;
    };
}>();

// ---------- Helpers ----------
const formatDate = (date: string | null) => 
    date ? new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';

const formatCurrency = (amount: number | string | null): string => {
    const num = Number(amount);
    return `RM ${(isNaN(num) ? 0 : num).toFixed(2)}`;
};

const statusBadgeClass = (status: string) => {
    switch (status?.toLowerCase()) {
        case 'paid': return 'bg-emerald-100 text-emerald-700';
        case 'pending': return 'bg-amber-100 text-amber-700';
        case 'overdue': return 'bg-red-100 text-red-700';
        default: return 'bg-slate-100 text-slate-700';
    }
};

// ---------- Filters ----------
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');

let searchTimeout: ReturnType<typeof setTimeout>;

watch([search, statusFilter], ([newSearch, newStatus]) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/payments', 
            { search: newSearch, status: newStatus }, 
            { preserveState: true, preserveScroll: true }
        );
    }, 300);
});

// ---------- Actions ----------
const deletePayment = (id: number) => {
    if (confirm('Delete this payment?')) {
        router.delete(`/admin/payments/${id}`, { preserveScroll: true });
    }
};

// Approve payment
const approvePayment = (id: number) => {
    if (confirm('Approve this payment?')) {
        router.post(`/admin/payments/${id}/approve`, {}, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AppSidebarLayout>
        <div class="p-6 md:p-8 bg-slate-50 min-h-screen">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Payments</h1>
                    <p class="text-sm text-slate-500 mt-1">All lease payment records.</p>
                </div>
            </div>

            <div class="mb-6 flex flex-col sm:flex-row gap-4">
                <div class="relative w-full sm:w-96">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input v-model="search" type="text" placeholder="Search by car or driver..." class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-full text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm bg-white" />
                </div>
                <select v-model="statusFilter" class="px-4 py-2.5 border border-slate-200 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                    <option value="">All Statuses</option>
                    <option value="paid">Paid</option>
                    <option value="pending">Pending</option>
                    <option value="overdue">Overdue</option>
                </select>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50/80 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Car</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Driver</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Paid Date</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Due Date</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Proof</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="payment in props.payments" :key="payment.id" class="hover:bg-slate-50/50 group">
                                <td class="px-6 py-4 align-top">
                                    <span v-if="payment.lease?.car">{{ payment.lease.car.license_plate }}</span>
                                    <span v-else class="text-slate-400">—</span>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <span v-if="payment.lease?.driver">{{ payment.lease.driver.name }}</span>
                                    <span v-else class="text-slate-400">—</span>
                                </td>
                                <td class="px-6 py-4 align-top">{{ formatCurrency(payment.amount) }}</td>
                                <td class="px-6 py-4 align-top">{{ payment.paid_date ? formatDate(payment.paid_date) : '—' }}</td>
                                <td class="px-6 py-4 align-top">{{ formatDate(payment.due_date) }}</td>
                                <td class="px-6 py-4 align-top">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium" :class="statusBadgeClass(payment.status)">
                                        {{ payment.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <a v-if="payment.proof_url" :href="payment.proof_url" target="_blank" class="text-indigo-600 hover:underline text-sm">View</a>
                                    <span v-else class="text-slate-400">—</span>
                                </td>
<td class="px-6 py-4 align-middle text-right">
    <div class="flex items-center justify-end gap-2">
        
        <button
            v-if="payment.status === 'pending'"
            @click="approvePayment(payment.id)"
            class="group flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white rounded-lg transition-all duration-200 border border-emerald-100 hover:border-emerald-600 shadow-sm active:scale-95"
            title="Approve Payment"
        >
            <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
            <span class="text-xs font-bold uppercase tracking-wider">Approve</span>
        </button>

        <button 
            @click="deletePayment(payment.id)" 
            class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all duration-200" 
            title="Delete Record"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </button>
        
    </div>
</td>
                            </tr>
                            <tr v-if="!props.payments?.length">
                                <td colspan="8" class="px-6 py-8 text-center text-slate-500">No payments found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppSidebarLayout>
</template>

<style scoped>
@reference "tailwindcss";
</style>