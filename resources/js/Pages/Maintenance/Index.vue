<script setup>
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
   maintenances: {
        type: Array,
        default: () => [] // If the controller sends nothing, this stays as []
    },
    filters: Object,
});

const formatDate = (date) => {
    return date ? new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';
};

const completeMaintenance = (id) => {
    if (confirm('Mark this maintenance as complete? The car will be made available.')) {
        router.patch(route('admin.maintenance.complete', id), {}, {
            preserveScroll: true,
        });
    }
};

// Filter
const statusFilter = ref(props.filters?.status || '');
watch(statusFilter, (value) => {
    router.get('/admin/maintenance', { status: value }, { preserveState: true, preserveScroll: true });
});
</script>

<template>
    <AppSidebarLayout>
        <Head title="Maintenance" />

        <div class="p-6 md:p-8 bg-slate-50 min-h-screen">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Maintenance</h1>
                    <p class="text-sm text-slate-500">Track and complete vehicle maintenance</p>
                </div>
                <select v-model="statusFilter" class="px-4 py-2 border rounded-lg">
                    <option value="">All Statuses</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
            </div>

            <div class="bg-white rounded-xl shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vehicle</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Driver</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Scheduled</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completed</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="item in maintenances" :key="item.id">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>{{ item.car?.license_plate }}</div>
                                <div class="text-xs text-gray-500">{{ item.car?.make }} {{ item.car?.model }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ item.driver?.name || '—' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ formatDate(item.scheduled_date) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ formatDate(item.completed_date) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="{
                                    'bg-amber-100 text-amber-700': item.status === 'in_progress',
                                    'bg-emerald-100 text-emerald-700': item.status === 'completed'
                                }" class="px-2 py-1 rounded-full text-xs font-medium">
                                    {{ item.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button
                                    v-if="item.status === 'in_progress'"
                                    @click="completeMaintenance(item.id)"
                                    class="text-emerald-600 hover:text-emerald-800 font-medium"
                                >
                                    Complete
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!maintenances.length">
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">No maintenance records.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppSidebarLayout>
</template>