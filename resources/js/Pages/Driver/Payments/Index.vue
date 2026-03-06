<script setup>
import DriverLayout from '@/layouts/DriverLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    CreditCard, 
    Plus, 
    Receipt, 
    Car, 
    Calendar, 
    CheckCircle2, 
    Clock, 
    AlertCircle,
    ChevronRight,
    ArrowUpRight
} from 'lucide-vue-next';

const props = defineProps({
    payments: Object, // paginated
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-MY', { style: 'currency', currency: 'MYR' }).format(amount || 0);
};

const formatDate = (date) => {
    return date ? new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';
};
</script>

<template>
    <DriverLayout>
        <Head title="Payment History" />

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Payment History</h1>
                    <p class="text-slate-500 mt-1">Track your leasing installments and download receipts.</p>
                </div>
                
                <Link 
                    :href="route('driver.payments.create')" 
                    class="inline-flex items-center justify-center space-x-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold transition-all shadow-md shadow-emerald-100 active:scale-95"
                >
                    <Plus class="h-5 w-5" />
                    <span>Make a Payment</span>
                </Link>
            </div>

            <div v-if="payments.data?.length" class="space-y-4">
                <div class="hidden md:grid grid-cols-12 px-8 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest">
                    <div class="col-span-2">Due Date</div>
                    <div class="col-span-3">Lease / Car</div>
                    <div class="col-span-2 text-right">Amount</div>
                    <div class="col-span-2 text-center">Status</div>
                    <div class="col-span-3 text-right">Details</div>
                </div>

                <div 
                    v-for="payment in payments.data" 
                    :key="payment.id" 
                    class="group bg-white border border-slate-200/60 rounded-2xl px-6 py-5 md:px-8 flex flex-col md:grid md:grid-cols-12 items-center gap-4 transition-all hover:shadow-lg hover:shadow-emerald-900/5 hover:border-emerald-200"
                >
                    <div class="col-span-2 w-full flex md:block justify-between items-center">
                        <span class="md:hidden text-xs font-bold text-slate-400 uppercase">Due Date</span>
                        <div class="flex items-center text-slate-900 font-medium">
                            <Calendar class="h-4 w-4 mr-2 text-slate-300" />
                            {{ formatDate(payment.due_date) }}
                        </div>
                    </div>

                    <div class="col-span-3 w-full">
             <div class="inline-flex items-center">
    <div class="h-8 w-8 bg-slate-50 rounded-lg flex items-center justify-center mr-3">
        <Car class="h-4 w-4 text-slate-400" />
    </div>
    <span class="text-sm font-bold text-slate-700">
        {{ payment.lease?.car?.license_plate || 'N/A' }}
    </span>
</div>
                    </div>

                    <div class="col-span-2 w-full text-left md:text-right">
                        <span class="text-lg font-extrabold text-slate-900">
                            {{ formatCurrency(payment.amount) }}
                        </span>
                    </div>

                    <div class="col-span-2 w-full flex justify-start md:justify-center">
                        <div v-if="payment.status === 'paid'" class="inline-flex items-center space-x-1.5 text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-full border border-emerald-100">
                            <CheckCircle2 class="h-3.5 w-3.5" />
                            <span class="text-xs font-bold uppercase tracking-tight">Paid</span>
                        </div>
                        <div v-else-if="payment.status === 'pending'" class="inline-flex items-center space-x-1.5 text-amber-700 bg-amber-50 px-3 py-1.5 rounded-full border border-amber-100">
                            <Clock class="h-3.5 w-3.5" />
                            <span class="text-xs font-bold uppercase tracking-tight">Pending</span>
                        </div>
                        <div v-else class="inline-flex items-center space-x-1.5 text-rose-700 bg-rose-50 px-3 py-1.5 rounded-full border border-rose-100">
                            <AlertCircle class="h-3.5 w-3.5" />
                            <span class="text-xs font-bold uppercase tracking-tight">Overdue</span>
                        </div>
                    </div>

                    <div class="col-span-3 w-full flex flex-col items-start md:items-end space-y-1">
                        <a v-if="payment.receipt_path" 
                           :href="'/storage/' + payment.receipt_path" 
                           target="_blank" 
                           class="inline-flex items-center text-xs font-bold text-emerald-600 hover:text-emerald-700 bg-emerald-50/50 group-hover:bg-emerald-50 px-3 py-2 rounded-lg transition-colors"
                        >
                            <Receipt class="h-3.5 w-3.5 mr-2" />
                            View Receipt
                            <ArrowUpRight class="h-3 w-3 ml-1 opacity-50" />
                        </a>
                        <span v-if="payment.paid_date" class="text-[10px] text-slate-400 font-medium uppercase tracking-tighter">
                            Processed: {{ formatDate(payment.paid_date) }}
                        </span>
                        <span v-else-if="payment.status === 'paid'" class="text-[10px] text-slate-400 italic">
                            Processing confirmation...
                        </span>
                    </div>
                </div>

                <div v-if="payments.links?.length > 3" class="mt-10 flex justify-center items-center space-x-1">
                    <template v-for="(link, key) in payments.links" :key="key">
                        <div v-if="link.url === null" 
                             class="px-4 py-2 text-sm text-slate-300 select-none"
                             v-html="link.label" />
                        <Link v-else 
                              :href="link.url" 
                              class="px-4 py-2 text-sm font-bold rounded-xl transition-all"
                              :class="link.active ? 'bg-emerald-600 text-white shadow-md shadow-emerald-100' : 'text-slate-500 hover:bg-white border border-transparent hover:border-slate-200'"
                              v-html="link.label" />
                    </template>
                </div>
            </div>

            <div v-else class="bg-white border-2 border-dashed border-slate-200 rounded-[2.5rem] p-20 text-center">
                <div class="inline-flex items-center justify-center h-20 w-20 rounded-full bg-slate-50 mb-6">
                    <CreditCard class="h-10 w-10 text-slate-200" />
                </div>
                <h3 class="text-xl font-bold text-slate-900">No payment history</h3>
                <p class="text-slate-500 mt-2 max-w-xs mx-auto">
                    Installment records will appear here once your lease is activated.
                </p>
            </div>
        </div>
    </DriverLayout>
</template>

<style scoped>
/* Row Entrance Animation */
.group {
    animation: slideUp 0.4s ease-out forwards;
    opacity: 0;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Delay for staggered effect */
.group:nth-child(1) { animation-delay: 0.05s; }
.group:nth-child(2) { animation-delay: 0.1s; }
.group:nth-child(3) { animation-delay: 0.15s; }
</style>