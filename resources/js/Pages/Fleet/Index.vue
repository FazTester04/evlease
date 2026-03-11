<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { 
    CreditCard, X, Calendar, Clock, UploadCloud, CheckCircle2, Loader2,
    Settings2, ChevronDown, FileUp, Upload
} from 'lucide-vue-next';

const page = usePage<any>();

// Reactive data from Inertia props
const cars = computed(() => page.props.cars || []);
const filters = computed(() => page.props.filters || { search: '', status: '' });

// ---------- Helpers ----------
const formatDate = (dateString: string | null) => {
    if (!dateString || dateString === '—') return '—';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric',
    });
};

const today = new Date().toISOString().split('T')[0];

// ---------- Filters ----------
const search = ref(filters.value.search || '');
const statusFilter = ref(filters.value.status || '');

let searchTimeout: ReturnType<typeof setTimeout>;
watch([search, statusFilter], ([newSearch, newStatus]) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/vehicles', { 
            search: newSearch, 
            status: newStatus 
        }, { preserveState: true, preserveScroll: true });
    }, 300);
});

// ---------- Add Vehicle Modal ----------
const showAddModal = ref(false);
const addForm = useForm({
    license_plate: '',
    vin: '',
    make: '',
    model: '',
    year: new Date().getFullYear(),
    color: '',
    remarks: '',
    status: 'available',
    // Insurance fields
    insurance_provider: '',
    insurance_policy_number: '',
    insurance_expiry_date: '',
    insurance_file: null as File | null,
    // Road tax fields
    road_tax_number: '',
    road_tax_expiry_date: '',
    road_tax_file: null as File | null,
});

const insuranceFileName = ref('');
const roadTaxFileName = ref('');

const handleInsuranceFileChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        addForm.insurance_file = file;
        insuranceFileName.value = file.name;
    }
};

const handleRoadTaxFileChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        addForm.road_tax_file = file;
        roadTaxFileName.value = file.name;
    }
};

const submitAdd = () => {
    addForm.post('/admin/cars', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showAddModal.value = false;
            addForm.reset();
            insuranceFileName.value = '';
            roadTaxFileName.value = '';
            router.reload({ only: ['cars'] });
        },
    });
};

// ---------- Edit Vehicle ----------
const showEditCarModal = ref(false);
const editingCar = ref<any>(null);
const editCarProcessing = ref(false);

const editCar = (car: any) => {
    editingCar.value = { ...car, errors: {} };
    showEditCarModal.value = true;
};

const updateCar = () => {
    editCarProcessing.value = true;
    const { errors, ...carData } = editingCar.value;
    router.put(`/admin/cars/${editingCar.value.id}`, carData, {
        preserveScroll: true,
        onSuccess: () => {
            showEditCarModal.value = false;
            editingCar.value = null;
            editCarProcessing.value = false;
        },
        onError: (errors) => {
            editingCar.value.errors = errors;
            editCarProcessing.value = false;
        },
        onFinish: () => {
            editCarProcessing.value = false;
        },
    });
};

const deleteCar = (id: number) => {
    if (confirm('Are you sure?')) {
        router.delete(`/admin/cars/${id}`, { preserveScroll: true });
    }
};

// ---------- Document Upload ----------
const docForm = useForm({
    car_id: '',
    type: 'insurance',
    document: null as File | null,
    expiry_date: '',
    status: 'valid',
});

const showDocModal = ref(false);
const selectedCarForDoc = ref<any>(null);

const openDocModal = (car: any) => {
    if (!car?.id) return;
    selectedCarForDoc.value = car;
    docForm.car_id = String(car.id);
    showDocModal.value = true;
};

const handleDocFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    docForm.document = target.files?.[0] || null;
};

const submitDoc = () => {
    docForm.post('/admin/documents/upload', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            showDocModal.value = false;
            docForm.reset();
        },
    });
};

