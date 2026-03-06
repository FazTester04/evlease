<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { Chart, registerables } from 'chart.js';
import { 
    Car, 
    TrendingUp, 
    AlertCircle, 
    Key, 
    Wrench, 
    FileWarning, 
    Search,
    UserCircle2,
    ArrowUpRight,
   
} from 'lucide-vue-next';

Chart.register(...registerables);

const page = usePage<any>();
const paymentChartCanvas = ref<HTMLCanvasElement | null>(null);
let paymentChartInstance: Chart | null = null;

onMounted(() => {
    createPaymentChart();
});

function createPaymentChart() {
    if (!paymentChartCanvas.value) return;
    if (paymentChartInstance) paymentChartInstance.destroy();

    const ctx = paymentChartCanvas.value.getContext('2d');
    if (!ctx) return;

    const { paidOnTime, late, overdue } = page.props.paymentStatus;

    paymentChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['On Time', 'Late', 'Overdue'],
            datasets: [{
                data: [paidOnTime, late, overdue],
                backgroundColor: ['#10B981', '#F59E0B', '#EF4444'],
                hoverOffset: 4,
                borderWidth: 0,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '80%',
            plugins: {
                legend: { display: false },
            },
        },
    });
}

const totalPayments = computed(() => {
    const { paidOnTime, late, overdue } = page.props.paymentStatus;
    return paidOnTime + late + overdue;
});

const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('en-MY', { style: 'currency', currency: 'MYR', maximumFractionDigits: 0 }).format(val);
};
</script>

