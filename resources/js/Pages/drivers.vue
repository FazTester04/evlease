<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { router, usePage, useForm, Link } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
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
    UserPlus
} from 'lucide-vue-next';

const props = defineProps<{
    drivers: any[];
    filters: { search?: string; availability?: string };
}>();

// ---------- Helpers ----------
// License expiry must be a future date (min = tomorrow for date input)
const minLicenseExpiryDate = (() => {
    const d = new Date();
    d.setDate(d.getDate() + 1);
    return d.toISOString().slice(0, 10);
})();

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

// ---------- Search & Filter ----------
const search = ref(props.filters?.search || '');
const availabilityFilter = ref(props.filters?.availability || '');
const today = new Date().toISOString().split('T')[0];

let searchTimeout: ReturnType<typeof setTimeout>;
watch([search, availabilityFilter], ([newSearch, newAvail]) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/drivers', { 
            search: newSearch, 
            availability: newAvail 
        }, { preserveState: true, preserveScroll: true });
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
    remarks: '',
    driver_license: '',
    license_expiry: '',
    license_document: null as File | null,
    ic_number: '',
    ic_file: null as File | null,
    status: 'active',
    remarks: '',
});

const icFileName = ref('');
const licenseFileName = ref('');

// ---------- Client‑side validation ----------
const clientErrors = ref({
    name: '',
    email: '',
    password: '',
    phone: '',
    date_of_birth: '',
    address: '',
    driver_license: '',
    license_expiry: '',
    ic_number: '',
    ic_file: '',
    license_document: '',
    status: '',
});

const validateField = (field: string) => {
    const value = createForm[field as keyof typeof createForm] as string;
    let error = '';

    if (field === 'name' && !value) error = 'Name is required.';
    if (field === 'email') {
        if (!value) error = 'Email is required.';
        else if (!/^\S+@\S+\.\S+$/.test(value)) error = 'Please enter a valid email address.';
    }
    if (field === 'password') {
        if (!value) error = 'Password is required.';
        else if (value.length < 8) error = 'Password must be at least 8 characters.';
    }
    if (field === 'phone') {
        if (!value) error = 'Phone number is required.';
        else if (!/^\+60\d{9}$/.test(value)) error = 'Phone must start with +60 and have exactly 9 digits.';
    }
    if (field === 'date_of_birth') {
        if (!value) error = 'Date of birth is required.';
        else if (new Date(value) > new Date()) error = 'Date of birth must be in the past.';
    }
    if (field === 'address' && !value) error = 'Address is required.';
    if (field === 'driver_license' && !value) error = 'Driver license number is required.';
    if (field === 'license_expiry') {
        if (!value) error = 'License expiry date is required.';
        else if (new Date(value) <= new Date()) error = 'License expiry must be in the future.';
    }
    if (field === 'ic_number') {
        if (!value) error = 'IC number is required.';
        else if (!/^\d{12}$/.test(value)) error = 'IC number must be exactly 12 digits.';
    }
    if (field === 'ic_file' && !createForm.ic_file) error = 'IC document is required.';
    if (field === 'license_document' && !createForm.license_document) error = 'License document is required.';

    clientErrors.value[field] = error;
};

const validateAll = () => {
    const fields = ['name','email','password','phone','date_of_birth','address','driver_license','license_expiry','ic_number','ic_file','license_document'];
    fields.forEach(f => validateField(f));
    return Object.values(clientErrors.value).every(e => !e);
};

const canSubmitCreate = computed(() => {
    return validateAll() && !createForm.processing;
});

const clearError = (field: string) => {
    clientErrors.value[field] = '';
};

// ---------- File handlers ----------
const handleLicenseDocumentChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        createForm.license_document = file;
        licenseFileName.value = file.name;
        validateField('license_document');
    }
};

const handleICFileChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        createForm.ic_file = file;
        icFileName.value = file.name;
        validateField('ic_file');
    }
};

const submitCreate = () => {
    if (!validateAll()) return;
    createForm.post('/admin/drivers', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
            icFileName.value = '';
            licenseFileName.value = '';
            Object.keys(clientErrors.value).forEach(k => clientErrors.value[k] = '');
        },
    });
};

