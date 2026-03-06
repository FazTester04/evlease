<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { router, usePage, useForm, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { 
    UserCog, 
    X, 
    Phone, 
    CheckCircle2, 
    RefreshCw, 
    Loader2,
    KeyRound,
    FileText,
    ShieldCheck,
    UserPlus // Added this since you have a Create modal too
} from 'lucide-vue-next';

const props = defineProps<{
    drivers: any[];
    filters: { search?: string };
}>();

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
    ic_number: '',               // <-- new IC number field
    ic_file: null as File | null, // <-- new IC file field
});

const icFileName = ref('');
const licenseFileName = ref('');


const handleLicenseDocumentChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        createForm.license_document = file;
        // UPDATE THIS LINE:
        createForm.license_file_name = file.name; 
    }
};
const handleICFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        createForm.ic_file = file;
        // UPDATE THIS LINE:
        createForm.ic_file_name = file.name;
    }
};

const submitCreate = () => {
    createForm.post('/admin/drivers', {
        forceFormData: true, // required for file uploads
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
const editICFile = ref<File | null>(null); // separate ref for edit IC file

const editDriver = (driver: any) => {
    // Clone driver and add errors placeholder
    editingDriver.value = { ...driver, errors: {}, license_document: null };
    editICFile.value = null; // reset file input
    showEditModal.value = true;
};

const handleEditLicenseDocumentChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        editingDriver.license_document = file;
        editingDriver.license_file_name = file.name; // This enables the icon!
    }
};

const handleEditICFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        editingDriver.ic_document = file;
        editingDriver.ic_file_name = file.name; // This enables the icon!
    }
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
    formData.append('ic_number', editingDriver.value.ic_number || ''); // <-- add IC number
    if (editingDriver.value.license_document) {
        formData.append('license_document', editingDriver.value.license_document);
    }
    if (editICFile.value) { // <-- add IC file if changed
        formData.append('ic_file', editICFile.value);
    }

    router.post(`/admin/drivers/${editingDriver.value.id}`, formData, {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            editingDriver.value = null;
            editProcessing.value = false;
            editICFile.value = null;
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
                    <p class="text-sm text-slate-500 mt-1">Manage driver profiles, licenses, and identification.</p>
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
                    <input v-model="search" type="text" placeholder="Search by name, email, IC, or license..." class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-full text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm bg-white" />
                </div>
            </div>

            <!-- Drivers Table -->
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50/80 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Driver / IC</th>
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
                                    <div class="text-xs text-slate-500 mt-1">IC: {{ driver.ic_number || '—' }}</div>
                                    <a v-if="driver.ic_document?.file_url" :href="driver.ic_document.file_url" target="_blank" class="text-xs text-indigo-600 hover:underline inline-block mt-1">View IC</a>
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
<td class="px-6 py-4 align-middle text-right">
    <div class="flex items-center justify-end gap-1">
        
        <!-- <Link :href="`/chat/${driver.id}`" 
            class="group relative p-2 text-blue-600 hover:bg-blue-600 hover:text-white rounded-xl transition-all duration-300 active:scale-90 shadow-sm hover:shadow-blue-200" 
            title="Send Message">
            <svg class="w-5 h-5 relative z-10 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <span class="absolute top-1.5 right-1.5 flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-20"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500 border border-white"></span>
            </span>
        </Link> -->

        <div class="w-px h-6 bg-slate-200 mx-1"></div>

        <button @click="editDriver(driver)" 
            class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all" 
            title="Edit Driver">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
        </button>

        <button @click="deleteDriver(driver.id)" 
            class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" 
            title="Delete Account">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
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
<div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity" @click="showCreateModal = false"></div>

    <div class="relative w-full max-w-2xl bg-white rounded-[2.5rem] shadow-2xl overflow-hidden transform transition-all animate-in fade-in zoom-in duration-300 max-h-[90vh] overflow-y-auto">
        
        <div class="bg-slate-50 border-b border-slate-100 p-8 flex items-center justify-between sticky top-0 z-30">
            <div class="flex items-center gap-5">
                <div class="h-14 w-14 bg-indigo-600 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-200">
                    <UserPlus class="w-7 h-7" />
                </div>
                <div>
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Onboard New Driver</h3>
                    <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mt-1">Personnel Registration System</p>
                </div>
            </div>
            <button @click="showCreateModal = false" class="p-2 bg-white border border-slate-100 rounded-full text-slate-400 hover:text-rose-500 transition-all shadow-sm">
                <X class="h-6 w-6" />
            </button>
        </div>

        <form @submit.prevent="submitCreate" enctype="multipart/form-data" class="p-8">
            <div class="space-y-10">
                
                <div class="space-y-6">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="h-6 w-1 bg-indigo-500 rounded-full"></div>
                        <h4 class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Personal Profile</h4>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Full Name *</label>
                            <input v-model="createForm.name" type="text" 
                                class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-bold text-slate-900 shadow-inner" required />
                            <p v-if="createForm.errors.name" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ createForm.errors.name }}</p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Email Address</label>
                            <input v-model="createForm.email" type="email" 
                                class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-medium text-slate-700 shadow-inner" placeholder="driver@example.com" />
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Account Password *</label>
                            <div class="relative">
                                <KeyRound class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300" />
                                <input v-model="createForm.password" type="password" 
                                    class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-medium text-slate-700 shadow-inner" required />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Phone Number</label>
                            <div class="relative">
                                <Phone class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300" />
                                <input v-model="createForm.phone" type="text" 
                                    class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-bold text-slate-900 shadow-inner" placeholder="+60..." />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="h-6 w-1 bg-indigo-500 rounded-full"></div>
                        <h4 class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Legal Identification</h4>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">IC / National ID Number *</label>
                            <input v-model="createForm.ic_number" type="text" 
                                class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-mono font-black text-slate-900 shadow-inner" required />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Date of Birth</label>
                            <input v-model="createForm.date_of_birth" type="date" 
                                class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl shadow-inner text-sm font-bold text-slate-700" />
                        </div>
                    </div>

                    <div class="p-6 bg-slate-50 border border-slate-100 rounded-[2rem] space-y-4">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Resident Address</label>
                        <textarea v-model="createForm.address" rows="2" 
                            class="w-full px-4 py-4 bg-white border-transparent rounded-2xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all text-sm font-medium resize-none shadow-sm" placeholder="Full residential address..."></textarea>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="h-6 w-1 bg-indigo-500 rounded-full"></div>
                        <h4 class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Document Verification</h4>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">License No.</label>
                            <input v-model="createForm.driver_license" type="text" class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl shadow-inner text-sm font-bold" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">License Expiry</label>
                            <input v-model="createForm.license_expiry" type="date" class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl shadow-inner text-sm font-bold" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label :class="[
                            'relative group p-4 border-2 border-dashed rounded-2xl transition-all cursor-pointer flex items-center gap-4',
                            createForm.ic_file_name 
                                ? 'bg-emerald-50 border-emerald-400 shadow-sm' 
                                : 'bg-slate-50 border-slate-200 hover:bg-white hover:border-indigo-400'
                        ]">
                            <div :class="[
                                'h-10 w-10 rounded-xl flex items-center justify-center shadow-sm transition-colors',
                                createForm.ic_file_name ? 'bg-emerald-500 text-white' : 'bg-white text-slate-400 group-hover:text-indigo-600'
                            ]">
                                <CheckCircle2 v-if="createForm.ic_file_name" class="w-5 h-5" />
                                <FileText v-else class="w-5 h-5" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p :class="['text-xs font-black uppercase tracking-tight', createForm.ic_file_name ? 'text-emerald-700' : 'text-slate-700']">
                                    IC Document *
                                </p>
                                <p class="text-[10px] truncate text-slate-500 font-medium">
                                    {{ createForm.ic_file_name || 'Upload ID Copy' }}
                                </p>
                            </div>
                            <input type="file" @change="handleICFileChange" class="hidden" required />
                        </label>

                        <label :class="[
                            'relative group p-4 border-2 border-dashed rounded-2xl transition-all cursor-pointer flex items-center gap-4',
                            createForm.license_file_name 
                                ? 'bg-emerald-50 border-emerald-400 shadow-sm' 
                                : 'bg-slate-50 border-slate-200 hover:bg-white hover:border-indigo-400'
                        ]">
                            <div :class="[
                                'h-10 w-10 rounded-xl flex items-center justify-center shadow-sm transition-colors',
                                createForm.license_file_name ? 'bg-emerald-500 text-white' : 'bg-white text-slate-400 group-hover:text-indigo-600'
                            ]">
                                <CheckCircle2 v-if="createForm.license_file_name" class="w-5 h-5" />
                                <ShieldCheck v-else class="w-5 h-5" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p :class="['text-xs font-black uppercase tracking-tight', createForm.license_file_name ? 'text-emerald-700' : 'text-slate-700']">
                                    License Copy
                                </p>
                                <p class="text-[10px] truncate text-slate-500 font-medium">
                                    {{ createForm.license_file_name || 'Upload License' }}
                                </p>
                            </div>
                            <input type="file" @change="handleLicenseDocumentChange" class="hidden" />
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-12 flex items-center gap-4 sticky bottom-0 bg-white pt-4 border-t border-slate-100">
                <button type="button" @click="showCreateModal = false" 
                    class="flex-1 px-6 py-4 border border-slate-200 rounded-2xl text-xs font-black uppercase tracking-widest text-slate-400 hover:bg-slate-50 transition-all">
                    Cancel
                </button>
                <button type="submit" :disabled="createForm.processing" 
                    class="flex-[2] px-6 py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl text-xs font-black uppercase tracking-[0.2em] shadow-xl shadow-indigo-100 transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-3">
                    <Loader2 v-if="createForm.processing" class="h-5 w-5 animate-spin" />
                    <span>{{ createForm.processing ? 'Registering...' : 'Confirm Registration' }}</span>
                </button>
            </div>
        </form>
    </div>
