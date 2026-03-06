<script setup>
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    payments: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

watch(search, (value) => {
    router.get('/admin/overdue-payments', { search: value }, { preserveState: true, preserveScroll: true });
});

const formatDate = (date) => {
    return date ? new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-MY', { style: 'currency', currency: 'MYR' }).format(amount || 0);
};

const daysOverdue = (dueDate) => {
    const due = new Date(dueDate);
    const today = new Date();
    const diff = Math.floor((today - due) / (1000 * 60 * 60 * 24));
    return diff > 0 ? diff : 0;
};
</script>

<template>
    <AdminLayout>
        <Head title="Overdue Payments" />

        <div class="p-6 md:p-8 bg-slate-50 min-h-screen">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Overdue Payments</h1>
                    <p class="text-sm text-slate-500">Payments that are past their due date</p>
                </div>
            </div>

            <!-- Search -->
            <div class="mb-6">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search by car plate or driver..."
                    class="w-full md:w-96 px-4 py-2 border border-slate-200 rounded-lg"
                />
            </div>

            <!-- Payments Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Car</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Driver</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Days Overdue</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="payment in payments.data" :key="payment.id">
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ payment.lease?.car?.license_plate || 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ payment.lease?.driver?.name || 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-medium">
                                {{ formatCurrency(payment.amount) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ formatDate(payment.due_date) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">
                                    {{ daysOverdue(payment.due_date) }} days
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <Link :href="`/admin/payments/${payment.id}`" class="text-indigo-600 hover:underline">View</Link>
                            </td>
                        </tr>
                        <tr v-if="!payments.data.length">
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">No overdue payments found.</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="payments.links?.length > 3" class="px-6 py-4 border-t border-gray-200 flex justify-center">
                    <div v-html="payments.links" class="pagination"></div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>