// ---------- Edit Driver Modal ----------
const showEditModal = ref(false);
const editingDriver = ref<any>(null);
const editProcessing = ref(false);
const editICFile = ref<File | null>(null);

const editDriver = (driver: any) => {
    editingDriver.value = { 
        ...driver, 
        errors: {}, 
        license_document: null,
        status: driver.status || 'available',
        remarks: driver.remarks || '',
    };
    showEditModal.value = true;
};

const handleEditLicenseDocumentChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        editingDriver.value.license_document = file;
        editingDriver.value.license_file_name = file.name;
    }
};

const handleEditICFileChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        editICFile.value = file;
        editingDriver.value.ic_file_name = file.name;
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
    formData.append('ic_number', editingDriver.value.ic_number || '');
    formData.append('status', editingDriver.value.status || 'active');
    formData.append('remarks', editingDriver.value.remarks || '');
    if (editingDriver.value.license_document) {
        formData.append('license_document', editingDriver.value.license_document);
    }
    if (editICFile.value) {
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

// Notification: show after add driver, dismissible and auto-hide
const showSuccessNotification = ref(false);
const successMessage = ref('');
let successNotificationTimeout: ReturnType<typeof setTimeout>;

function showDriverAddedNotification(message: string) {
    successMessage.value = message;
    showSuccessNotification.value = true;
    clearTimeout(successNotificationTimeout);
    successNotificationTimeout = setTimeout(() => {
        showSuccessNotification.value = false;
    }, 6000);
}

function dismissSuccessNotification() {
    showSuccessNotification.value = false;
    clearTimeout(successNotificationTimeout);
}

watch(() => page.props.flash?.success, (msg) => {
    if (msg) showDriverAddedNotification(msg);
}, { immediate: true });
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

            <!-- Search & Filter -->
            <div class="mb-6 flex flex-col md:flex-row items-center gap-4">
                <div class="relative w-full md:w-96">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input v-model="search" type="text" placeholder="Search by name, email, IC, or license..." 
                        class="block w-full pl-11 pr-4 py-2.5 border border-slate-200 rounded-full text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 shadow-sm bg-white transition-all" />
                </div>

                <select v-model="availabilityFilter" class="px-4 py-2.5 border border-slate-200 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                    <option value="">All Drivers</option>
                     <option value="available">Available</option>
    <option value="on_lease">On Lease</option>
    <option value="unavailable">Unavailable</option>
                </select>
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
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Lease Status</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Driver Status</th>
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
                                    <span v-if="driver.license_document?.expiry_date" 
                                          class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                                          :class="[
                                              statusBadgeClass(driver.license_document.status),
                                              driver.license_document.status === 'expired' ? 'blink-red' : ''
                                          ]">
                                        {{ formatDate(driver.license_document.expiry_date) }}
                                        <span v-if="driver.license_document.status === 'expired'" class="ml-1">(Expired)</span>
                                    </span>
                                    <span v-else class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium animate-blink-red bg-red-500/90 text-white">No license / Expired</span>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <div v-if="driver.active_lease" class="text-sm">
                                        <Link :href="`/admin/leases/${driver.active_lease.id}`" class="text-indigo-600 hover:underline">
                                            {{ driver.active_lease.car?.license_plate }} ({{ driver.active_lease.status }})
                                        </Link>
                                        <div class="text-xs text-slate-500 mt-1">Since {{ formatDate(driver.active_lease.start_date) }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium capitalize"
                                       :class="{
              'bg-emerald-100 text-emerald-700': driver.status === 'available',
              'bg-blue-100 text-blue-700': driver.status === 'on_lease',
              'bg-red-100 text-red-700': driver.status === 'unavailable'
          }">
        {{ driver.status.replace('_', ' ') }}
                                    </span>
                                </td>
                            <td class="px-6 py-4 align-middle text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <div class="w-px h-6 bg-slate-200 mx-1"></div>
                                        <button @click="editDriver(driver)" 
                                            class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all" 
                                            title="Edit Driver">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button @click="deleteDriver(driver.id)" 
                                            class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all" 
                                            title="Delete Account">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!props.drivers?.length">
                                <td colspan="6" class="px-6 py-8 text-center text-slate-500">No drivers found.</td>
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
                    <div class="h-14 w-14 bg-emerald-600 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-200">
                        <UserPlus class="w-7 h-7" />
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight"> New Driver</h3>
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mt-1">Driver Registration System</p>
                    </div>
                </div>
                <button @click="showCreateModal = false" class="p-2 bg-white border border-slate-100 rounded-full text-slate-400 hover:text-rose-500 transition-all shadow-sm">
                    <X class="h-6 w-6" />
                </button>
            </div>

            <form @submit.prevent="submitCreate" enctype="multipart/form-data" novalidate class="p-8">
                <div class="space-y-10">
                    
                    <!-- Personal Profile -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="h-6 w-1 bg-emerald-500 rounded-full"></div>
                            <h4 class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Personal Profile</h4>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Full Name -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Full Name *</label>
                                <input v-model="createForm.name" type="text" 
                                    @input="clearError('name')" @blur="validateField('name')"
                                    class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all font-bold text-slate-900 shadow-inner" />
                                <p v-if="clientErrors.name" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ clientErrors.name }}</p>
                                <p v-if="createForm.errors.name" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ createForm.errors.name }}</p>
                            </div>

                            <!-- Email -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Email Address *</label>
                                <input v-model="createForm.email" type="email" 
                                    @input="clearError('email')" @blur="validateField('email')"
                                    class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all font-medium text-slate-700 shadow-inner" placeholder="driver@example.com" />
                                <p v-if="clientErrors.email" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ clientErrors.email }}</p>
                                <p v-if="createForm.errors.email" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ createForm.errors.email }}</p>
                            </div>

                            <!-- Password -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Account Password *</label>
                                <div class="relative">
                                    <KeyRound class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300" />
                                    <input v-model="createForm.password" type="password" 
                                        @input="clearError('password')" @blur="validateField('password')"
                                        class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-medium text-slate-700 shadow-inner" />
                                </div>
                                <p v-if="clientErrors.password" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ clientErrors.password }}</p>
                                <p v-if="createForm.errors.password" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ createForm.errors.password }}</p>
                            </div>

                            <!-- Phone -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Phone Number *</label>
                                <div class="relative">
                                    <Phone class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300" />
                                    <input v-model="createForm.phone" type="tel" 
                                        pattern="\+60\d{9}" title="Must be +60 followed by 9 digits"
                                        @input="clearError('phone')" @blur="validateField('phone')"
                                        class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-bold text-slate-900 shadow-inner" placeholder="+60123456789" />
                                </div>
                                <p v-if="clientErrors.phone" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ clientErrors.phone }}</p>
                                <p v-if="createForm.errors.phone" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ createForm.errors.phone }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Legal Identification -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="h-6 w-1 bg-emerald-500 rounded-full"></div>
                            <h4 class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Legal Identification</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- IC Number -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">IC / National ID Number *</label>
                                <input v-model="createForm.ic_number" type="text" inputmode="numeric"
                                    pattern="\d{12}" title="Exactly 12 digits"
                                    @input="clearError('ic_number')" @blur="validateField('ic_number')"
                                    class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-mono font-black text-slate-900 shadow-inner" />
                                <p v-if="clientErrors.ic_number" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ clientErrors.ic_number }}</p>
                                <p v-if="createForm.errors.ic_number" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ createForm.errors.ic_number }}</p>
                            </div>
                            <!-- Date of Birth -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Date of Birth *</label>
                                <input v-model="createForm.date_of_birth" type="date" :max="today"
                                    @input="clearError('date_of_birth')" @blur="validateField('date_of_birth')"
                                    class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl shadow-inner text-sm font-bold text-slate-700" />
                                <p v-if="clientErrors.date_of_birth" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ clientErrors.date_of_birth }}</p>
                                <p v-if="createForm.errors.date_of_birth" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ createForm.errors.date_of_birth }}</p>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="p-6 bg-slate-50 border border-slate-100 rounded-[2rem] space-y-4">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Resident Address *</label>
                            <textarea v-model="createForm.address" rows="2" 
                                @input="clearError('address')" @blur="validateField('address')"
                                class="w-full px-4 py-4 bg-white border-transparent rounded-2xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all text-sm font-medium resize-none shadow-sm" placeholder="Full residential address..."></textarea>
                            <p v-if="clientErrors.address" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ clientErrors.address }}</p>
                            <p v-if="createForm.errors.address" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ createForm.errors.address }}</p>
                        </div>
                    </div>

                    <!-- Driver Status -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="h-6 w-1 bg-emerald-500 rounded-full"></div>
                            <h4 class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Driver Status</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Status *</label>
                                <select 
                                    v-model="createForm.status" 
                                    @input="clearError('status')" 
                                    @blur="validateField('status')"
                                    class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl shadow-inner text-sm font-bold"
                                >
                                    <option value="available">Available</option>
                        
                                </select>
                                <p v-if="clientErrors.status" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ clientErrors.status }}</p>
                                <p v-if="createForm.errors.status" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ createForm.errors.status }}</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Remarks (optional)</label>
                                <input 
                                    v-model="createForm.remarks" 
                                    type="text" 
                                    class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl shadow-inner text-sm font-medium" 
                                    placeholder="Any notes about the driver"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Document Verification -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="h-6 w-1 bg-emerald-500 rounded-full"></div>
                            <h4 class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Document Verification</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- License No. -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">License No. *</label>
                                <input v-model="createForm.driver_license" type="text" 
                                    @input="clearError('driver_license')" @blur="validateField('driver_license')"
                                    class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl shadow-inner text-sm font-bold" />
                                <p v-if="clientErrors.driver_license" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ clientErrors.driver_license }}</p>
                                <p v-if="createForm.errors.driver_license" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ createForm.errors.driver_license }}</p>
                            </div>
                            <!-- License Expiry -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">License Expiry *</label>
                                <input v-model="createForm.license_expiry" type="date" :min="today"
                                    @input="clearError('license_expiry')" @blur="validateField('license_expiry')"
                                    class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl shadow-inner text-sm font-bold" />
                                <p v-if="clientErrors.license_expiry" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ clientErrors.license_expiry }}</p>
                                <p v-if="createForm.errors.license_expiry" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ createForm.errors.license_expiry }}</p>
                            </div>
                        </div>

                        <!-- File Uploads -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- IC File -->
                            <label :class="[
                                'relative group p-4 border-2 border-dashed rounded-2xl transition-all cursor-pointer flex items-center gap-4',
                                icFileName ? 'bg-emerald-50 border-emerald-400 shadow-sm' : 'bg-slate-50 border-slate-200 hover:bg-white hover:border-indigo-400'
                            ]">
                                <div :class="[
                                    'h-10 w-10 rounded-xl flex items-center justify-center shadow-sm transition-colors',
                                    icFileName ? 'bg-emerald-500 text-white' : 'bg-white text-slate-400 group-hover:text-indigo-600'
                                ]">
                                    <CheckCircle2 v-if="icFileName" class="w-5 h-5" />
                                    <FileText v-else class="w-5 h-5" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p :class="['text-xs font-black uppercase tracking-tight', icFileName ? 'text-emerald-700' : 'text-slate-700']">IC Document *</p>
                                    <p class="text-[10px] truncate text-slate-500 font-medium">{{ icFileName || 'Upload ID Copy' }}</p>
                                </div>
                                <input type="file" name="ic_file" @change="handleICFileChange" class="hidden" accept=".jpg,.jpeg,.png,.pdf" />
                            </label>
                            <p v-if="clientErrors.ic_file" class="text-[10px] text-rose-500 font-bold uppercase mt-1 col-span-2">{{ clientErrors.ic_file }}</p>
                            <p v-if="createForm.errors.ic_file" class="text-[10px] text-rose-500 font-bold uppercase mt-1 col-span-2">{{ createForm.errors.ic_file }}</p>

                            <!-- License File -->
                            <label :class="[
                                'relative group p-4 border-2 border-dashed rounded-2xl transition-all cursor-pointer flex items-center gap-4',
                                licenseFileName ? 'bg-emerald-50 border-emerald-400 shadow-sm' : 'bg-slate-50 border-slate-200 hover:bg-white hover:border-indigo-400'
                            ]">
                                <div :class="[
                                    'h-10 w-10 rounded-xl flex items-center justify-center shadow-sm transition-colors',
                                    licenseFileName ? 'bg-emerald-500 text-white' : 'bg-white text-slate-400 group-hover:text-indigo-600'
                                ]">
                                    <CheckCircle2 v-if="licenseFileName" class="w-5 h-5" />
                                    <ShieldCheck v-else class="w-5 h-5" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p :class="['text-xs font-black uppercase tracking-tight', licenseFileName ? 'text-emerald-700' : 'text-slate-700']">License Copy *</p>
                                    <p class="text-[10px] truncate text-slate-500 font-medium">{{ licenseFileName || 'Upload License' }}</p>
                                </div>
                                <input type="file" name="license_document" @change="handleLicenseDocumentChange" class="hidden" accept=".pdf,.jpg,.jpeg,.png" />
                            </label>
                            <p v-if="clientErrors.license_document" class="text-[10px] text-rose-500 font-bold uppercase mt-1 col-span-2">{{ clientErrors.license_document }}</p>
                            <p v-if="createForm.errors.license_document" class="text-[10px] text-rose-500 font-bold uppercase mt-1 col-span-2">{{ createForm.errors.license_document }}</p>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="mt-12 flex items-center gap-4 sticky bottom-0 bg-white pt-4 border-t border-slate-100">
                    <button type="button" @click="showCreateModal = false" class="flex-1 px-6 py-4 border border-slate-200 rounded-2xl text-xs font-black uppercase tracking-widest text-slate-400 hover:bg-slate-50 transition-all">
                        Cancel
                    </button>
                    <button type="submit" :disabled="createForm.processing || !canSubmitCreate" 
                        class="flex-[2] px-6 py-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-xs font-black uppercase tracking-[0.2em] shadow-xl shadow-emerald-100 transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-3">
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

            <form @submit.prevent="updateDriver" enctype="multipart/form-data" novalidate class="p-8">
                <div class="space-y-10">
                    
                    <!-- Core Profile -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="h-6 w-1 bg-emerald-500 rounded-full"></div>
                            <h4 class="text-[11px] font-black uppercase tracking-[0.2em] text-emerald-600/70">Core Profile</h4>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Full Name -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Full Name *</label>
                                <input v-model="editingDriver.name" type="text" 
                                    class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all font-bold text-slate-900 shadow-inner" required />
                                <p v-if="editingDriver.errors?.name" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ editingDriver.errors.name }}</p>
                            </div>

                            <!-- Email -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Email Address</label>
                                <input v-model="editingDriver.email" type="email" 
                                    class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all font-medium text-slate-700 shadow-inner" />
                                <p v-if="editingDriver.errors?.email" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ editingDriver.errors.email }}</p>
                            </div>

                            <!-- Phone -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Phone Number</label>
                                <div class="relative">
                                    <Phone class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300" />
                                    <input v-model="editingDriver.phone" type="tel" 
                                        pattern="\+60\d{9}" title="Must be +60 followed by 9 digits"
                                        class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border-transparent rounded-2xl focus:ring-4 focus:ring-emerald-500/10 transition-all font-bold text-slate-900 shadow-inner" />
                                </div>
                                <p v-if="editingDriver.errors?.phone" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ editingDriver.errors.phone }}</p>
                            </div>

                            <!-- IC -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">IC / National ID</label>
                                <input v-model="editingDriver.ic_number" type="text" inputmode="numeric"
                                    pattern="\d{12}" title="Exactly 12 digits"
                                    class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl shadow-inner font-mono font-bold text-slate-900 uppercase focus:ring-4 focus:ring-emerald-500/10" />
                                <p v-if="editingDriver.errors?.ic_number" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ editingDriver.errors.ic_number }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Address & License -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="h-6 w-1 bg-emerald-500 rounded-full"></div>
                            <h4 class="text-[11px] font-black uppercase tracking-[0.2em] text-emerald-600/70">Address & License</h4>
                        </div>

                        <!-- Address -->
                        <div class="p-6 bg-emerald-50/30 border border-emerald-100/50 rounded-[2rem] space-y-4 shadow-inner">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Current Residential Address</label>
                            <textarea v-model="editingDriver.address" rows="2" 
                                class="w-full px-4 py-4 bg-white border-transparent rounded-2xl focus:ring-4 focus:ring-emerald-500/10 transition-all text-sm font-medium resize-none shadow-sm"></textarea>
                            <p v-if="editingDriver.errors?.address" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ editingDriver.errors.address }}</p>
                        </div>

                        <!-- License number and expiry -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">License No.</label>
                                <input v-model="editingDriver.driver_license" type="text" class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl shadow-inner text-sm font-bold" />
                                <p v-if="editingDriver.errors?.driver_license" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ editingDriver.errors.driver_license }}</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">License Expiry</label>
                                <input v-model="editingDriver.license_expiry" type="date" :min="today" class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl shadow-inner text-sm font-bold" />
                                <p v-if="editingDriver.errors?.license_expiry" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ editingDriver.errors.license_expiry }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Driver Status -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="h-6 w-1 bg-emerald-500 rounded-full"></div>
                            <h4 class="text-[11px] font-black uppercase tracking-[0.2em] text-emerald-600/70">Driver Status</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Status *</label>
                                <select v-model="editingDriver.status"
                                    class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl shadow-inner text-sm font-bold">
                                      <option value="available">Available</option>
    <option value="on_lease">On Lease</option>
    <option value="unavailable">Unavailable</option>
                                </select>
                                <p v-if="editingDriver.errors?.status" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ editingDriver.errors.status }}</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Remarks (optional)</label>
                                <input v-model="editingDriver.remarks" type="text" 
                                    class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl shadow-inner text-sm font-medium" 
                                    placeholder="Any notes about the driver" />
                                <p v-if="editingDriver.errors?.remarks" class="text-[10px] text-rose-500 font-bold uppercase mt-1">{{ editingDriver.errors.remarks }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Update Documents -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="h-6 w-1 bg-emerald-500 rounded-full"></div>
                            <h4 class="text-[11px] font-black uppercase tracking-[0.2em] text-emerald-600/70">Update Documents</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- IC Document -->
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
                                <input type="file" @change="handleEditICFileChange" class="hidden" accept=".jpg,.jpeg,.png,.pdf" />
                            </label>
                            <p v-if="editingDriver.errors?.ic_file" class="text-[10px] text-rose-500 font-bold uppercase mt-1 col-span-2">{{ editingDriver.errors.ic_file }}</p>

                            <!-- License Document -->
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
                                <input type="file" @change="handleEditLicenseDocumentChange" class="hidden" accept=".pdf,.jpg,.jpeg,.png" />
                            </label>
                            <p v-if="editingDriver.errors?.license_document" class="text-[10px] text-rose-500 font-bold uppercase mt-1 col-span-2">{{ editingDriver.errors.license_document }}</p>
                        </div>
                    </div>
                </div>

                <!-- Action buttons -->
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
@keyframes blink-red {
    0%, 100% { opacity: 1; background-color: rgb(239 68 68 / 0.95); }
    50% { opacity: 0.75; background-color: rgb(185 28 28 / 0.95); }
}
.animate-blink-red {
    animation: blink-red 1.2s ease-in-out infinite;
}
.modal-input {
    @apply mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border outline-none transition-colors;
}
.modal-label {
    @apply block text-sm font-medium text-slate-700 mb-1;
}

@keyframes blink {
    0%, 50% { opacity: 1; }
    51%, 100% { opacity: 0.3; }
}
.blink-red {
    animation: blink 1s infinite;
    background-color: #fee2e2 !important;
    color: #b91c1c !important;
}
</style>