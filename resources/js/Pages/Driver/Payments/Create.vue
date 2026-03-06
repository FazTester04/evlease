<script setup>
import DriverLayout from '@/layouts/DriverLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
    ChevronLeft, 
    CreditCard, 
    UploadCloud, 
    FileText, 
    CheckCircle2, 
    AlertCircle,
    Car
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    leases: Array,
    selectedLease: Object,
});

const form = useForm({
    lease_id: props.selectedLease?.id || '',
    amount: '',
    receipt: null,
});

// For UI feedback after selection
const selectedLeaseDetails = computed(() => {
    return props.leases.find(l => l.id === form.lease_id);
});

const fileName = computed(() => form.receipt?.name || null);

const submit = () => {
    form.post(route('driver.payments.store'), {
        onSuccess: () => form.reset('receipt'),
    });
};

const handleFileChange = (e) => {
    form.receipt = e.target.files[0];
};
</script>

<template>
    <DriverLayout>
        <Head title="Make a Payment" />

        <div class="max-w-2xl mx-auto px-4 py-8">
            <div class="mb-8">
                <Link :href="route('driver.payments.index')" 
                    class="inline-flex items-center text-sm font-bold text-slate-500 hover:text-emerald-600 transition-colors group">
                    <ChevronLeft class="size-4 mr-1 group-hover:-translate-x-1 transition-transform" />
                    Back to Payments
                </Link>
                <h1 class="text-3xl font-black text-slate-900 mt-2 tracking-tight">Make a Payment</h1>
            </div>

            <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/60 border border-slate-100 overflow-hidden">
                
                <div v-if="selectedLeaseDetails" 
                    class="bg-emerald-50 border-b border-emerald-100 p-6 flex items-center gap-4 animate-in slide-in-from-top duration-300">
                    <div class="p-3 bg-white rounded-2xl text-emerald-600 shadow-sm">
                        <Car class="size-6" />
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">Active Lease</p>
                        <p class="text-lg font-black text-slate-900">
                            {{ selectedLeaseDetails.car?.license_plate }}
                        </p>
                        <p class="text-xs font-medium text-emerald-600">
                            {{ selectedLeaseDetails.car?.make }} {{ selectedLeaseDetails.car?.model }}
                        </p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="p-8 space-y-8">
                    
                    <div class="space-y-3">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 flex items-center gap-2">
                            <span class="size-4 flex items-center justify-center rounded-full bg-slate-100 text-slate-500">1</span>
                            Select Lease
                        </label>
                        <div class="relative">
                            <select v-model="form.lease_id" 
                                class="w-full pl-4 pr-10 py-4 bg-slate-50 border-slate-200 rounded-2xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all font-bold text-slate-700 appearance-none" 
                                required>
                                <option value="" disabled>Select your vehicle...</option>
                                <option v-for="lease in leases" :key="lease.id" :value="lease.id">
                                    {{ lease.car?.license_plate }} ({{ lease.car?.model }})
                                </option>
                            </select>
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                <CreditCard class="size-4 text-slate-400" />
                            </div>
                        </div>
                        <p v-if="form.errors.lease_id" class="text-xs text-rose-500 font-bold flex items-center gap-1">
                            <AlertCircle class="size-3" /> {{ form.errors.lease_id }}
                        </p>
                    </div>

                    <div class="space-y-3">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 flex items-center gap-2">
                            <span class="size-4 flex items-center justify-center rounded-full bg-slate-100 text-slate-500">2</span>
                            Payment Amount
                        </label>
                        <div class="relative">
                            <span class="absolute left-5 top-1/2 -translate-y-1/2 text-lg font-black text-slate-400">RM</span>
                            <input type="number" step="0.01" min="0.01" v-model="form.amount" 
                                inputmode="decimal"
                                placeholder="0.00"
                                class="w-full pl-14 pr-4 py-5 bg-slate-50 border-slate-200 rounded-2xl focus:ring-2 focus:ring-emerald-500 text-2xl font-black text-slate-900 transition-all" 
                                required />
                        </div>
                        <p v-if="form.errors.amount" class="text-xs text-rose-500 font-bold flex items-center gap-1">
                            <AlertCircle class="size-3" /> {{ form.errors.amount }}
                        </p>
                    </div>

                    <div class="space-y-3">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 flex items-center gap-2">
                            <span class="size-4 flex items-center justify-center rounded-full bg-slate-100 text-slate-500">3</span>
                            Proof of Payment
                        </label>
                        
                        <div class="relative group">
                            <input type="file" @change="handleFileChange" accept=".jpg,.jpeg,.png,.pdf" 
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required />
                            
                            <div :class="[
                                'border-2 border-dashed rounded-[1.5rem] p-8 flex flex-col items-center justify-center transition-all',
                                fileName ? 'bg-emerald-50 border-emerald-200' : 'bg-slate-50 border-slate-200 group-hover:bg-slate-100 group-hover:border-slate-300'
                            ]">
                                <div v-if="!fileName" class="flex flex-col items-center">
                                    <div class="p-4 bg-white rounded-2xl shadow-sm text-slate-400 mb-3 group-hover:scale-110 transition-transform">
                                        <UploadCloud class="size-8" />
                                    </div>
                                    <p class="text-sm font-black text-slate-600">Upload Receipt</p>
                                    <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-tighter font-bold">PDF, JPG, or PNG (Max 5MB)</p>
                                </div>
                                
                                <div v-else class="flex flex-col items-center animate-in zoom-in duration-300">
                                    <div class="p-4 bg-emerald-100 rounded-2xl text-emerald-600 mb-3">
                                        <FileText class="size-8" />
                                    </div>
                                    <p class="text-sm font-black text-emerald-800 truncate max-w-[200px]">{{ fileName }}</p>
                                    <p class="text-[10px] text-emerald-600 mt-1 font-bold flex items-center gap-1">
                                        <CheckCircle2 class="size-3" /> READY TO SUBMIT
                                    </p>
                                </div>
                            </div>
                        </div>
                        <p v-if="form.errors.receipt" class="text-xs text-rose-500 font-bold flex items-center gap-1">
                            <AlertCircle class="size-3" /> {{ form.errors.receipt }}
                        </p>
                    </div>

                    <div class="pt-4">
                        <button type="submit" :disabled="form.processing" 
                            class="w-full py-5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-black text-lg shadow-xl shadow-emerald-200 transition-all active:scale-95 flex items-center justify-center gap-3 disabled:opacity-50">
                            <Loader2 v-if="form.processing" class="size-6 animate-spin" />
                            {{ form.processing ? 'Processing...' : 'Confirm & Submit' }}
                        </button>
                        <p class="text-center text-[10px] text-slate-400 mt-4 font-bold uppercase tracking-widest">
                            Secure payment processing via EV Fleet Admin
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </DriverLayout>
</template>