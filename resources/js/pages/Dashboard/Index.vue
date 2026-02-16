<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

// ---------- Use 'any' to bypass global PageProps constraint ----------
const page = usePage<any>();

// ---------- Payment Status Chart ----------
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
            labels: ['Paid on Time', 'Late Payments', 'Overdue'],
            datasets: [
                {
                    data: [paidOnTime, late, overdue],
                    backgroundColor: ['#10B981', '#F59E0B', '#EF4444'],
                    borderWidth: 0,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => {
                            const label = ctx.label || '';
                            const val = ctx.raw as number;
                            const total = paidOnTime + late + overdue;
                            const percent = total > 0 ? ((val / total) * 100).toFixed(0) : 0;
                            return `${label}: ${val} leases (${percent}%)`;
                        },
                    },
                },
            },
        },
    });
}

// ---------- Helper: total payments for percentage calculation ----------
const totalPayments = computed(() => {
    const { paidOnTime, late, overdue } = page.props.paymentStatus;
    return paidOnTime + late + overdue;
});
</script>

<template>
    <AppSidebarLayout>
        <div class="px-8 py-6 space-y-6 bg-gray-50 min-h-screen">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            </div>

            <!-- Row 1: KPI Cards -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-white rounded-lg shadow p-6 flex flex-col">
                    <span class="text-3xl font-bold text-gray-900">{{ page.props.kpis.totalVehiclesLeased }}</span>
                    <span class="text-sm text-gray-500 mt-1">Total vehicles leased</span>
                </div>
                <div class="bg-white rounded-lg shadow p-6 flex flex-col">
                    <span class="text-3xl font-bold text-gray-900">${{ page.props.kpis.monthlyRevenue }}</span>
                    <span class="text-sm text-gray-500 mt-1">Monthly Revenue</span>
                </div>
                <div class="bg-white rounded-lg shadow p-6 flex flex-col">
                    <span class="text-3xl font-bold text-gray-900">{{ page.props.kpis.overduePaymentsCount }}</span>
                    <span class="text-sm text-gray-500 mt-1">Overdue Payments</span>
                </div>
                <div class="bg-white rounded-lg shadow p-6 flex flex-col">
                    <span class="text-3xl font-bold text-gray-900">{{ page.props.kpis.vehiclesLeasedCount }}</span>
                    <span class="text-sm text-gray-500 mt-1">vehicles leased</span>
                </div>
            </div>

            <!-- Row 2: Payment Status + Notification / Maintenance & Documents -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Payment Status Overview (spans 2 cols) -->
                <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Payment Status Overview</h2>
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-8">
                        <div class="relative w-48 h-48">
                            <canvas ref="paymentChartCanvas"></canvas>
                        </div>
                        <div class="flex-1 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="w-3 h-3 rounded-full bg-green-500 mr-2"></span>
                                    <span class="text-sm text-gray-600">Paid on Time</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900">
                                    {{ page.props.paymentStatus.paidOnTime }} leases
                                    <span class="text-gray-500 ml-1">
                                        ({{ totalPayments > 0 ? ((page.props.paymentStatus.paidOnTime / totalPayments) * 100).toFixed(0) : 0 }}%)
                                    </span>
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="w-3 h-3 rounded-full bg-amber-500 mr-2"></span>
                                    <span class="text-sm text-gray-600">Late Payments</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900">
                                    {{ page.props.paymentStatus.late }} leases
                                    <span class="text-gray-500 ml-1">
                                        ({{ totalPayments > 0 ? ((page.props.paymentStatus.late / totalPayments) * 100).toFixed(0) : 0 }}%)
                                    </span>
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="w-3 h-3 rounded-full bg-red-500 mr-2"></span>
                                    <span class="text-sm text-gray-600">Overdue</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900">
                                    {{ page.props.paymentStatus.overdue }} leases
                                    <span class="text-gray-500 ml-1">
                                        ({{ totalPayments > 0 ? ((page.props.paymentStatus.overdue / totalPayments) * 100).toFixed(0) : 0 }}%)
                                    </span>
                                </span>
                            </div>
                            <div class="mt-4 p-3 bg-red-50 rounded-md border border-red-100">
                                <p class="text-sm text-red-800">
                                    <span class="font-medium">{{ page.props.kpis.overduePaymentsCount }} payments are late</span> - follow up needed
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right column: Maintenance & Documents -->
                <div class="space-y-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Vehicles in Maintenance</h3>
                        <div class="mt-2 flex items-baseline">
                            <span class="text-2xl font-bold text-gray-900">{{ page.props.maintenance.vehiclesInMaintenance }}</span>
                            <span class="ml-2 text-sm text-gray-500">vehicle currently unavailable</span>
                        </div>
                        <div class="mt-4 p-3 bg-yellow-50 rounded-md border border-yellow-100">
                            <p class="text-sm text-yellow-800">
                                <span class="font-medium">Overdue Service Maintenance</span><br>
                                {{ page.props.maintenance.overdueServices }} vehicles have overdue service - lease compliance required
                            </p>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Expired Documents</h3>
                        <div class="mt-2 flex items-baseline">
                            <span class="text-2xl font-bold text-gray-900">{{ page.props.expiringDocuments }}</span>
                            <span class="ml-2 text-sm text-gray-500">documents are expiring soon - follow up needed</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 3: Vehicles Requiring Payment Follow-up & All Leased Vehicles -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Vehicles Requiring Payment Follow-up</h2>
                    <div class="space-y-4">
                        <div v-for="item in page.props.vehiclesFollowUp" :key="item.vehicle_id" class="border-b border-gray-100 last:border-0 pb-4 last:pb-0">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-sm font-medium text-gray-900">{{ item.vehicle_id }}</span>
                                        <span v-if="item.days_overdue >= 20" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                            {{ item.days_overdue }} days overdue
                                        </span>
                                        <span v-else-if="item.days_overdue >= 3" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                            {{ item.days_overdue }} days late
                                        </span>
                                        <span v-else-if="item.days_overdue >= 1" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ item.days_overdue }} day late
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1">Driver: {{ item.driver_name }} ({{ item.driver_id }})</p>
                                    <p class="text-sm text-gray-900 font-medium mt-1">${{ Number(item.amount).toFixed(0) }}</p>
                                    <div class="text-xs text-gray-400 mt-1">
                                        Payment due: {{ item.payment_due }} - 
                                        Last paid: {{ item.last_paid }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="page.props.vehiclesFollowUp.length === 0" class="text-sm text-gray-500 italic">
                            No overdue payments.
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">All Leased Vehicles</h2>
                    <div class="space-y-2">
                        <div v-for="vehicle in page.props.allLeasedVehicles" :key="vehicle.id" class="py-2 border-b border-gray-50 last:border-0">
                            <span class="font-mono text-sm text-gray-800">{{ vehicle.license_plate }}</span>
                            <span class="ml-2 text-xs text-gray-500">{{ vehicle.model }}</span>
                        </div>
                        <div v-if="page.props.allLeasedVehicles.length === 0" class="text-sm text-gray-500 italic">
                            No active leases.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppSidebarLayout>
</template>