<template>
    <AppSidebarLayout>
        <div class="max-w-[1600px] mx-auto px-8 py-10 space-y-10">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight">Admin Command Center</h1>
                    <p class="text-slate-500 font-medium">Real-time fleet performance and financial health.</p>
                </div>
                <div class="flex items-center gap-3">
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition-shadow group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-emerald-50 rounded-2xl text-emerald-600 group-hover:scale-110 transition-transform">
                            <Car class="size-6" />
                        </div>
                        <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">+12%</span>
                    </div>
                    <span class="block text-3xl font-black text-slate-900">{{ page.props.kpis.totalVehiclesLeased }}</span>
                    <span class="text-sm font-bold text-slate-400 uppercase tracking-wider mt-1">Fleet Capacity</span>
                </div>

                <div class="bg-slate-900 rounded-3xl p-6 shadow-xl shadow-slate-200 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-slate-800 rounded-2xl text-emerald-400 group-hover:scale-110 transition-transform">
                            <TrendingUp class="size-6" />
                        </div>
                    </div>
                    <span class="block text-3xl font-black text-white">{{ formatCurrency(page.props.kpis.monthlyRevenue) }}</span>
                    <span class="text-sm font-bold text-slate-400 uppercase tracking-wider mt-1">Monthly Revenue</span>
                </div>

                <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-rose-50 rounded-2xl text-rose-600 group-hover:scale-110 transition-transform">
                            <AlertCircle class="size-6" />
                        </div>
                    </div>
                    <span class="block text-3xl font-black text-slate-900">{{ page.props.kpis.overduePaymentsCount }}</span>
                    <span class="text-sm font-bold text-slate-400 uppercase tracking-wider mt-1">Urgent Overdue</span>
                </div>

                <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-indigo-50 rounded-2xl text-indigo-600 group-hover:scale-110 transition-transform">
                            <Key class="size-6" />
                        </div>
                    </div>
                    <span class="block text-3xl font-black text-slate-900">{{ page.props.kpis.vehiclesLeasedCount }}</span>
                    <span class="text-sm font-bold text-slate-400 uppercase tracking-wider mt-1">Active Contracts</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-sm">
                    <div class="flex items-center justify-between mb-8">
                        <h2 class="text-xl font-black text-slate-900">Payment Health</h2>
                        <span class="text-sm font-bold text-slate-400">Past 30 Days</span>
                    </div>
                    
                    <div class="flex flex-col md:flex-row items-center gap-12">
                        <div class="relative size-64">
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-4xl font-black text-slate-900">{{ totalPayments }}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase">Total Leases</span>
                            </div>
                            <canvas ref="paymentChartCanvas"></canvas>
                        </div>

                        <div class="flex-1 w-full space-y-4">
                            <div v-for="(val, key) in [
                                { label: 'Paid On Time', count: page.props.paymentStatus.paidOnTime, color: 'bg-emerald-500' },
                                { label: 'Late Payments', count: page.props.paymentStatus.late, color: 'bg-amber-500' },
                                { label: 'Overdue', count: page.props.paymentStatus.overdue, color: 'bg-rose-500' }
                            ]" :key="key" class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 border border-slate-100">
                                <div class="flex items-center gap-3">
                                    <div :class="['size-3 rounded-full', val.color]"></div>
                                    <span class="text-sm font-bold text-slate-600">{{ val.label }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="block text-sm font-black text-slate-900">{{ val.count }}</span>
                                    <span class="text-[10px] font-bold text-slate-400">{{ ((val.count / totalPayments) * 100).toFixed(0) }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-amber-50 rounded-[2rem] p-6 border border-amber-100 relative overflow-hidden group">
                        <Wrench class="absolute -right-4 -bottom-4 size-24 text-amber-200/50 rotate-12" />
                        <h3 class="text-amber-800 font-black uppercase text-xs tracking-widest mb-4">Maintenance Alerts</h3>
                        <div class="relative z-10">
                            <span class="text-4xl font-black text-amber-900">{{ page.props.maintenance.vehiclesInMaintenance }}</span>
                            <p class="text-amber-700 text-sm font-bold mt-2 leading-relaxed">
                                {{ page.props.maintenance.overdueServices }} Vehicles overdue for service.
                            </p>
                        </div>
                    </div>

                    <div class="bg-rose-50 rounded-[2rem] p-6 border border-rose-100 relative overflow-hidden group">
                        <FileWarning class="absolute -right-4 -bottom-4 size-24 text-rose-200/50 -rotate-12" />
                        <h3 class="text-rose-800 font-black uppercase text-xs tracking-widest mb-4">Documentation</h3>
                        <div class="relative z-10">
                            <span class="text-4xl font-black text-rose-900">{{ page.props.expiringDocuments }}</span>
                            <p class="text-rose-700 text-sm font-bold mt-2 leading-relaxed">
                                Documents expiring soon. Follow-up required.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-sm">
                    <h2 class="text-xl font-black text-slate-900 mb-6">Payment Follow-ups</h2>
                    <div class="space-y-4">
                        <div v-for="item in page.props.vehiclesFollowUp" :key="item.vehicle_id" 
                            class="group/item flex items-center justify-between p-4 bg-white border border-slate-100 rounded-2xl hover:border-emerald-200 hover:shadow-md hover:shadow-emerald-900/5 transition-all">
                            <div class="flex items-center gap-4">
                                <div class="size-12 bg-slate-50 rounded-xl flex items-center justify-center font-mono font-bold text-slate-400 group-hover/item:text-emerald-600 group-hover/item:bg-emerald-50 transition-colors text-xs text-center leading-tight">
                                    {{ item.vehicle_id }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-900">{{ item.driver_name }}</span>
                                        <span :class="[
                                            'text-[10px] font-black uppercase px-2 py-0.5 rounded-md',
                                            item.days_overdue >= 20 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700'
                                        ]">
                                            {{ item.days_overdue }} Days Late
                                        </span>
                                    </div>
                                    <p class="text-xs font-medium text-slate-400 mt-0.5">RM{{ item.amount }} • Due {{ item.payment_due }}</p>
                                </div>
                            </div>
                            <button class="p-2 bg-slate-50 text-slate-400 rounded-lg hover:bg-emerald-600 hover:text-white transition-all">
                                <ArrowUpRight class="size-4" />
                            </button>
                        </div>
                        <div v-if="!page.props.vehiclesFollowUp.length" class="text-center py-10 text-slate-400 font-bold">
                            All payments are currently up to date!
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-black text-slate-900">Leased Inventory</h2>
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-slate-300" />
                            <input type="text" placeholder="Quick search..." class="pl-9 pr-4 py-2 bg-slate-50 border-none rounded-xl text-xs font-bold focus:ring-2 focus:ring-emerald-500 w-48" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div v-for="vehicle in page.props.allLeasedVehicles" :key="vehicle.id" 
                            class="flex items-center gap-3 p-3 bg-slate-50/50 rounded-xl border border-transparent hover:border-slate-200 transition-all">
                            <div class="size-8 bg-white rounded-lg flex items-center justify-center shadow-sm">
                                <Car class="size-4 text-slate-400" />
                            </div>
                            <div class="truncate">
                                <p class="text-xs font-black text-slate-900">{{ vehicle.license_plate }}</p>
                                <p class="text-[10px] font-bold text-slate-400 truncate">{{ vehicle.model }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppSidebarLayout>
</template>