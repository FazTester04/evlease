<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const page = usePage<any>();
const props = page.props as any;

// ---------- Helpers ----------
const formatDate = (dateString: string | null) => {
    if (!dateString || dateString === '—') return '—';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

// ---------- Search ----------
const search = ref(props.filters?.search || '');
let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/fleet/vehicles', { search: value }, { preserveState: true, preserveScroll: true });
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
    status: 'available',
});
const submitAdd = () => {
    addForm.post('/admin/cars', {
        preserveScroll: true,
        onSuccess: () => {
            showAddModal.value = false;
            addForm.reset();
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
watch(() => paymentForm.paid_date, (newPaidDate) => {
    if (newPaidDate) {
        const date = new Date(newPaidDate);
        const dueDate = new Date(date.getFullYear(), date.getMonth(), 29);
        paymentForm.due_date = dueDate.toISOString().split('T')[0];
    }
});
const handlePaymentProofChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    paymentForm.proof = target.files?.[0] || null;
};
const openPaymentModal = (car: any) => {
    selectedCarForPayment.value = car;
    paymentForm.lease_id = car.active_lease_id;
    const today = new Date();
    paymentForm.paid_date = today.toISOString().split('T')[0];
    const dueDate = new Date(today.getFullYear(), today.getMonth(), 29);
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

// ---------- Edit Lease ----------
const showEditLeaseModal = ref(false);
const editingLease = ref<any>(null);
const leaseProcessing = ref(false);
const editLeaseForCar = (car: any) => {
    if (!props.leases || !car.active_lease_id) return;
    const lease = props.leases.find((l: any) => l.id === car.active_lease_id);
    if (lease) {
        editingLease.value = { ...lease, errors: {} };
        showEditLeaseModal.value = true;
    }
};
const updateLease = async () => {
    leaseProcessing.value = true;
    router.put(`/admin/leases/${editingLease.value.id}`, editingLease.value, {
        preserveScroll: true,
        onSuccess: () => {
            showEditLeaseModal.value = false;
            editingLease.value = null;
        },
        onError: (errors) => {
            editingLease.value.errors = errors;
        },
        onFinish: () => {
            leaseProcessing.value = false;
        },
    });
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
                <button @click="showAddModal = true" class="inline-flex items-center bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg font-medium text-sm shadow-sm transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add Vehicle
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
                    <input v-model="search" type="text" placeholder="Search by plate or VIN..." class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-full text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition-shadow bg-white" />
                </div>
            </div>

            <!-- Vehicles Table (your existing table) -->
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
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Next Due</th>
                                <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="car in props.cars" :key="car.id" class="hover:bg-slate-50/50 transition-colors group">
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
                                        <div class="text-xs text-slate-700 font-medium mt-1">RM{{ car.current_driver.monthly_payment }} / mo</div>
                                        <button @click="editLeaseForCar(car)" class="text-indigo-600 hover:text-indigo-800 font-medium text-xs mt-2 transition-colors">Edit Lease &rarr;</button>
                                    </div>
                                    <span v-else class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-500">Unassigned</span>
                                </td>
                                <td class="px-6 py-4 align-top text-sm">
                                    <div class="mb-2">
                                        <span class="text-slate-500 text-xs block mb-0.5">Payment</span>
                                        <span class="font-medium text-slate-900">{{ formatDate(car.next_payment_due) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 text-xs block mb-0.5">Service</span>
                                        <span class="font-medium text-slate-900">{{ formatDate(car.next_service_date) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-top text-right">
                                    <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button v-if="car.active_lease_id" @click="openPaymentModal(car)" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-md transition-colors" title="Record Payment">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        </button>
                                        <button @click="openDocModal(car)" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-md transition-colors" title="Upload Document">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </button>
                                        <button @click="editCar(car)" class="p-1.5 text-slate-600 hover:bg-slate-100 rounded-md transition-colors" title="Edit Vehicle">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>
                                        <button @click="deleteCar(car.id)" class="p-1.5 text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Delete Vehicle">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!props.cars?.length">
                                <td colspan="7" class="px-6 py-8 text-center text-slate-500">No vehicles found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppSidebarLayout>

    <!-- Add Vehicle Modal -->
    <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showAddModal = false">
        <div class="fixed inset-0 bg-black/50 transition-opacity" @click="showAddModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-xl shadow-xl overflow-hidden">
                <form @submit.prevent="submitAdd">
                    <div class="px-6 py-4">
                        <h3 class="text-xl font-semibold text-slate-900 mb-2">Add New Vehicle</h3>
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="modal-label">License Plate *</label>
                                    <input v-model="addForm.license_plate" type="text" class="modal-input" required />
                                    <p v-if="addForm.errors.license_plate" class="text-sm text-red-600 mt-1">{{ addForm.errors.license_plate }}</p>
                                </div>
                                <div>
                                    <label class="modal-label">VIN</label>
                                    <input v-model="addForm.vin" type="text" class="modal-input" />
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="modal-label">Make *</label>
                                    <input v-model="addForm.make" type="text" class="modal-input" required />
                                </div>
                                <div>
                                    <label class="modal-label">Model *</label>
                                    <input v-model="addForm.model" type="text" class="modal-input" required />
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="modal-label">Year *</label>
                                    <input v-model="addForm.year" type="number" min="1900" :max="new Date().getFullYear() + 1" class="modal-input" required />
                                </div>
                                <div>
                                    <label class="modal-label">Color</label>
                                    <input v-model="addForm.color" type="text" class="modal-input" />
                                </div>
                            </div>
                            <div>
                                <label class="modal-label">Status *</label>
                                <select v-model="addForm.status" class="modal-input" required>
                                    <option value="available">Available</option>
                                    <option value="leased">Leased</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-3 flex justify-end gap-3 border-t border-slate-200">
                        <button type="button" @click="showAddModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 bg-white hover:bg-slate-50">Cancel</button>
                        <button type="submit" :disabled="addForm.processing" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm disabled:opacity-50">
                            {{ addForm.processing ? 'Saving...' : 'Save Vehicle' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Vehicle Modal -->
    <div v-if="showEditCarModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showEditCarModal = false">
        <div class="fixed inset-0 bg-black/50 transition-opacity" @click="showEditCarModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-xl shadow-xl overflow-hidden">
                <form @submit.prevent="updateCar">
                    <div class="px-6 py-4">
                        <h3 class="text-xl font-semibold text-slate-900 mb-2">Edit Vehicle</h3>
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="modal-label">License Plate *</label>
                                    <input v-model="editingCar.license_plate" type="text" class="modal-input" required />
                                    <p v-if="editingCar.errors?.license_plate" class="text-sm text-red-600 mt-1">{{ editingCar.errors.license_plate }}</p>
                                </div>
                                <div>
                                    <label class="modal-label">VIN</label>
                                    <input v-model="editingCar.vin" type="text" class="modal-input" />
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="modal-label">Make *</label>
                                    <input v-model="editingCar.make" type="text" class="modal-input" required />
                                </div>
                                <div>
                                    <label class="modal-label">Model *</label>
                                    <input v-model="editingCar.model" type="text" class="modal-input" required />
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="modal-label">Year *</label>
                                    <input v-model="editingCar.year" type="number" min="1900" :max="new Date().getFullYear() + 1" class="modal-input" required />
                                </div>
                                <div>
                                    <label class="modal-label">Color</label>
                                    <input v-model="editingCar.color" type="text" class="modal-input" />
                                </div>
                            </div>
                            <div>
                                <label class="modal-label">Status *</label>
                                <select v-model="editingCar.status" class="modal-input" required>
                                    <option value="available">Available</option>
                                    <option value="leased">Leased</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-3 flex justify-end gap-3 border-t border-slate-200">
                        <button type="button" @click="showEditCarModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 bg-white hover:bg-slate-50">Cancel</button>
                        <button type="submit" :disabled="editCarProcessing" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm disabled:opacity-50">
                            {{ editCarProcessing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Document Upload Modal -->
    <div v-if="showDocModal" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black/50 transition-opacity" @click="showDocModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white rounded-xl shadow-xl p-6">
                <h3 class="text-lg font-medium mb-4">Upload Document for {{ selectedCarForDoc?.license_plate }}</h3>
                <form @submit.prevent="submitDoc" enctype="multipart/form-data">
                    <div class="space-y-4">
                        <div>
                            <label class="modal-label">Document Type</label>
                            <select v-model="docForm.type" class="modal-input">
                                <option value="insurance">Insurance</option>
                                <option value="road_tax">Road Tax</option>
                            </select>
                        </div>
                        <div>
                            <label class="modal-label">File (PDF, JPG, PNG)</label>
                            <input type="file" @change="handleDocFileChange" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <label class="modal-label">Expiry Date</label>
                            <input type="date" v-model="docForm.expiry_date" class="modal-input" required />
                        </div>
                        <div>
                            <label class="modal-label">Status</label>
                            <select v-model="docForm.status" class="modal-input">
                                <option value="valid">Valid</option>
                                <option value="expiring">Expiring</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showDocModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 bg-white hover:bg-slate-50">Cancel</button>
                        <button type="submit" :disabled="docForm.processing" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm disabled:opacity-50">
                            {{ docForm.processing ? 'Uploading...' : 'Upload' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div v-if="showPaymentModal" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black/50 transition-opacity" @click="showPaymentModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white rounded-xl shadow-xl p-6">
                <h3 class="text-lg font-medium mb-4">Record Payment for {{ selectedCarForPayment?.license_plate }}</h3>
                <form @submit.prevent="submitPayment" enctype="multipart/form-data">
                    <div class="space-y-4">
                        <div>
                            <label class="modal-label">Amount (RM)</label>
                            <input type="number" step="0.01" v-model="paymentForm.amount" class="modal-input" required />
                        </div>
                        <div>
                            <label class="modal-label">Payment Date</label>
                            <input type="date" v-model="paymentForm.paid_date" class="modal-input" required />
                        </div>
                        <div>
                            <label class="modal-label">Due Date (29th)</label>
                            <input type="date" v-model="paymentForm.due_date" class="modal-input bg-slate-100" readonly required />
                        </div>
                        <div>
                            <label class="modal-label">Proof of Payment</label>
                            <input type="file" @change="handlePaymentProofChange" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full" />
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showPaymentModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 bg-white hover:bg-slate-50">Cancel</button>
                        <button type="submit" :disabled="paymentForm.processing" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm disabled:opacity-50">
                            {{ paymentForm.processing ? 'Saving...' : 'Save Payment' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Lease Modal -->
    <div v-if="showEditLeaseModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showEditLeaseModal = false">
        <div class="fixed inset-0 bg-black/50 transition-opacity" @click="showEditLeaseModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-xl shadow-xl overflow-hidden">
                <form @submit.prevent="updateLease">
                    <div class="px-6 py-4">
                        <h3 class="text-xl font-semibold text-slate-900 mb-2">Edit Lease</h3>
                        <p class="text-sm text-slate-500 mb-4">{{ editingLease?.car?.license_plate }} – {{ editingLease?.driver?.name }}</p>
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="modal-label">Start Date *</label>
                                    <input type="date" v-model="editingLease.start_date" class="modal-input" required />
                                    <p v-if="editingLease.errors?.start_date" class="text-sm text-red-600 mt-1">{{ editingLease.errors.start_date }}</p>
                                </div>
                                <div>
                                    <label class="modal-label">End Date</label>
                                    <input type="date" v-model="editingLease.end_date" class="modal-input" />
                                    <p v-if="editingLease.errors?.end_date" class="text-sm text-red-600 mt-1">{{ editingLease.errors.end_date }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="modal-label">Monthly Payment (RM) *</label>
                                    <input type="number" step="0.01" min="0" v-model="editingLease.monthly_payment" class="modal-input" required />
                                    <p v-if="editingLease.errors?.monthly_payment" class="text-sm text-red-600 mt-1">{{ editingLease.errors.monthly_payment }}</p>
                                </div>
                                <div>
                                    <label class="modal-label">Down Payment (RM)</label>
                                    <input type="number" step="0.01" min="0" v-model="editingLease.down_payment" class="modal-input" placeholder="0.00" />
                                    <p v-if="editingLease.errors?.down_payment" class="text-sm text-red-600 mt-1">{{ editingLease.errors.down_payment }}</p>
                                </div>
                            </div>
                            <div>
                                <label class="modal-label">Status</label>
                                <select v-model="editingLease.status" class="modal-input">
                                    <option value="active">Active</option>
                                    <option value="pending">Pending</option>
                                    <option value="ended">Ended</option>
                                </select>
                                <p v-if="editingLease.errors?.status" class="text-sm text-red-600 mt-1">{{ editingLease.errors.status }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-3 flex justify-end gap-3 border-t border-slate-200">
                        <button type="button" @click="showEditLeaseModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 bg-white hover:bg-slate-50">Cancel</button>
                        <button type="submit" :disabled="leaseProcessing" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm disabled:opacity-50">
                            {{ leaseProcessing ? 'Saving...' : 'Save Changes' }}
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