<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const page = usePage<any>();
const props = page.props as any;

// ---------- Helpers ----------
const formatDate = (date: string | null) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric',
    });
};

const statusBadgeClass = (status: string) => {
    switch (status?.toLowerCase()) {
        case 'valid': return 'bg-emerald-100 text-emerald-700';
        case 'expiring': return 'bg-amber-100 text-amber-700';
        case 'expired': return 'bg-red-100 text-red-700';
        default: return 'bg-slate-100 text-slate-700';
    }
};

// ---------- Search ----------
const search = ref(props.filters?.search || '');
let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/drivers', { search: value }, { preserveState: true, preserveScroll: true });
    }, 300);
});

// ---------- Create Driver Modal ----------
const showCreateModal = ref(false);
const createForm = useForm({
    name: '',
    email: '',
    password: '',
    phone: '',
    date_of_birth: '',
    address: '',
    driver_license: '',
    license_expiry: '',
    license_document: null as File | null,
});

const handleLicenseDocumentChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    createForm.license_document = target.files?.[0] || null;
};

const submitCreate = () => {
    createForm.post('/admin/drivers', {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

// ---------- Edit Driver Modal ----------
const showEditModal = ref(false);
const editingDriver = ref<any>(null);
const editProcessing = ref(false);

const editDriver = (driver: any) => {
    editingDriver.value = { ...driver, errors: {}, license_document: null };
    showEditModal.value = true;
};

const handleEditLicenseDocumentChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    editingDriver.value.license_document = target.files?.[0] || null;
};

const updateDriver = () => {
    editProcessing.value = true;
    const formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('name', editingDriver.value.name);
    formData.append('email', editingDriver.value.email || '');
    formData.append('phone', editingDriver.value.phone || '');
    formData.append('date_of_birth', editingDriver.value.date_of_birth || '');
    formData.append('address', editingDriver.value.address || '');
    formData.append('driver_license', editingDriver.value.driver_license || '');
    formData.append('license_expiry', editingDriver.value.license_expiry || '');
    if (editingDriver.value.license_document) {
        formData.append('license_document', editingDriver.value.license_document);
    }

    router.post(`/admin/drivers/${editingDriver.value.id}`, formData, {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            editingDriver.value = null;
            editProcessing.value = false;
        },
        onError: (errors) => {
            editingDriver.value.errors = errors;
            editProcessing.value = false;
        },
    });
};

const deleteDriver = (id: number) => {
    if (confirm('Delete this driver? This will also remove their lease history.')) {
        router.delete(`/admin/drivers/${id}`, { preserveScroll: true });
    }
};
</script>

<template>
    <AppSidebarLayout>
        <div class="p-6 md:p-8 bg-slate-50 min-h-screen">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Drivers</h1>
                    <p class="text-sm text-slate-500 mt-1">Manage driver profiles and licenses.</p>
                </div>
                <button @click="showCreateModal = true" class="inline-flex items-center bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg font-medium text-sm shadow-sm">
                    <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Add Driver
                </button>
            </div>

            <!-- Search -->
            <div class="mb-6 flex items-center">
                <div class="relative w-full md:w-96">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input v-model="search" type="text" placeholder="Search by name, email, or license number..." class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-full text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm bg-white" />
                </div>
            </div>

            <!-- Drivers Table -->
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50/80 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Name / Contact</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">License</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">License Expiry</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Lease</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="driver in props.drivers" :key="driver.id" class="hover:bg-slate-50/50 transition-colors group">
                                <td class="px-6 py-4 align-top">
                                    <div class="font-semibold text-slate-900">{{ driver.name }}</div>
                                    <div class="text-xs text-slate-500">{{ driver.email || '—' }}</div>
                                    <div class="text-xs text-slate-500">{{ driver.phone || '—' }}</div>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <div class="font-medium">{{ driver.driver_license || '—' }}</div>
                                    <a v-if="driver.license_document?.file_url" :href="driver.license_document.file_url" target="_blank" class="text-xs text-indigo-600 hover:underline">View License</a>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <span v-if="driver.license_document?.expiry_date" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium" :class="statusBadgeClass(driver.license_document.status)">
                                        {{ formatDate(driver.license_document.expiry_date) }}
                                    </span>
                                    <span v-else class="text-slate-400">—</span>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <div v-if="driver.active_lease" class="text-sm">
                                        <div>{{ driver.active_lease.car?.license_plate }}</div>
                                        <div class="text-xs text-slate-500">{{ driver.active_lease.car?.make }} {{ driver.active_lease.car?.model }}</div>
                                        <div class="text-xs text-slate-500">Since {{ formatDate(driver.active_lease.start_date) }}</div>
                                    </div>
                                    <span v-else class="text-slate-400 text-sm">No active lease</span>
                                </td>
                                <td class="px-6 py-4 align-top text-right">
                                    <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button @click="editDriver(driver)" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-md" title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                        <button @click="deleteDriver(driver.id)" class="p-1.5 text-red-600 hover:bg-red-50 rounded-md" title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!props.drivers?.length">
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500">No drivers found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppSidebarLayout>

    <!-- Create Driver Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showCreateModal = false">
        <div class="fixed inset-0 bg-black/50 transition-opacity" @click="showCreateModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-xl shadow-xl overflow-hidden">
                <form @submit.prevent="submitCreate" enctype="multipart/form-data">
                    <div class="px-6 py-4">
                        <h3 class="text-xl font-semibold text-slate-900 mb-2">Add New Driver</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Left column -->
                            <div class="space-y-4">
                                <div>
                                    <label class="modal-label">Name *</label>
                                    <input v-model="createForm.name" type="text" class="modal-input" required />
                                    <p v-if="createForm.errors.name" class="text-sm text-red-600 mt-1">{{ createForm.errors.name }}</p>
                                </div>
                                <div>
                                    <label class="modal-label">Email</label>
                                    <input v-model="createForm.email" type="email" class="modal-input" />
                                </div>
                                <div>
                                    <label class="modal-label">Password *</label>
                                    <input v-model="createForm.password" type="password" class="modal-input" required />
                                </div>
                                <div>
                                    <label class="modal-label">Phone</label>
                                    <input v-model="createForm.phone" type="text" class="modal-input" />
                                </div>
                                <div>
                                    <label class="modal-label">Date of Birth</label>
                                    <input v-model="createForm.date_of_birth" type="date" class="modal-input" />
                                </div>
                            </div>
                            <!-- Right column -->
                            <div class="space-y-4">
                                <div>
                                    <label class="modal-label">Address</label>
                                    <textarea v-model="createForm.address" rows="2" class="modal-input"></textarea>
                                </div>
                                <div>
                                    <label class="modal-label">Driver License Number</label>
                                    <input v-model="createForm.driver_license" type="text" class="modal-input" />
                                </div>
                                <div>
                                    <label class="modal-label">License Expiry Date</label>
                                    <input v-model="createForm.license_expiry" type="date" class="modal-input" />
                                </div>
                                <div>
                                    <label class="modal-label">License Document (PDF/JPG/PNG)</label>
                                    <input type="file" @change="handleLicenseDocumentChange" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-3 flex justify-end gap-3 border-t border-slate-200">
                        <button type="button" @click="showCreateModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 bg-white hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="createForm.processing" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm disabled:opacity-50">
                            {{ createForm.processing ? 'Saving...' : 'Create Driver' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Driver Modal -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showEditModal = false">
        <div class="fixed inset-0 bg-black/50 transition-opacity" @click="showEditModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-xl shadow-xl overflow-hidden">
                <form @submit.prevent="updateDriver" enctype="multipart/form-data">
                    <div class="px-6 py-4">
                        <h3 class="text-xl font-semibold text-slate-900 mb-2">Edit Driver</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Left column -->
                            <div class="space-y-4">
                                <div>
                                    <label class="modal-label">Name *</label>
                                    <input v-model="editingDriver.name" type="text" class="modal-input" required />
                                    <p v-if="editingDriver.errors?.name" class="text-sm text-red-600 mt-1">{{ editingDriver.errors.name }}</p>
                                </div>
                                <div>
                                    <label class="modal-label">Email</label>
                                    <input v-model="editingDriver.email" type="email" class="modal-input" />
                                </div>
                                <div>
                                    <label class="modal-label">Phone</label>
                                    <input v-model="editingDriver.phone" type="text" class="modal-input" />
                                </div>
                                <div>
                                    <label class="modal-label">Date of Birth</label>
                                    <input v-model="editingDriver.date_of_birth" type="date" class="modal-input" />
                                </div>
                            </div>
                            <!-- Right column -->
                            <div class="space-y-4">
                                <div>
                                    <label class="modal-label">Address</label>
                                    <textarea v-model="editingDriver.address" rows="2" class="modal-input"></textarea>
                                </div>
                                <div>
                                    <label class="modal-label">Driver License Number</label>
                                    <input v-model="editingDriver.driver_license" type="text" class="modal-input" />
                                </div>
                                <div>
                                    <label class="modal-label">License Expiry Date</label>
                                    <input v-model="editingDriver.license_expiry" type="date" class="modal-input" />
                                </div>
                                <div>
                                    <label class="modal-label">License Document (replace)</label>
                                    <input type="file" @change="handleEditLicenseDocumentChange" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full" />
                                    <p class="text-xs text-slate-500 mt-1">Leave empty to keep current file.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-3 flex justify-end gap-3 border-t border-slate-200">
                        <button type="button" @click="showEditModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 bg-white hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="editProcessing" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm disabled:opacity-50">
                            {{ editProcessing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
@reference "tailwindcss";
.modal-input {
    @apply mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border outline-none transition-colors;
}
.modal-label {
    @apply block text-sm font-medium text-slate-700 mb-1;
}
</style>