// ---------- Payment Recording ----------
const showPaymentModal = ref(false);
const selectedCarForPayment = ref<any>(null);
const paymentForm = useForm({
    lease_id: '',
    amount: '',
    paid_date: new Date().toISOString().split('T')[0],
    due_date: '',
    proof: null as File | null,
});

const handlePaymentProofChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        paymentForm.receipt = file;
        paymentForm.receipt_name = file.name;
    }
};

const openPaymentModal = (car: any) => {
    selectedCarForPayment.value = car;
    paymentForm.lease_id = car.active_lease_id;
    const today = new Date();
    paymentForm.paid_date = today.toISOString().split('T')[0];
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate();
    const dueDate = new Date(today.getFullYear(), today.getMonth(), lastDay);
    paymentForm.due_date = dueDate.toISOString().split('T')[0];
    paymentForm.amount = car.current_driver?.monthly_payment || '';
    showPaymentModal.value = true;
};

const submitPayment = () => {
    paymentForm.post('/admin/payments/record', {
        preserveScroll: true,
        onSuccess: () => {
            showPaymentModal.value = false;
            paymentForm.reset();
        },
    });
};

// ---------- Maintenance ----------
const setMaintenance = (id: number) => {
    if (confirm('Move this vehicle to maintenance? Any active lease will be paused.')) {
        router.patch(`/admin/vehicles/${id}/maintenance`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ only: ['cars'] });
            },
        });
    }
};
</script>

