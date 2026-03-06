<script setup>
import DriverLayout from '@/Layouts/DriverLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    ArrowLeft, 
    Car, 
    CreditCard, 
    Receipt, 
    TrendingUp, 
    Calendar,
    ChevronRight,
    CheckCircle2,
    Clock,
    AlertCircle
} from 'lucide-vue-next';

const props = defineProps({
    lease: Object,
    payments: Array,
    total_paid: Number,
    total_pending: Number,
    next_payment: Object,
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-MY', { 
        style: 'currency', 
        currency: 'MYR',
        minimumFractionDigits: 2 
    }).format(amount || 0);
};

const formatDate = (date) => {
    return date ? new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';
};

const statusBadgeClass = (status) => {
    const map = {
        paid: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        pending: 'bg-amber-50 text-amber-700 ring-amber-600/20',
        overdue: 'bg-rose-50 text-rose-700 ring-rose-600/20',
    };
    return map[status] || 'bg-slate-50 text-slate-600 ring-slate-600/10';
};
</script>

<template>
    <DriverLayout>
        <Head :title="'Statement - ' + lease.car?.license_plate" />

        <div class="max-w-6xl mx-auto py-8 px-4 sm:px-6">
            
            <div class="mb-8">
                <Link :href="route('driver.leases.index')" 
                    class="inline-flex items-center gap-2 text-sm font-bold text-slate-400 hover:text-emerald-600 transition-colors group">
                    <div class="p-2 rounded-xl bg-white border border-slate-100 group-hover:bg-emerald-50 group-hover:border-emerald-100 transition-all">
                        <ArrowLeft class="w-4 h-4" />
                    </div>
                    Back to My Leases
                </Link>
            </div>

            <div class="relative overflow-hidden bg-slate-900 rounded-[2.5rem] p-8 md:p-12 text-white mb-8 shadow-2xl shadow-slate-200">
                <div class="absolute top-0 right-0 -mt-20 -mr-20 w-64 h-64 bg-emerald-500/20 rounded-full blur-[100px]"></div>
                <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-64 h-64 bg-indigo-500/10 rounded-full blur-[100px]"></div>

                <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-8">
                    <div class="flex items-center gap-6">
                        <div class="h-20 w-20 bg-emerald-500 rounded-3xl flex items-center justify-center shadow-lg shadow-emerald-500/40">
                            <Car class="w-10 h-10 text-white" />
                        </div>
                        <div>
                            <h1 class="text-3xl md:text-4xl font-black tracking-tight leading-tight">
                                {{ lease.car?.make }} {{ lease.car?.model }}
                            </h1>
                            <div class="flex items-center gap-3 mt-2">
                                <span class="px-3 py-1 bg-white/10 backdrop-blur-md rounded-lg font-mono text-sm font-bold tracking-widest text-emerald-400 border border-white/10">
                                    {{ lease.car?.license_plate }}
                                </span>
                                <span class="h-1.5 w-1.5 rounded-full bg-white/30"></span>
                                <span class="text-slate-400 text-sm font-bold uppercase tracking-widest">Lease Statement</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="next_payment" class="bg-white/10 backdrop-blur-xl border border-white/10 rounded-[2rem] p-6 min-w-[280px]">
                        <div class="flex items-center gap-2 text-emerald-400 mb-3">
                            <Clock class="w-4 h-4" />
                            <span class="text-[10px] font-black uppercase tracking-[0.2em]">Next Due Date</span>
                        </div>
                        <p class="text-2xl font-black mb-1">{{ formatCurrency(next_payment.amount) }}</p>
                        <p class="text-slate-400 text-sm font-medium">{{ formatDate(next_payment.due_date) }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm">
                    <div class="p-3 bg-slate-50 w-fit rounded-2xl mb-6">
                        <CreditCard class="w-6 h-6 text-slate-400" />
                    </div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Monthly Installment</p>
                    <p class="text-3xl font-black text-slate-900">{{ formatCurrency(lease.monthly_payment) }}</p>
                </div>

                <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:scale-110 transition-transform">
                        <CheckCircle2 class="w-24 h-24 text-emerald-600" />
                    </div>
                    <div class="p-3 bg-emerald-50 w-fit rounded-2xl mb-6">
                        <TrendingUp class="w-6 h-6 text-emerald-600" />
                    </div>
                    <p class="text-[10px] font-black text-emerald-600/60 uppercase tracking-[0.2em] mb-1">Total Paid To Date</p>
                    <p class="text-3xl font-black text-emerald-600">{{ formatCurrency(total_paid) }}</p>
                </div>

                <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:scale-110 transition-transform">
                        <AlertCircle class="w-24 h-24 text-amber-600" />
                    </div>
                    <div class="p-3 bg-amber-50 w-fit rounded-2xl mb-6">
                        <Receipt class="w-6 h-6 text-amber-600" />
                    </div>
                    <p class="text-[10px] font-black text-amber-600/60 uppercase tracking-[0.2em] mb-1">Outstanding Balance</p>
                    <p class="text-3xl font-black text-amber-600">{{ formatCurrency(total_pending) }}</p>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-8 py-8 border-b border-slate-50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-black text-slate-900 tracking-tight">Payment History</h3>
                        <p class="text-slate-400 text-sm font-medium mt-1">Detailed history of all lease transactions</p>
                    </div>
   <!-- <button class="px-6 py-3 bg-slate-50 hover:bg-slate-100 rounded-xl text-xs font-black uppercase tracking-widest text-slate-600 transition-all flex items-center gap-2"> 
                        <FileText class="w-4 h-4" />
                        Download PDF Statement
                    </button> -->
                </div>

             <div class="overflow-x-auto">
    <table class="w-full">
        <thead>
            <tr class="bg-slate-50/50">
                <th class="px-8 py-5 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Billing Period</th>
                <th class="px-8 py-5 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Transaction Date</th>
                <th class="px-8 py-5 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Amount</th>
                <th class="px-8 py-5 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Status</th>
                </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
            <tr v-for="payment in payments" :key="payment.id" class="group hover:bg-slate-50/50 transition-colors">
                <td class="px-8 py-6">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-slate-100 rounded-lg text-slate-400 group-hover:bg-white group-hover:text-emerald-600 transition-colors">
                            <Calendar class="w-4 h-4" />
                        </div>
                        <span class="text-sm font-bold text-slate-700">{{ formatDate(payment.due_date) }}</span>
                    </div>
                </td>
                <td class="px-8 py-6 text-sm font-medium text-slate-500">
                    {{ payment.paid_date ? formatDate(payment.paid_date) : 'Pending Settlement' }}
                </td>
                <td class="px-8 py-6">
                    <span class="text-sm font-black text-slate-900">{{ formatCurrency(payment.amount) }}</span>
                </td>
                <td class="px-8 py-6">
                    <span :class="['inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest ring-1 ring-inset', statusBadgeClass(payment.status)]">
                        {{ payment.status }}
                    </span>
                </td>
                </tr>
            <tr v-if="!payments.length">
                <td colspan="4" class="px-8 py-20 text-center">
                    <div class="flex flex-col items-center">
                        <Receipt class="w-12 h-12 text-slate-200 mb-4" />
                        <p class="text-slate-400 font-bold tracking-tight">No payment records found for this lease.</p>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
            </div>
        </div>
    </DriverLayout>
</template>