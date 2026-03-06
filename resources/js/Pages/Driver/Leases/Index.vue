<script setup>
import DriverLayout from '@/layouts/DriverLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    Car, 
    Calendar, 
    ChevronRight, 
    FileText, 
    AlertCircle,
    CheckCircle2,
    Clock
} from 'lucide-vue-next';

defineProps({
    leases: Array,
});

const formatDate = (date) => {
    return date ? new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';
};

// Helper for status styling
const getStatusClasses = (status) => {
    const map = {
        active: 'bg-emerald-100 text-emerald-700 ring-emerald-600/20',
        pending: 'bg-amber-100 text-amber-700 ring-amber-600/20',
        completed: 'bg-slate-100 text-slate-700 ring-slate-600/20',
        cancelled: 'bg-rose-100 text-rose-700 ring-rose-600/20',
    };
    return map[status] || 'bg-gray-100 text-gray-700';
};
</script>

<template>
    <DriverLayout>
        <Head title="My Vehicle Leases" />

        <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6">
            <div class="mb-10">
                <h1 class="text-3xl font-black text-slate-900 tracking-tight">My Leases</h1>
                <p class="text-slate-500 mt-2 font-medium">Manage your vehicle agreements and view statements.</p>
            </div>

            <div v-if="leases.length" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="lease in leases" :key="lease.id" 
                    class="group relative bg-white rounded-[2rem] border border-slate-100 shadow-sm hover:shadow-xl hover:shadow-emerald-500/5 transition-all duration-300 overflow-hidden">
                    
                    <div class="absolute top-6 right-6">
                        <span :class="['inline-flex items-center rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest ring-1 ring-inset', getStatusClasses(lease.status)]">
                            <span class="mr-1.5 h-1.5 w-1.5 rounded-full fill-current" :class="lease.status === 'active' ? 'bg-emerald-500' : 'bg-current'"></span>
                            {{ lease.status }}
                        </span>
                    </div>

                    <div class="p-8">
                        <div class="flex items-center gap-5 mb-8">
                            <div class="h-16 w-16 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center group-hover:bg-emerald-600 group-hover:text-white transition-colors duration-300">
                                <Car class="w-8 h-8" />
                            </div>
                            <div>
                                <h2 class="text-xl font-black text-slate-900 tracking-tight leading-tight">
                                    {{ lease.car?.make }} {{ lease.car?.model }}
                                </h2>
                                <p class="text-emerald-600 font-mono font-bold text-sm tracking-wider uppercase mt-0.5">
                                    {{ lease.car?.license_plate }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-8">
                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100/50">
                                <div class="flex items-center gap-2 text-slate-400 mb-1">
                                    <Calendar class="w-3.5 h-3.5" />
                                    <span class="text-[10px] font-black uppercase tracking-widest">Start Date</span>
                                </div>
                                <p class="text-sm font-bold text-slate-700">{{ formatDate(lease.start_date) }}</p>
                            </div>
                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100/50">
                                <div class="flex items-center gap-2 text-slate-400 mb-1">
                                    <Clock class="w-3.5 h-3.5" />
                                    <span class="text-[10px] font-black uppercase tracking-widest">Contract</span>
                                </div>
                                <p class="text-sm font-bold text-slate-700 capitalize">{{ lease.type || 'Standard' }}</p>
                            </div>
                        </div>

                        <Link :href="route('driver.leases.show', lease.id)" 
                            class="flex items-center justify-between w-full px-6 py-4 bg-slate-900 text-white rounded-2xl font-bold text-sm group-hover:bg-emerald-600 transition-all duration-300 shadow-lg shadow-slate-200 group-hover:shadow-emerald-200">
                            <span class="flex items-center gap-3">
                                <FileText class="w-4 h-4 opacity-70" />
                                View Statements
                            </span>
                            <ChevronRight class="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                        </Link>
                    </div>
                </div>
            </div>

            <div v-else class="flex flex-col items-center justify-center py-20 px-6 bg-white rounded-[3rem] border-2 border-dashed border-slate-100 shadow-inner">
                <div class="h-24 w-24 bg-slate-50 text-slate-200 rounded-full flex items-center justify-center mb-6">
                    <AlertCircle class="w-12 h-12" />
                </div>
                <h3 class="text-xl font-bold text-slate-900">No active leases found</h3>
                <p class="text-slate-400 mt-2 max-w-xs text-center leading-relaxed">
                    Once a vehicle lease is assigned to you by the administrator, it will appear here.
                </p>
                <Link href="/" class="mt-8 text-sm font-black uppercase tracking-widest text-emerald-600 hover:text-emerald-700 transition-colors">
                    Back to Dashboard
                </Link>
            </div>
        </div>
    </DriverLayout>
</template>