<template>
    <AppSidebarLayout>
        <div class="p-6 md:p-8 bg-slate-50 min-h-screen">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Vehicles</h1>
                    <p class="text-sm text-slate-500 mt-1">Manage your fleet, assignments, and documents.</p>
                </div>
                <button @click="showAddModal = true" class="inline-flex items-center bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-lg font-medium text-sm shadow-sm transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add Vehicle
                </button>
            </div>

            <!-- Filters -->
            <div class="mb-6 flex flex-col sm:flex-row gap-4">
                <div class="relative w-full sm:w-96">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input v-model="search" type="text" placeholder="Search by plate or VIN..." class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-full text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm bg-white" />
                </div>
                <select v-model="statusFilter" class="px-4 py-2.5 border border-slate-200 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                    <option value="">All Statuses</option>
                    <option value="available">Available</option>
                    <option value="leased">Leased</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>

            <!-- Vehicles Table -->
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50/80 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Vehicle ID</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Details</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Documents</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Driver / Lease</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="car in cars" :key="car.id" class="hover:bg-slate-50/50 transition-colors group">
                                <td class="px-6 py-4 align-top">
                                    <div class="font-semibold text-slate-900">{{ car.license_plate }}</div>
                                    <div class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path></svg>
                                        {{ car.vin || '—' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <div class="text-sm font-medium text-slate-900">{{ car.year }} {{ car.make }} {{ car.model }}</div>
                                    <div v-if="car.color" class="text-xs text-slate-500 mt-1">Color: {{ car.color }}</div>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium capitalize"
                                        :class="{
                                            'bg-emerald-100 text-emerald-700': car.status === 'available',
                                            'bg-blue-100 text-blue-700': car.status === 'leased',
                                            'bg-amber-100 text-amber-700': car.status === 'maintenance'
                                        }">
                                        {{ car.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 align-top space-y-2">
                                    <div class="text-sm">
                                        <span class="text-slate-500 text-xs uppercase tracking-wider block mb-0.5">Road Tax</span>
                                        <span v-if="car.road_tax && car.road_tax.status !== 'Not Uploaded'" class="flex items-center gap-1.5">
                                            <div class="w-2 h-2 rounded-full" :class="car.road_tax.status === 'Valid' ? 'bg-emerald-500' : 'bg-red-500'"></div>
                                            <span class="font-medium text-slate-700">{{ car.road_tax.status }}</span>
                                            <span v-if="car.road_tax.expiry_date" class="text-xs text-slate-400">({{ formatDate(car.road_tax.expiry_date) }})</span>
                                        </span>
                                        <span v-else class="text-slate-400 text-sm italic">Pending</span>
                                    </div>
                                    <div class="text-sm mt-2">
                                        <span class="text-slate-500 text-xs uppercase tracking-wider block mb-0.5">Insurance</span>
                                        <span v-if="car.insurance && car.insurance.status !== 'Not Uploaded'" class="flex items-center gap-1.5">
                                            <div class="w-2 h-2 rounded-full" :class="car.insurance.status === 'Valid' ? 'bg-emerald-500' : 'bg-red-500'"></div>
                                            <span class="font-medium text-slate-700">{{ car.insurance.status }}</span>
                                            <span v-if="car.insurance.expiry_date" class="text-xs text-slate-400">({{ formatDate(car.insurance.expiry_date) }})</span>
                                        </span>
                                        <span v-else class="text-slate-400 text-sm italic">Pending</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <div v-if="car.current_driver" class="bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                                        <div class="font-medium text-slate-900 text-sm">{{ car.current_driver.name }}</div>
                                        <div class="text-xs text-slate-500 mt-1">ID: {{ car.current_driver.driver_license }}</div>
                                    </div>
                                    <span v-else class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-500">Unassigned</span>
                                </td>
                                <td class="px-6 py-4 align-middle text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <div class="flex items-center bg-slate-50 border border-slate-200/60 rounded-lg p-0.5 mr-1">
                                            <!-- <button v-if="car.active_lease_id" @click="openPaymentModal(car)" class="p-2 text-indigo-600 hover:bg-white hover:shadow-sm rounded-md transition-all active:scale-90" title="Record Payment">
                                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            </button> -->

                                        </div>
                                        <button @click="openDocModal(car)" class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all" title="Documents">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </button>
                                        <button @click="editCar(car)" class="p-2 text-slate-400 hover:text-emerald-900 hover:bg-emerald-100 rounded-lg transition-all" title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>
                                        <button @click="deleteCar(car.id)" class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all" title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                        <button 
    v-if="car.status !== 'maintenance'" 
    @click="setMaintenance(car.id)" 
    class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all" 
    title="Move to Maintenance"
>
    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
            d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
    </svg>
</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!cars.length">
                                <td colspan="6" class="px-6 py-8 text-center text-slate-500">No vehicles found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppSidebarLayout>

    <!-- Add Vehicle Modal -->
    <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showAddModal = false"></div>

        <div class="relative w-full max-w-2xl bg-white rounded-[2.5rem] shadow-2xl overflow-hidden transform transition-all animate-in fade-in zoom-in duration-200 max-h-[90vh] flex flex-col">
            
            <div class="bg-slate-50 border-b border-slate-100 p-8 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-slate-900 leading-tight">Add New Vehicle</h3>
                        <p class="text-[10px] text-slate-400 font-bold mt-1 uppercase tracking-widest">Fleet Expansion Entry</p>
                    </div>
                </div>
                <button @click="showAddModal = false" class="text-slate-400 hover:text-rose-500 transition-colors">
                    <X class="h-6 w-6" />
                </button>
            </div>

            <form @submit.prevent="submitAdd" class="overflow-y-auto p-8 flex-1">
                <div class="space-y-10">
                    
                    <div class="space-y-6">
                        <div class="flex items-center gap-2">
                            <span class="h-1 w-8 bg-emerald-600 rounded-full"></span>
                            <h4 class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Identification</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-700 ml-1">License Plate *</label>
                                <input v-model="addForm.license_plate" type="text" 
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all font-mono font-bold text-slate-900 uppercase placeholder:text-slate-300" 
                                    placeholder="WXX 1234" required />
                                <p v-if="addForm.errors.license_plate" class="text-[10px] text-red-500 font-bold uppercase mt-1 ml-1">{{ addForm.errors.license_plate }}</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-700 ml-1">VIN (Chassis Number)</label>
                                <input v-model="addForm.vin" type="text" 
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all font-mono text-sm text-slate-600 uppercase" />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="flex items-center gap-2">
                            <span class="h-1 w-8 bg-emerald-600 rounded-full"></span>
                            <h4 class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Specifications</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-700 ml-1">Make *</label>
                                <input v-model="addForm.make" type="text" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm font-medium" placeholder="e.g. Proton" required />
                                <p v-if="addForm.errors.make" class="text-[10px] text-red-500 font-bold uppercase mt-1">{{ addForm.errors.make }}</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-700 ml-1">Model *</label>
                                <input v-model="addForm.model" type="text" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm font-medium" placeholder="e.g. Saga" required />
                                <p v-if="addForm.errors.model" class="text-[10px] text-red-500 font-bold uppercase mt-1">{{ addForm.errors.model }}</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-700 ml-1">Year *</label>
                                <input v-model="addForm.year" type="number" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm font-medium" required />
                                <p v-if="addForm.errors.year" class="text-[10px] text-red-500 font-bold uppercase mt-1">{{ addForm.errors.year }}</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-700 ml-1">Color *</label>
                                <input v-model="addForm.color" type="text" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm font-medium" placeholder="e.g. White" required />
                                <p v-if="addForm.errors.color" class="text-[10px] text-red-500 font-bold uppercase mt-1">{{ addForm.errors.color }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Insurance Details -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-2">
                            <div class="h-6 w-1 bg-emerald-500 rounded-full"></div>
                            <h4 class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Insurance Details</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-700 ml-1">Provider *</label>
                                <input v-model="addForm.insurance_provider" type="text" class="w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm font-medium" placeholder="e.g. Allianz" required />
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-700 ml-1">Policy Number *</label>
                                <input v-model="addForm.insurance_policy_number" type="text" class="w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm font-medium" placeholder="e.g. POL-12345" required />
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-700 ml-1">Expiry Date *</label>
                                <input v-model="addForm.insurance_expiry_date" type="date" :min="today" class="w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm font-medium" required />
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-700 ml-1">Policy Document *</label>
                                <div class="relative group">
                                    <input type="file" @change="handleInsuranceFileChange" accept=".pdf,.jpg,.jpeg,.png" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required />
                                    <div class="px-4 py-3 bg-white border border-dashed border-slate-300 rounded-xl group-hover:border-emerald-500 group-hover:bg-emerald-50 transition-all flex items-center justify-between">
                                        <span class="text-xs text-slate-500 truncate pr-4">{{ insuranceFileName || 'Select PDF/Image' }}</span>
                                        <Upload class="h-4 w-4 text-slate-400 group-hover:text-emerald-600" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Road Tax Details -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-2">
                            <div class="h-6 w-1 bg-emerald-500 rounded-full"></div>
                            <h4 class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Road Tax Details</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-700 ml-1">Road Tax Number *</label>
                                <input v-model="addForm.road_tax_number" type="text" class="w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm font-medium" placeholder="e.g. RTX-123" required />
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-700 ml-1">Expiry Date *</label>
                                <input v-model="addForm.road_tax_expiry_date" type="date" :min="today" class="w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm font-medium" required />
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <label class="text-xs font-bold text-slate-700 ml-1">Road Tax Scan *</label>
                                <div class="relative group">
                                    <input type="file" @change="handleRoadTaxFileChange" accept=".pdf,.jpg,.jpeg,.png" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required />
                                    <div class="px-4 py-3 bg-white border border-dashed border-slate-300 rounded-xl group-hover:border-emerald-500 group-hover:bg-emerald-50 transition-all flex items-center justify-between">
                                        <span class="text-xs text-slate-500 truncate pr-4">{{ roadTaxFileName || 'Select PDF/Image' }}</span>
                                        <Upload class="h-4 w-4 text-slate-400 group-hover:text-emerald-600" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="h-1 w-8 bg-emerald-600 rounded-full"></span>
                            <h4 class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Remarks</h4>
                        </div>
                        <textarea v-model="addForm.remarks" rows="3"
                            class="w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm font-medium placeholder:text-slate-300 resize-none" 
                            placeholder="Additional notes..."></textarea>
                    </div>
                </div>
            </form>

            <div class="p-8 bg-slate-50 border-t border-slate-100 flex items-center gap-4 shrink-0">
                <button type="button" @click="showAddModal = false" 
                    class="flex-1 px-6 py-4 border border-slate-200 rounded-2xl text-sm font-bold text-slate-500 hover:bg-white transition-all">
                    Cancel
                </button>
                <button type="submit" @click="submitAdd" :disabled="addForm.processing" 
                    class="flex-[2] px-6 py-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-sm font-bold shadow-lg shadow-emerald-100 transition-all disabled:opacity-50 flex items-center justify-center gap-2">
                    <Loader2 v-if="addForm.processing" class="h-4 w-4 animate-spin" />
                    {{ addForm.processing ? 'Registering...' : 'Register Vehicle' }}
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Vehicle Modal -->
    <div v-if="showEditCarModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity" @click="showEditCarModal = false"></div>

        <div class="relative w-full max-w-2xl bg-white rounded-[2.5rem] shadow-2xl overflow-hidden transform transition-all animate-in fade-in zoom-in duration-300">
            
            <div class="bg-slate-50 border-b border-slate-100 p-8 flex items-center justify-between relative overflow-hidden">
                <div class="absolute -right-10 -top-10 w-32 h-32 bg-emerald-500/5 rounded-full blur-3xl"></div>
                
                <div class="relative z-10 flex items-center gap-5">
                    <div class="h-14 w-14 bg-emerald-600 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-200">
                        <Settings2 class="w-7 h-7" />
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight">Edit Vehicle</h3>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="px-2 py-0.5 bg-slate-900 text-white text-[10px] font-mono font-bold uppercase rounded tracking-wider">
                                {{ editingCar?.license_plate }}
                            </span>
                            <span class="text-slate-400 text-sm font-bold uppercase tracking-tighter">
                                {{ editingCar?.make }} {{ editingCar?.model }}
                            </span>
                        </div>
                    </div>
                </div>
                <button @click="showEditCarModal = false" class="p-2 bg-white border border-slate-100 rounded-full text-slate-400 hover:text-rose-500 transition-all shadow-sm z-10">
                    <X class="h-6 w-6" />
                </button>
            </div>

            <form @submit.prevent="updateCar" class="p-8">
                <div class="space-y-8">
                    
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="h-6 w-1 bg-emerald-500 rounded-full"></div>
                            <h4 class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Identification Details</h4>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">License Plate</label>
                                <input v-model="editingCar.license_plate" type="text" 
                                    class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all font-mono font-black text-slate-900 uppercase shadow-inner" required />
                                <p v-if="editingCar.errors?.license_plate" class="text-[10px] text-rose-500 font-bold uppercase mt-1 ml-1">{{ editingCar.errors.license_plate }}</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">VIN / Chassis Number</label>
                                <input v-model="editingCar.vin" type="text" 
                                    class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all font-mono text-sm text-slate-600 uppercase shadow-inner" placeholder="Optional" />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="h-6 w-1 bg-emerald-500 rounded-full"></div>
                            <h4 class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Specifications</h4>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                            <div class="col-span-2 md:col-span-1 space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Make</label>
                                <input v-model="editingCar.make" type="text" class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 text-sm font-bold text-slate-700 transition-all shadow-inner" required />
                            </div>
                            <div class="col-span-2 md:col-span-1 space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Model</label>
                                <input v-model="editingCar.model" type="text" class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 text-sm font-bold text-slate-700 transition-all shadow-inner" required />
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Year</label>
                                <input v-model="editingCar.year" type="number" class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 text-sm font-bold text-slate-700 transition-all shadow-inner" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Color</label>
                                <input v-model="editingCar.color" type="text" class="w-full px-4 py-3.5 bg-slate-50 border-transparent rounded-2xl focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 text-sm font-bold text-slate-700 transition-all shadow-inner" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Operational Status</label>
                            <div class="flex gap-3">
                                <select v-model="editingCar.status" 
                                    :class="[
                                        'w-full px-4 py-4 border-transparent rounded-2xl text-sm font-black uppercase tracking-wider transition-all shadow-inner appearance-none cursor-pointer',
                                        editingCar.status === 'available' ? 'bg-emerald-100 text-emerald-700 ring-2 ring-emerald-500/20' : 
                                        editingCar.status === 'leased' ? 'bg-indigo-100 text-indigo-700 ring-2 ring-indigo-500/20' : 
                                        'bg-amber-100 text-amber-700 ring-2 ring-amber-500/20'
                                    ]">
                                    <option value="available">🟢 Available for Lease</option>
                                    <option value="leased">🔵 Currently Leased</option>
                                    <option value="maintenance">🟠 Under Maintenance</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2 pt-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Internal Remarks & Notes</label>
                        <textarea v-model="editingCar.remarks" rows="3"
                            class="w-full px-5 py-4 bg-slate-50 border-transparent rounded-[1.5rem] focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all text-sm font-medium placeholder:text-slate-300 resize-none shadow-inner" 
                            placeholder="Condition notes, maintenance history..."></textarea>
                    </div>
                </div>

                <div class="mt-10 flex items-center gap-4">
                    <button type="button" @click="showEditCarModal = false" 
                        class="flex-1 px-6 py-4 border border-slate-200 rounded-2xl text-xs font-black uppercase tracking-widest text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition-all">
                        Discard
                    </button>
                    <button type="submit" :disabled="editCarProcessing" 
                        class="flex-[2] px-6 py-4 bg-slate-900 hover:bg-emerald-600 text-white rounded-2xl text-xs font-black uppercase tracking-[0.15em] shadow-xl shadow-slate-200 transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-3">
                        <Loader2 v-if="editCarProcessing" class="h-5 w-5 animate-spin" />
                        <span>{{ editCarProcessing ? 'Applying Changes...' : 'Update Vehicle' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Document Upload Modal -->
    <div v-if="showDocModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity" @click="showDocModal = false"></div>

        <div class="relative w-full max-w-md bg-white rounded-[2.5rem] shadow-2xl overflow-hidden transform transition-all animate-in fade-in zoom-in duration-300">
            
            <div class="bg-slate-50 border-b border-slate-100 p-7 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center shadow-sm">
                        <UploadCloud class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-900 leading-tight">Insurance/Road Tax Renewal</h3>
                        <div class="flex items-center gap-1.5 mt-1">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">Attaching to</span>
                            <span class="px-1.5 py-0.5 bg-slate-200 text-slate-700 rounded text-[9px] font-mono font-black uppercase">
                                {{ selectedCarForDoc?.license_plate }}
                            </span>
                        </div>
                    </div>
                </div>
                <button @click="showDocModal = false" class="p-2 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-full transition-all">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <form @submit.prevent="submitDoc" enctype="multipart/form-data" class="p-7 space-y-5">
                
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-[0.15em] text-slate-400 ml-1">Policy Category</label>
                    <div class="relative group">
                        <select v-model="docForm.type" 
                            class="w-full pl-4 pr-10 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all text-sm font-bold text-slate-700 appearance-none cursor-pointer">
                            <option value="insurance">Insurance Policy</option>
                            <option value="road_tax">Road Tax</option>
                        </select>
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400 group-hover:text-indigo-500 transition-colors">
                            <ChevronDown class="w-4 h-4" />
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-[0.15em] text-slate-400 ml-1">File Attachment</label>
                    <div class="relative group">
                        <input type="file" @change="handleDocFileChange" accept=".pdf,.jpg,.jpeg,.png" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" required />
                        
                        <div :class="[
                            'border-2 border-dashed rounded-[1.5rem] p-6 flex flex-col items-center justify-center transition-all duration-300 z-10',
                            docForm.file_name 
                                ? 'border-indigo-500 bg-indigo-50/30 shadow-inner' 
                                : 'border-slate-200 bg-slate-50 group-hover:border-indigo-400 group-hover:bg-indigo-50/50'
                        ]">
                            <div class="mb-2">
                                <CheckCircle2 v-if="docForm.file_name" class="h-10 w-10 text-indigo-600 animate-in zoom-in" />
                                <FileUp v-else class="h-10 w-10 text-slate-300 group-hover:text-indigo-500 transition-colors" />
                            </div>

                            <div class="text-center">
                                <template v-if="docForm.file_name">
                                    <p class="text-sm font-bold text-indigo-900 leading-none">Ready to Upload</p>
                                    <p class="text-[11px] text-indigo-600 mt-1 font-mono font-bold truncate max-w-[220px] bg-white px-2 py-0.5 rounded border border-indigo-100">
                                        {{ docForm.file_name }}
                                    </p>
                                </template>
                                <template v-else>
                                    <p class="text-sm font-bold text-slate-600">Select Document File</p>
                                    <p class="text-[10px] text-slate-400 mt-1 uppercase font-bold tracking-tighter">PDF, JPG or PNG (5MB)</p>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Expiry Date</label>
                        <div class="relative">
                            <Calendar class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input type="date" v-model="docForm.expiry_date" 
                                class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 text-sm font-bold text-slate-700 transition-all" required />
                        </div>
                    </div>

                    <!-- <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Status</label>
                        <div class="relative">
                            <select v-model="docForm.status" 
                                :class="[
                                    'w-full pl-3 pr-10 py-3 border rounded-xl text-sm font-bold appearance-none transition-all',
                                    docForm.status === 'valid' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 
                                    docForm.status === 'expiring' ? 'bg-amber-50 border-amber-200 text-amber-700' : 
                                    'bg-rose-50 border-rose-200 text-rose-700'
                                ]">
                                <option value="valid">Valid</option>
                                <option value="expiring">Expiring</option>
                                <option value="expired">Expired</option>
                            </select>
                            <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 opacity-50 pointer-events-none" />
                        </div>
                    </div> -->
                </div>

                <div class="flex items-center gap-3 pt-4">
                    <button type="button" @click="showDocModal = false" 
                        class="flex-1 px-4 py-4 border border-slate-200 rounded-2xl text-[11px] font-black text-slate-400 hover:bg-slate-50 transition-all uppercase tracking-widest">
                        Cancel
                    </button>
                    <button type="submit" :disabled="docForm.processing" 
                        class="flex-[2] px-4 py-4 bg-slate-900 hover:bg-indigo-600 text-white rounded-2xl text-[11px] font-black shadow-xl shadow-slate-200 transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2 uppercase tracking-[0.15em]">
                        <Loader2 v-if="docForm.processing" class="h-4 w-4 animate-spin" />
                        {{ docForm.processing ? 'Uploading...' : 'Confirm Renewal' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Payment Modal -->
    <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showPaymentModal = false"></div>

        <div class="relative w-full max-w-lg bg-white rounded-[2rem] shadow-2xl overflow-hidden transform transition-all animate-in fade-in zoom-in duration-200">
            
            <div class="bg-slate-50 border-b border-slate-100 p-8 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center shadow-sm">
                        <CreditCard class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-slate-900 leading-tight">Record Payment</h3>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="px-2 py-0.5 bg-slate-200 text-slate-700 rounded text-[10px] font-mono font-bold tracking-wider uppercase">
                                {{ selectedCarForPayment?.license_plate }}
                            </span>
                            <span class="text-xs text-slate-400 font-medium">{{ selectedCarForPayment?.model }}</span>
                        </div>
                    </div>
                </div>
                <button @click="showPaymentModal = false" class="text-slate-400 hover:text-slate-600 transition-colors p-2 hover:bg-white rounded-full shadow-sm">
                    <X class="h-6 w-6" />
                </button>
            </div>

            <form @submit.prevent="submitPayment" class="p-8">
                <div class="space-y-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Amount (RM)</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-sm">RM</span>
                                <input type="number" step="0.01" v-model="paymentForm.amount" 
                                    class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all font-bold text-slate-900" 
                                    placeholder="0.00" required />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Payment Date</label>
                            <div class="relative">
                                <Calendar class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                                <input type="date" v-model="paymentForm.paid_date" 
                                    class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all font-medium text-slate-700" 
                                    required />
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-amber-50 rounded-2xl border border-amber-100 flex items-center justify-between shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="bg-white p-2 rounded-xl shadow-sm">
                                <Clock class="h-5 w-5 text-amber-600" />
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-amber-700 uppercase tracking-tight">Billing Period End</p>
                                <p class="text-sm font-black text-amber-900 leading-none mt-0.5">{{ paymentForm.due_date || 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="text-[10px] font-bold bg-amber-200/50 text-amber-700 px-2 py-1 rounded-lg">SYSTEM GENERATED</div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Proof of Payment</label>
                        <div class="relative group">
                            <input type="file" @change="handlePaymentProofChange" accept=".pdf,.jpg,.jpeg,.png" 
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" />
                            
                            <div :class="[
                                'border-2 border-dashed rounded-2xl p-6 flex flex-col items-center justify-center transition-all duration-300 z-10',
                                paymentForm.receipt_name 
                                    ? 'border-emerald-500 bg-emerald-50 shadow-inner' 
                                    : 'border-slate-200 bg-slate-50 group-hover:border-emerald-400 group-hover:bg-emerald-50/50'
                            ]">
                                
                                <div class="mb-2">
                                    <CheckCircle2 v-if="paymentForm.receipt_name" class="h-10 w-10 text-emerald-600 animate-in zoom-in" />
                                    <UploadCloud v-else class="h-10 w-10 text-slate-300 group-hover:text-emerald-500 transition-colors" />
                                </div>

                                <div class="text-center">
                                    <template v-if="paymentForm.receipt_name">
                                        <p class="text-sm font-bold text-emerald-900 tracking-tight">Receipt Attached</p>
                                        <p class="text-[11px] text-emerald-600 mt-1 font-mono font-bold bg-white px-2 py-0.5 rounded border border-emerald-100 truncate max-w-[200px]">
                                            {{ paymentForm.receipt_name }}
                                        </p>
                                        <p class="text-[9px] text-emerald-400 mt-2 uppercase font-black">Click to replace file</p>
                                    </template>
                                    <template v-else>
                                        <p class="text-sm font-bold text-slate-600">Click or drag receipt here</p>
                                        <p class="text-[10px] text-slate-400 mt-1 uppercase font-bold tracking-tighter">PDF, JPG, or PNG (Max 5MB)</p>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-10 flex flex-wrap gap-4">
                    <button type="button" @click="showPaymentModal = false" 
                        class="flex-1 px-6 py-4 border border-slate-200 rounded-xl text-xs font-black uppercase tracking-widest text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition-all">
                        Cancel
                    </button>
                    <button type="submit" :disabled="paymentForm.processing" 
                        class="flex-[2] px-6 py-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-emerald-200/50 transition-all disabled:opacity-50 disabled:scale-95 flex items-center justify-center gap-3">
                        <Loader2 v-if="paymentForm.processing" class="h-5 w-5 animate-spin" />
                        {{ paymentForm.processing ? 'Recording Transaction...' : 'Complete Payment' }}
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