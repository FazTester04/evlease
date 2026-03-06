<script setup>
import DriverLayout from '@/layouts/DriverLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    Car, 
    CreditCard, 
    Calendar, 
    ArrowUpRight, 
    FileUp, 
    ChevronRight, 
    Clock, 
    CheckCircle2,
    AlertCircle,
    TrendingUp
} from 'lucide-vue-next';

defineProps({
    leases: Array,
    totalOutstanding: Number,
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-MY', { style: 'currency', currency: 'MYR' }).format(amount || 0);
};

const formatDate = (date) => {
    return date ? new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';
};

// Helper to determine if a payment is late
const isOverdue = (dateString) => {
    if (!dateString) return false;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return new Date(dateString) < today;
};
</script>

<template>
    <DriverLayout>
        <Head title="Driver Dashboard" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 tracking-tight">
                        Welcome back, {{ $page.props.auth.user.name.split(' ')[0] }}
                    </h1>
                    <p class="text-slate-500 mt-1">Here's the current status of your fleet leases.</p>
                </div>
                <div class="flex items-center space-x-2 text-sm font-medium text-emerald-700 bg-emerald-50 px-4 py-2 rounded-full border border-emerald-100">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span>System Active: EV Fleet Cloud</span>
                </div>
            </div>

            <!-- <div v-if="leases.length && totalOutstanding > 0" class="mb-8 bg-slate-900 rounded-[2.5rem] p-8 text-white shadow-2xl relative overflow-hidden">
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-emerald-500/20 rounded-full blur-3xl"></div>
                <div class="relative flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-black text-emerald-400 uppercase tracking-[0.2em] mb-2">Total Remaining Lease Value</p>
                        <p class="text-4xl font-black">{{ formatCurrency(totalOutstanding) }}</p>
                    </div>
                    <div class="h-14 w-14 bg-white/10 backdrop-blur-lg rounded-2xl flex items-center justify-center border border-white/10 shadow-lg">
                        <TrendingUp class="h-7 w-7 text-emerald-400" />
                    </div>
                </div>
            </div> -->

            <div v-if="leases.length" class="space-y-10">
                <div v-for="lease in leases" :key="lease.id" class="group">
                    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200/60 overflow-hidden transition-all duration-300 hover:shadow-xl hover:shadow-emerald-900/5">
                        
                        <div class="flex flex-col lg:flex-row">
                            <div class="lg:w-1/3 p-8 bg-slate-50 border-r border-slate-100 flex flex-col justify-between">
                                <div>
                                    <div class="h-14 w-14 bg-emerald-600 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-200 mb-6 transform group-hover:scale-110 transition-transform">
                                        <Car class="h-7 w-7 text-white" />
                                    </div>
                                    <h2 class="text-2xl font-bold text-slate-900 leading-tight">
                                        {{ lease.car?.make }} <br/>
                                        <span class="text-emerald-600">{{ lease.car?.model }}</span>
                                    </h2>
                                    <div class="mt-4 inline-block px-3 py-1 bg-white border border-slate-200 rounded-lg text-sm font-mono font-bold tracking-wider text-slate-700">
                                        {{ lease.car?.license_plate }}
                                    </div>
                                    <!-- Inside the lease card, after the license plate block -->
<div v-if="lease.status === 'paused'" class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-xl">
    <div class="flex items-center gap-2 text-amber-700">
        <Clock class="h-4 w-4" />
        <span class="text-xs font-bold uppercase tracking-wide">Lease paused – vehicle in maintenance</span>
    </div>
    <p class="text-xs text-amber-600 mt-1">Payments will resume once maintenance is complete.</p>
</div>
                                    <div class="mt-6 flex items-center text-xs font-semibold text-slate-400 uppercase tracking-widest">
                                        <Calendar class="h-3.5 w-3.5 mr-2" />
                                        Lease Active: {{ formatDate(lease.start_date) }}
                                    </div>
                                </div>
                            </div>

                            <div class="lg:w-2/3 p-8">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 mb-10">
                                    <div class="space-y-2">
                                        <div class="flex items-center text-slate-400 space-x-2">
                                            <TrendingUp class="h-4 w-4" />
                                            <span class="text-[10px] font-bold uppercase tracking-wider">Monthly</span>
                                        </div>
                                        <p class="text-2xl font-black text-slate-900">{{ formatCurrency(lease.monthly_payment) }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex items-center text-slate-400 space-x-2">
                                            <Clock class="h-4 w-4 text-amber-500" />
                                            <span class="text-[10px] font-bold uppercase tracking-wider">Balance Left</span>
                                        </div>
                                        <p class="text-2xl font-black text-amber-600">{{ formatCurrency(lease.outstanding_balance) }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex items-center text-slate-400 space-x-2">
                                            <Calendar :class="isOverdue(lease.next_due_date) ? 'text-rose-500' : 'text-emerald-500'" class="h-4 w-4" />
                                            <span class="text-[10px] font-bold uppercase tracking-wider">Next Due</span>
                                        </div>
                                        
                                        <div class="flex items-center gap-2">
                                            <template v-if="lease.next_due_date">
                                                <p :class="isOverdue(lease.next_due_date) ? 'text-rose-600' : 'text-slate-900'" class="text-2xl font-black">
                                                    {{ formatDate(lease.next_due_date) }}
                                                </p>
                                                <span v-if="isOverdue(lease.next_due_date)" class="px-2 py-0.5 bg-rose-100 text-rose-700 text-[10px] font-black uppercase rounded-md border border-rose-200">
                                                    Late
                                                </span>
                                            </template>
                                            <template v-else>
                                                <div class="flex items-center gap-2 text-emerald-600">
                                                    <CheckCircle2 class="h-5 w-5" />
                                                    <p class="text-xl font-black">Up to Date</p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-4">
                          <Link v-if="lease.status === 'active'" 
    :href="route('driver.payments.create', { lease_id: lease.id })" 
    class="flex-1 min-w-[140px] flex items-center justify-center space-x-2 px-6 py-3.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold transition-all duration-200 shadow-lg shadow-emerald-200/50 active:scale-[0.98] group"
>
    <CreditCard class="h-4 w-4 transition-transform group-hover:-rotate-12" />
    <span class="tracking-tight">Make Payment</span>
</Link>

<button v-else 
    disabled 
    class="flex-1 min-w-[140px] flex items-center justify-center space-x-2 px-6 py-3.5 bg-amber-50 border border-amber-200/60 text-amber-700/80 rounded-xl font-bold cursor-not-allowed transition-all"
>
    <div class="flex h-2 w-2 relative">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
    </div>
    <span class="tracking-tight uppercase text-[11px] font-black">Payments Paused</span>
</button>
                                    
                                    <Link :href="route('driver.leases.show', lease.id)" 
                                        class="flex-1 min-w-[140px] flex items-center justify-center space-x-2 px-6 py-3.5 border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl font-bold transition-all">
                                        <FileUp class="h-4 w-4" />
                                        <span>View Statement</span>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <div v-if="lease.recent_payments?.length" class="bg-slate-50/50 border-t border-slate-100 p-8">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Recent Activity</h3>
                                <div class="h-px flex-1 bg-slate-200 mx-4"></div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div v-for="payment in lease.recent_payments" :key="payment.id" 
                                    class="bg-white border border-slate-200/50 rounded-2xl px-5 py-4 flex items-center justify-between transition-all hover:border-emerald-200">
                                    <div class="flex items-center space-x-4">
                                        <div :class="{
                                            'bg-emerald-50 text-emerald-600': payment.status === 'paid',
                                            'bg-amber-50 text-amber-600': payment.status === 'pending',
                                            'bg-rose-50 text-rose-600': payment.status === 'overdue'
                                        }" class="h-10 w-10 rounded-full flex items-center justify-center">
                                            <CheckCircle2 v-if="payment.status === 'paid'" class="h-5 w-5" />
                                            <Clock v-else-if="payment.status === 'pending'" class="h-5 w-5" />
                                            <AlertCircle v-else class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-900">{{ formatCurrency(payment.amount) }}</p>
                                            <p class="text-[10px] text-slate-500 font-medium uppercase tracking-tighter">{{ formatDate(payment.due_date) }}</p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold capitalize px-3 py-1 rounded-full" 
                                          :class="payment.status === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'">
                                        {{ payment.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="bg-white border border-slate-200 rounded-[3rem] p-20 text-center shadow-sm">
                <div class="inline-flex items-center justify-center h-24 w-24 rounded-full bg-slate-50 mb-8">
                    <Car class="h-12 w-12 text-slate-200" />
                </div>
                <h3 class="text-2xl font-bold text-slate-900">No active leases found</h3>
                <p class="text-slate-500 mt-2 max-w-sm mx-auto">Once your vehicle lease agreement is finalized, your dashboard will come to life.</p>
            </div>
        </div>
    </DriverLayout>
</template>

<style scoped>
.max-w-7xl {
    animation: fadeIn 0.8s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>