</div>

    <!-- Edit Driver Modal -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity" @click="showEditModal = false"></div>

    <div class="relative w-full max-w-2xl bg-white rounded-[2.5rem] shadow-2xl overflow-hidden transform transition-all animate-in fade-in zoom-in duration-300 max-h-[90vh] overflow-y-auto">
        
        <div class="bg-emerald-50/50 border-b border-emerald-100 p-8 flex items-center justify-between sticky top-0 z-30 backdrop-blur-sm">
            <div class="flex items-center gap-5">
                <div class="h-14 w-14 bg-emerald-600 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-200">
                    <UserCog class="w-7 h-7" />
                </div>
                <div>
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Edit Driver Details</h3>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="px-2 py-0.5 bg-emerald-600 text-white text-[10px] font-bold uppercase rounded tracking-wider">
                            ID: {{ editingDriver?.id }}
                        </span>
                        <span class="text-slate-400 text-sm font-bold uppercase tracking-tighter">
                            {{ editingDriver?.name }}
                        </span>
                    </div>
                </div>
            </div>
            <button @click="showEditModal = false" class="p-2 bg-white border border-slate-100 rounded-full text-slate-400 hover:text-rose-500 transition-all shadow-sm">
                <X class="h-6 w-6" />
            </button>
        </div>

        <form @submit.prevent="updateDriver" enctype="multipart/form-data" class="p-8">
            <div class="space-y-10">
                
                <div class="space-y-6">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="h-6 w-1 bg-emerald-500 rounded-full"></div>
                        <h4 class="text-[11px] font-black uppercase tracking-[0.2em] text-emerald-600/70">Core Profile</h4>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Full Name *</label>
                            <input v-model="editingDriver.name" type="text" 
                                class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all font-bold text-slate-900 shadow-inner" required />
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Email Address</label>
                            <input v-model="editingDriver.email" type="email" 
                                class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all font-medium text-slate-700 shadow-inner" />
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Phone Number</label>
                            <div class="relative">
                                <Phone class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300" />
                                <input v-model="editingDriver.phone" type="text" 
                                    class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border-transparent rounded-2xl focus:ring-4 focus:ring-emerald-500/10 transition-all font-bold text-slate-900 shadow-inner" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">IC / National ID</label>
                            <input v-model="editingDriver.ic_number" type="text" 
                                class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl shadow-inner font-mono font-bold text-slate-900 uppercase focus:ring-4 focus:ring-emerald-500/10" />
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="h-6 w-1 bg-emerald-500 rounded-full"></div>
                        <h4 class="text-[11px] font-black uppercase tracking-[0.2em] text-emerald-600/70">Address & License</h4>
                    </div>

                    <div class="p-6 bg-emerald-50/30 border border-emerald-100/50 rounded-[2rem] space-y-4 shadow-inner">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Current Residential Address</label>
                        <textarea v-model="editingDriver.address" rows="2" 
                            class="w-full px-4 py-4 bg-white border-transparent rounded-2xl focus:ring-4 focus:ring-emerald-500/10 transition-all text-sm font-medium resize-none shadow-sm"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">License No.</label>
                            <input v-model="editingDriver.driver_license" type="text" class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl shadow-inner text-sm font-bold" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">License Expiry</label>
                            <input v-model="editingDriver.license_expiry" type="date" class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl shadow-inner text-sm font-bold" />
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="h-6 w-1 bg-emerald-500 rounded-full"></div>
                        <h4 class="text-[11px] font-black uppercase tracking-[0.2em] text-emerald-600/70">Update Documents</h4>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label :class="[
                            'relative group p-4 border-2 border-dashed rounded-2xl transition-all cursor-pointer flex items-center gap-4',
                            editingDriver.ic_file_name 
                                ? 'bg-emerald-50 border-emerald-500 ring-2 ring-emerald-500/10' 
                                : 'bg-slate-50 border-slate-200 hover:border-emerald-400 hover:bg-emerald-50/30'
                        ]">
                            <div :class="['h-10 w-10 rounded-xl flex items-center justify-center shadow-sm transition-colors', editingDriver.ic_file_name ? 'bg-emerald-600 text-white' : 'bg-white text-slate-400 group-hover:text-emerald-600']">
                                <CheckCircle2 v-if="editingDriver.ic_file_name" class="w-5 h-5" />
                                <RefreshCw v-else class="w-5 h-5" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p :class="['text-xs font-black uppercase tracking-tight', editingDriver.ic_file_name ? 'text-emerald-700' : 'text-slate-700']">IC Document</p>
                                <p class="text-[10px] truncate text-slate-400 italic">
                                    {{ editingDriver.ic_file_name || 'Upload to replace existing' }}
                                </p>
                            </div>
                            <input type="file" @change="handleEditICFileChange" class="hidden" />
                        </label>

                        <label :class="[
                            'relative group p-4 border-2 border-dashed rounded-2xl transition-all cursor-pointer flex items-center gap-4',
                            editingDriver.license_file_name 
                                ? 'bg-emerald-50 border-emerald-500 ring-2 ring-emerald-500/10' 
                                : 'bg-slate-50 border-slate-200 hover:border-emerald-400 hover:bg-emerald-50/30'
                        ]">
                            <div :class="['h-10 w-10 rounded-xl flex items-center justify-center shadow-sm transition-colors', editingDriver.license_file_name ? 'bg-emerald-600 text-white' : 'bg-white text-slate-400 group-hover:text-emerald-600']">
                                <CheckCircle2 v-if="editingDriver.license_file_name" class="w-5 h-5" />
                                <RefreshCw v-else class="w-5 h-5" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p :class="['text-xs font-black uppercase tracking-tight', editingDriver.license_file_name ? 'text-emerald-700' : 'text-slate-700']">License Copy</p>
                                <p class="text-[10px] truncate text-slate-400 italic">
                                    {{ editingDriver.license_file_name || 'Upload to replace existing' }}
                                </p>
                            </div>
                            <input type="file" @change="handleEditLicenseDocumentChange" class="hidden" />
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-12 flex items-center gap-4 sticky bottom-0 bg-white pt-4 border-t border-slate-100">
                <button type="button" @click="showEditModal = false" 
                    class="flex-1 px-6 py-4 border border-slate-200 rounded-2xl text-xs font-black uppercase tracking-widest text-slate-400 hover:bg-slate-50 transition-all">
                    Discard
                </button>
                <button type="submit" :disabled="editProcessing" 
                    class="flex-[2] px-6 py-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-xs font-black uppercase tracking-[0.2em] shadow-xl shadow-emerald-100 transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-3">
                    <Loader2 v-if="editProcessing" class="h-5 w-5 animate-spin" />
                    <span>{{ editProcessing ? 'Updating...' : 'Save Driver Changes' }}</span>
                </button>
            </div>
        </form>
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