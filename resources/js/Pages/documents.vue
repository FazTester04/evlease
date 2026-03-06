<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const page = usePage<any>();
const props = page.props as any;

const formatDate = (date: string | null) => date ? new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';

// ---------- Filters ----------
const search = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || '');

let searchTimeout: ReturnType<typeof setTimeout>;
watch([search, typeFilter], ([newSearch, newType]) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/documents', { 
            search: newSearch, 
            type: newType
        }, { preserveState: true, preserveScroll: true });
    }, 300);
});

const deleteDocument = (id: number) => {
    if (confirm('Delete this document?')) {
        router.delete(`/admin/documents/${id}`, { preserveScroll: true });
    }
};
</script>

<template>
    <AppSidebarLayout>
        <div class="p-6 md:p-8 bg-slate-50 min-h-screen">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Documents</h1>
                    <p class="text-sm text-slate-500 mt-1">Vehicle insurance, road tax, driver licenses, and payment receipts.</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="mb-6 flex flex-col sm:flex-row gap-4 flex-wrap">
                <div class="relative w-full sm:w-64">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input v-model="search" type="text" placeholder="Search documents..." class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-full text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm bg-white" />
                </div>
                <select v-model="typeFilter" class="px-4 py-2.5 border border-slate-200 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                    <option value="">All Types</option>
                    <option value="road_tax">Road Tax</option>
                    <option value="insurance">Insurance</option>
                    <option value="driver_license">Driver License</option>
                    <option value="payment_receipt">Payment Receipt</option>
                </select>
            </div>

            <!-- Documents Table -->
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50/80 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Document</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Related To</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Expiry Date</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Attachment</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="doc in props.documents" :key="doc.id" class="hover:bg-slate-50/50 group">
                                <td class="px-6 py-4 align-top font-medium">{{ doc.name }}</td>
                                <td class="px-6 py-4 align-top">
                                    <template v-if="doc.car">
                                        Car: {{ doc.car.license_plate }}
                                    </template>
                                    <template v-else-if="doc.driver">
                                        Driver: {{ doc.driver.name }}
                                    </template>
                                    <template v-else-if="doc.lease_payment?.lease?.car">
                                        Payment Receipt for {{ doc.lease_payment.lease.car.license_plate }}
                                        <span v-if="doc.lease_payment.lease.driver" class="text-xs text-slate-500 block">
                                            Driver: {{ doc.lease_payment.lease.driver.name }}
                                        </span>
                                    </template>
                                    <span v-else>—</span>
                                </td>
                                <td class="px-6 py-4 align-top">{{ formatDate(doc.expiry_date) }}</td>
                                <td class="px-6 py-4 align-top">
                                    <a v-if="doc.file_url" :href="doc.file_url" target="_blank" class="text-indigo-600 hover:underline">View</a>
                                    <span v-else class="text-slate-400">—</span>
                                </td>
                                <td class="px-6 py-4 align-middle text-right">
                                    <div class="flex items-center justify-end">
                                        <button 
                                            @click="deleteDocument(doc.id)" 
                                            class="group flex items-center gap-2 px-3 py-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all duration-200 active:scale-95" 
                                            title="Remove Document"
                                        >
                                            <span class="text-xs font-semibold opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                                Delete
                                            </span>
                                            <div class="p-1 group-hover:bg-red-100 rounded-md transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </div>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!props.documents?.length">
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500">No documents found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppSidebarLayout>
</template>

<style scoped>
@reference "tailwindcss";
</style>