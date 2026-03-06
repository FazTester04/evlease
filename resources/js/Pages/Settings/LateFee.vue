<script setup>
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    late_fee_rate: String,
    late_fee_cap: String,
});

const form = useForm({
    late_fee_rate: props.late_fee_rate,
    late_fee_cap: props.late_fee_cap,
});

const submit = () => {
    form.post(route('admin.settings.late-fee.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Late Fee Settings" />
        <div class="p-6 md:p-8 max-w-2xl mx-auto">
            <h1 class="text-2xl font-bold mb-6">Late Fee Settings</h1>
            <div class="bg-white rounded-lg shadow p-6">
                <form @submit.prevent="submit">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Late Fee Rate (% per month)</label>
                            <input v-model="form.late_fee_rate" type="number" step="0.01" min="0" max="100" class="w-full border rounded-lg px-4 py-2" required />
                            <p class="text-xs text-gray-500 mt-1">Percentage of payment added each month overdue.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Late Fee Cap (% of payment)</label>
                            <input v-model="form.late_fee_cap" type="number" step="0.01" min="0" max="100" class="w-full border rounded-lg px-4 py-2" required />
                            <p class="text-xs text-gray-500 mt-1">Maximum total late fees as percentage of payment.</p>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            {{ form.processing ? 'Saving...' : 'Save Settings' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>