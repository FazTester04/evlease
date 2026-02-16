<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

// Removed shadcn‑vue dialog imports – using custom modal instead.

const page = usePage<any>();
const props = page.props as any;

// ---------- Search ----------
const search = ref(props.filters?.search || '');
const activeSection = ref('vehicles'); // vehicles | leases | drivers | maintenance | documents

let searchTimeout: number;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            '/fleet-management',
            { search: value },
            { preserveState: true, preserveScroll: true }
        );
    }, 300);
});

const editLeaseForCar = (car: any) => {
    const lease = props.leases.find((l: any) => l.id === car.active_lease_id);
    if (lease) {
        editLease(lease);
    }
};
// ---------- Create Lease Modal ----------
const showCreateLeaseModal = ref(false);

const leaseForm = useForm({
    car_id: '',
    driver_id: '',
    start_date: '',
    end_date: '',
    monthly_payment: '',
    status: 'active',
});

const submitLease = () => {
    leaseForm.post('/admin/leases', {
        preserveScroll: true,
        onSuccess: () => {
            showCreateLeaseModal.value = false;
            leaseForm.reset();
        },
    });
};

// ---------- Filter state ----------
const activeFilter = ref<string | null>(null);

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

// ---------- Car actions ----------
const showEditCarModal = ref(false);
const editingCar = ref<any>(null);

const editCar = (car: any) => {
    editingCar.value = { ...car };
    showEditCarModal.value = true;
};

const updateCar = () => {
    router.put(`/admin/cars/${editingCar.value.id}`, editingCar.value, {
        preserveScroll: true,
        onSuccess: () => {
            showEditCarModal.value = false;
            editingCar.value = null;
        },
    });
};

const deleteCar = (id: number) => {
    if (confirm('Are you sure you want to delete this car?')) {
        router.delete(`/admin/cars/${id}`, {
            preserveScroll: true,
        });
    }
};

// ---------- Lease actions ----------
const showEditLeaseModal = ref(false);
const editingLease = ref<any>(null);

const editLease = (lease: any) => {
    editingLease.value = { ...lease };
    showEditLeaseModal.value = true;
};

const updateLease = () => {
    router.put(`/admin/leases/${editingLease.value.id}`, editingLease.value, {
        preserveScroll: true,
        onSuccess: () => {
            showEditLeaseModal.value = false;
            editingLease.value = null;
        },
    });
};

const deleteLease = (id: number) => {
    if (confirm('Are you sure you want to delete this lease?')) {
        router.delete(`/admin/leases/${id}`, {
            preserveScroll: true,
        });
    }
};

// ---------- Document actions ----------
const showEditDocumentModal = ref(false);
const editingDocument = ref<any>(null);

const editDocument = (doc: any) => {
    editingDocument.value = { ...doc };
    showEditDocumentModal.value = true;
};

const updateDocument = () => {
    router.put(`/admin/documents/${editingDocument.value.id}`, editingDocument.value, {
        preserveScroll: true,
        onSuccess: () => {
            showEditDocumentModal.value = false;
            editingDocument.value = null;
        },
    });
};

const deleteDocument = (id: number) => {
    if (confirm('Are you sure you want to delete this document?')) {
        router.delete(`/admin/documents/${id}`, {
            preserveScroll: true,
        });
    }
};

// ---------- Badge helpers ----------
const statusBadgeClass = (status: string) => {
    switch (status?.toLowerCase()) {
        case 'leased': return 'bg-purple-100 text-purple-800';
        case 'maintenance': return 'bg-red-100 text-red-800';
        case 'available': return 'bg-green-100 text-green-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const documentStatusBadgeClass = (status: string) => {
    switch (status?.toLowerCase()) {
        case 'valid': return 'bg-green-100 text-green-800';
        case 'expiring': return 'bg-yellow-100 text-yellow-800';
        case 'expired': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const paymentStatusBadgeClass = (status: string) => {
    switch (status?.toLowerCase()) {
        case 'paid on time': return 'bg-green-100 text-green-800';
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'overdue': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const serviceStatusBadgeClass = (status: string) => {
    switch (status?.toLowerCase()) {
        case 'up to date': return 'bg-green-100 text-green-800';
        case 'due soon': return 'bg-yellow-100 text-yellow-800';
        case 'overdue': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const leasePaymentBadgeClass = (status: string) => {
    switch (status) {
        case 'Paid on Time': return 'bg-green-100 text-green-800';
        case 'Pending': return 'bg-yellow-100 text-yellow-800';
        case 'Overdue': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};
const showDocModal = ref(false);
const selectedCarForDoc = ref<any>(null);
const openDocModal = (car: any) => {
    selectedCarForDoc.value = car;
    docForm.car_id = car.id;
    showDocModal.value = true;
};

const submitDoc = () => {
    docForm.post('/admin/documents/upload', {
        preserveScroll: true,
        onSuccess: () => {
            showDocModal.value = false;
            docForm.reset();
        },
    });
};

// ---------- Payment Modal ----------
const showPaymentModal = ref(false);
const selectedCarForPayment = ref<any>(null);
const paymentForm = useForm({
    lease_id: '',
    amount: '',
    paid_date: new Date().toISOString().split('T')[0],
    due_date: '',
});

const openPaymentModal = (car: any) => {
    selectedCarForPayment.value = car;
    paymentForm.lease_id = car.active_lease_id;
    // Set due_date to the next payment due if available
    if (car.next_payment_due) {
        paymentForm.due_date = car.next_payment_due;
    }
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
const docForm = useForm<{
    car_id: string;
    type: string;
    document: File | null;
    expiry_date: string;
    status: string;
}>({
    car_id: '',
    type: 'insurance',
    document: null,
    expiry_date: '',
    status: 'valid',
});
const handleDocFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        docForm.document = target.files[0];
    } else {
        docForm.document = null;
    }
};
</script>

<template>
    <AppSidebarLayout>
        <div class="px-8 py-6 space-y-6 bg-gray-50 min-h-screen">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Vehicle Fleet</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Track lease and payment status for each vehicle - 2024 LEAPMOTOR C10 BEV Fleet
                    </p>
                </div>

                <!-- Add Vehicle Button (custom trigger) -->
                <button 
                    @click="showAddModal = true"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md shadow-sm"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Vehicle
                </button>

                <!-- Custom Modal -->
                <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showAddModal = false">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showAddModal = false"></div>

                    <!-- Modal panel -->
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div class="relative w-full max-w-2xl bg-white rounded-lg shadow-xl overflow-hidden">
                            <form @submit.prevent="submitAdd">
                                <div class="px-6 py-4">
                                    <h3 class="text-xl font-semibold text-gray-900">Add New Vehicle</h3>
                                    <p class="text-sm text-gray-500">Fill in the details below to add a new vehicle to your fleet.</p>

                                    <!-- Form fields -->
                                    <div class="mt-4 space-y-4">
                                        <!-- License Plate and VIN row -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">License Plate <span class="text-red-500">*</span></label>
                                                <input
                                                    v-model="addForm.license_plate"
                                                    type="text"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                                    required
                                                />
                                                <p v-if="addForm.errors.license_plate" class="mt-1 text-sm text-red-600">{{ addForm.errors.license_plate }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">VIN</label>
                                                <input
                                                    v-model="addForm.vin"
                                                    type="text"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                                />
                                                <p v-if="addForm.errors.vin" class="mt-1 text-sm text-red-600">{{ addForm.errors.vin }}</p>
                                            </div>
                                        </div>

                                        <!-- Make and Model row -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Make <span class="text-red-500">*</span></label>
                                                <input
                                                    v-model="addForm.make"
                                                    type="text"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                                    required
                                                />
                                                <p v-if="addForm.errors.make" class="mt-1 text-sm text-red-600">{{ addForm.errors.make }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Model <span class="text-red-500">*</span></label>
                                                <input
                                                    v-model="addForm.model"
                                                    type="text"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                                    required
                                                />
                                                <p v-if="addForm.errors.model" class="mt-1 text-sm text-red-600">{{ addForm.errors.model }}</p>
                                            </div>
                                        </div>

                                        <!-- Year and Color row -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Year <span class="text-red-500">*</span></label>
                                                <input
                                                    v-model="addForm.year"
                                                    type="number"
                                                    :min="1900"
                                                    :max="new Date().getFullYear() + 1"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                                    required
                                                />
                                                <p v-if="addForm.errors.year" class="mt-1 text-sm text-red-600">{{ addForm.errors.year }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Color</label>
                                                <input
                                                    v-model="addForm.color"
                                                    type="text"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                                />
                                                <p v-if="addForm.errors.color" class="mt-1 text-sm text-red-600">{{ addForm.errors.color }}</p>
                                            </div>
                                        </div>

                                        <!-- Status -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                                            <select
                                                v-model="addForm.status"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                                required
                                            >
                                                <option value="available">Available</option>
                                                <option value="leased">Leased</option>
                                                <option value="maintenance">Maintenance</option>
                                            </select>
                                            <p v-if="addForm.errors.status" class="mt-1 text-sm text-red-600">{{ addForm.errors.status }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer buttons -->
                                <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3">
                                    <button
                                        type="button"
                                        @click="showAddModal = false"
                                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="addForm.processing"
                                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                                    >
                                        <svg v-if="addForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ addForm.processing ? 'Saving...' : 'Save Vehicle' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search by plate number or VIN..."
                    class="pl-10 pr-4 py-2 w-full md:w-96 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                />
            </div>

            <!-- Filter Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-medium text-gray-700 mr-2">Filters:</span>
                <button
                    @click="activeFilter = 'status'"
                    :class="[
                        'px-3 py-1.5 text-xs font-medium rounded-full border transition',
                        activeFilter === 'status'
                            ? 'bg-blue-100 text-blue-800 border-blue-300'
                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'
                    ]"
                >
                    Status
                </button>
                <button
                    @click="activeFilter = 'road_tax'"
                    :class="[
                        'px-3 py-1.5 text-xs font-medium rounded-full border transition',
                        activeFilter === 'road_tax'
                            ? 'bg-blue-100 text-blue-800 border-blue-300'
                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'
                    ]"
                >
                    ROAD TAX
                </button>
                <button
                    @click="activeFilter = 'insurance'"
                    :class="[
                        'px-3 py-1.5 text-xs font-medium rounded-full border transition',
                        activeFilter === 'insurance'
                            ? 'bg-blue-100 text-blue-800 border-blue-300'
                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'
                    ]"
                >
                    INSURANCE
                </button>
                <button
                    @click="activeFilter = 'current_driver'"
                    :class="[
                        'px-3 py-1.5 text-xs font-medium rounded-full border transition',
                        activeFilter === 'current_driver'
                            ? 'bg-blue-100 text-blue-800 border-blue-300'
                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'
                    ]"
                >
                    CURRENT DRIVER
                </button>
                <button
                    @click="activeFilter = 'payment_status'"
                    :class="[
                        'px-3 py-1.5 text-xs font-medium rounded-full border transition',
                        activeFilter === 'payment_status'
                            ? 'bg-blue-100 text-blue-800 border-blue-300'
                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'
                    ]"
                >
                    PAYMENT STATUS
                </button>
                <button
                    @click="activeFilter = 'service_status'"
                    :class="[
                        'px-3 py-1.5 text-xs font-medium rounded-full border transition',
                        activeFilter === 'service_status'
                            ? 'bg-blue-100 text-blue-800 border-blue-300'
                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'
                    ]"
                >
                    SERVICE STATUS
                </button>
            </div>

            <!-- Secondary Tabs -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button
                        @click="activeSection = 'vehicles'"
                        :class="[
                            activeSection === 'vehicles'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        Vehicles
                    </button>
                    <button
                        @click="activeSection = 'leases'"
                        :class="[
                            activeSection === 'leases'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        Leases
                    </button>
                    <button
                        @click="activeSection = 'drivers'"
                        :class="[
                            activeSection === 'drivers'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        Drivers
                    </button>
                    <button
                        @click="activeSection = 'maintenance'"
                        :class="[
                            activeSection === 'maintenance'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        Maintenance
                    </button>
                    <button
                        @click="activeSection = 'documents'"
                        :class="[
                            activeSection === 'documents'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        Documents
                    </button>
                </nav>
            </div>

 <!-- VEHICLES TAB -->
<div v-if="activeSection === 'vehicles'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div v-for="car in props.cars" :key="car.id" class="bg-white rounded-lg shadow p-5 space-y-3 border border-gray-200 hover:shadow-md transition">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <span class="font-mono text-lg font-bold text-gray-900">{{ car.license_plate }}</span>
            <div class="flex items-center gap-1">
                <span :class="['px-2.5 py-0.5 rounded-full text-xs font-medium', statusBadgeClass(car.status)]">
                    {{ car.status }}
                </span>
                <button @click="editCar(car)" class="text-blue-600 hover:text-blue-800 p-1" title="Edit">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </button>
                <button @click="deleteCar(car.id)" class="text-red-600 hover:text-red-800 p-1" title="Delete">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="text-xs text-gray-500">VIN: {{ car.vin }}</div>
        <div class="text-sm text-gray-700">{{ car.year }} {{ car.make }} {{ car.model }}</div>

        <!-- Road Tax & Insurance -->
        <div class="grid grid-cols-2 gap-2 text-xs">
            <div>
                <span class="font-medium text-gray-600">Road Tax</span>
                <div v-if="car.road_tax.status !== 'Not Uploaded'" class="mt-1">
                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', documentStatusBadgeClass(car.road_tax.status)]">
                        {{ car.road_tax.status }}
                    </span>
                    <div v-if="car.road_tax.expiry_date" class="text-gray-500 mt-0.5">Exp: {{ car.road_tax.expiry_date }}</div>
                </div>
                <span v-else class="text-gray-400">Not uploaded</span>
            </div>
            <div>
                <span class="font-medium text-gray-600">Insurance</span>
                <div v-if="car.insurance.status !== 'Not Uploaded'" class="mt-1">
                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', documentStatusBadgeClass(car.insurance.status)]">
                        {{ car.insurance.status }}
                    </span>
                    <div v-if="car.insurance.expiry_date" class="text-gray-500 mt-0.5">Exp: {{ car.insurance.expiry_date }}</div>
                </div>
                <span v-else class="text-gray-400">Not uploaded</span>
            </div>
        </div>

        <!-- Driver & Payment -->
        <div class="border-t pt-2 mt-1">
            <div v-if="car.current_driver" class="flex items-center justify-between text-sm">
                <div class="flex items-center gap-2">
                    <div>
                        <span class="font-medium text-gray-700">{{ car.current_driver.name }}</span>
                        <span class="text-xs text-gray-500 ml-1">ID: {{ car.current_driver.driver_license }}</span>
                    </div>
                    <button 
                        v-if="car.active_lease_id"
                        @click="editLeaseForCar(car)" 
                        class="text-blue-600 hover:text-blue-800 p-1" 
                        title="Edit Lease"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </button>
                </div>
                <div class="text-xs font-medium">${{ car.current_driver.monthly_payment }}/mo</div>
            </div>
            <div v-else class="text-xs text-gray-400 italic">No driver assigned</div>
        </div>

        <!-- Next due dates -->
        <div class="flex justify-between text-xs text-gray-500">
            <span v-if="car.next_payment_due">Next due: {{ car.next_payment_due }}</span>
            <span v-else>—</span>
            <span v-if="car.next_service_date">Next service: {{ car.next_service_date }}</span>
            <span v-else>—</span>
        </div>

        <!-- Action Buttons (Documents & Payment) -->
        <div class="flex justify-between gap-2 mt-2 border-t pt-2">
            <button @click="openDocModal(car)" class="text-xs text-blue-600 hover:underline">+ Documents</button>
            <button v-if="car.active_lease_id" @click="openPaymentModal(car)" class="text-xs text-green-600 hover:underline">+ Payment</button>
        </div>
    </div>

    <!-- Empty state -->
    <div v-if="props.cars.length === 0" class="col-span-full text-center py-10 text-gray-500">
        No vehicles found.
    </div>
</div>
            

            <!-- LEASES TAB -->
            <div v-else-if="activeSection === 'leases'" class="bg-white rounded-lg shadow overflow-hidden">
                <div class="flex justify-between items-center p-4 border-b">
    <h2 class="text-lg font-semibold">Active Leases</h2>
    <button 
        @click="showCreateLeaseModal = true"
        class="inline-flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white text-sm px-3 py-1.5 rounded-md"
    >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        New Lease
    </button>
</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vehicle</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Driver</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monthly Payment</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="lease in props.leases" :key="lease.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ lease.car.license_plate }}</div>
                                    <div class="text-xs text-gray-500">{{ lease.car.make }} {{ lease.car.model }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ lease.driver.name }}</div>
                                    <div class="text-xs text-gray-500">ID: {{ lease.driver.driver_license }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ lease.start_date }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ lease.end_date }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">RM{{ lease.monthly_payment }}/mo</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', leasePaymentBadgeClass(lease.payment_status)]">
                                        {{ lease.payment_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button @click="editLease(lease)" class="text-blue-600 hover:text-blue-900 mr-2">Edit</button>
                                    <button @click="deleteLease(lease.id)" class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="props.leases?.length === 0">
                                <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                                    No active leases found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- DRIVERS TAB placeholder -->
            <div v-else-if="activeSection === 'drivers'" class="text-center py-10 text-gray-500">
                Drivers section coming soon.
            </div>

            <!-- MAINTENANCE TAB placeholder -->
            <div v-else-if="activeSection === 'maintenance'" class="text-center py-10 text-gray-500">
                Maintenance section coming soon.
            </div>

            <!-- DOCUMENTS TAB -->
            <div v-else-if="activeSection === 'documents'" class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Document</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Related To</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expiry Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="doc in props.documents" :key="doc.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ doc.name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div v-if="doc.car" class="text-sm text-gray-900">Car: {{ doc.car.license_plate }}</div>
                                    <div v-else-if="doc.driver" class="text-sm text-gray-900">Driver: {{ doc.driver.name }}</div>
                                    <div v-else class="text-sm text-gray-400">—</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ doc.expiry_date || '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', documentStatusBadgeClass(doc.status)]">
                                        {{ doc.status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button @click="editDocument(doc)" class="text-blue-600 hover:text-blue-900 mr-2">Edit</button>
                                    <button @click="deleteDocument(doc.id)" class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="props.documents?.length === 0">
                                <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                    No documents found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppSidebarLayout>
    <div v-if="showCreateLeaseModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showCreateLeaseModal = false">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showCreateLeaseModal = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-lg bg-white rounded-lg shadow-xl overflow-hidden">
            <form @submit.prevent="submitLease">
                <div class="px-6 py-4">
                    <h3 class="text-xl font-semibold text-gray-900">Create New Lease</h3>
                    <p class="text-sm text-gray-500">Assign a vehicle to a driver.</p>

                    <div class="mt-4 space-y-4">
                        <!-- Vehicle -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Vehicle <span class="text-red-500">*</span></label>
                            <select v-model="leaseForm.car_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" required>
                                <option value="">Select vehicle</option>
                                <option v-for="car in props.cars" :key="car.id" :value="car.id">
                                    {{ car.license_plate }} – {{ car.year }} {{ car.make }} {{ car.model }}
                                </option>
                            </select>
                            <p v-if="leaseForm.errors.car_id" class="mt-1 text-sm text-red-600">{{ leaseForm.errors.car_id }}</p>
                        </div>

                        <!-- Driver -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Driver <span class="text-red-500">*</span></label>
                            <select v-model="leaseForm.driver_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" required>
                                <option value="">Select driver</option>
                                <option v-for="driver in props.drivers" :key="driver.id" :value="driver.id">
                                    {{ driver.name }} ({{ driver.driver_license }})
                                </option>
                            </select>
                            <p v-if="leaseForm.errors.driver_id" class="mt-1 text-sm text-red-600">{{ leaseForm.errors.driver_id }}</p>
                        </div>

                        <!-- Start Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Date <span class="text-red-500">*</span></label>
                            <input type="date" v-model="leaseForm.start_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" required>
                            <p v-if="leaseForm.errors.start_date" class="mt-1 text-sm text-red-600">{{ leaseForm.errors.start_date }}</p>
                        </div>

                        <!-- End Date (optional) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Date</label>
                            <input type="date" v-model="leaseForm.end_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <p v-if="leaseForm.errors.end_date" class="mt-1 text-sm text-red-600">{{ leaseForm.errors.end_date }}</p>
                        </div>

                        <!-- Monthly Payment -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Monthly Payment (RM) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" min="0" v-model="leaseForm.monthly_payment" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" required>
                            <p v-if="leaseForm.errors.monthly_payment" class="mt-1 text-sm text-red-600">{{ leaseForm.errors.monthly_payment }}</p>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select v-model="leaseForm.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="ended">Ended</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3">
                    <button type="button" @click="showCreateLeaseModal = false" class="px-4 py-2 border rounded-md text-sm">Cancel</button>
                    <button type="submit" :disabled="leaseForm.processing" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm">Create Lease</button>
                </div>
            </form>
        </div>
    </div>
</div>
    <!-- Document Upload Modal -->
<div v-if="showDocModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showDocModal = false">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showDocModal = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <h3 class="text-lg font-medium mb-4">Upload Document for {{ selectedCarForDoc?.license_plate }}</h3>
            <form @submit.prevent="submitDoc" enctype="multipart/form-data">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium">Document Type</label>
                        <select v-model="docForm.type" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="insurance">Insurance</option>
                            <option value="road_tax">Road Tax</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">File (PDF, JPG, PNG)</label>
                        <input type="file" @change="handleDocFileChange" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Expiry Date</label>
                        <input type="date" v-model="docForm.expiry_date" class="mt-1 block w-full rounded-md border-gray-300" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Status</label>
                        <select v-model="docForm.status" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="valid">Valid</option>
                            <option value="expiring">Expiring</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" @click="showDocModal = false" class="px-4 py-2 border rounded">Cancel</button>
                    <button type="submit" :disabled="docForm.processing" class="px-4 py-2 bg-blue-600 text-white rounded">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Record Payment Modal -->
<div v-if="showPaymentModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showPaymentModal = false">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showPaymentModal = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <h3 class="text-lg font-medium mb-4">Record Payment for {{ selectedCarForPayment?.license_plate }}</h3>
            <form @submit.prevent="submitPayment">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium">Amount (RM)</label>
                        <input type="number" step="0.01" v-model="paymentForm.amount" class="mt-1 block w-full rounded-md border-gray-300" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Payment Date</label>
                        <input type="date" v-model="paymentForm.paid_date" class="mt-1 block w-full rounded-md border-gray-300" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Due Date</label>
                        <input type="date" v-model="paymentForm.due_date" class="mt-1 block w-full rounded-md border-gray-300" required>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" @click="showPaymentModal = false" class="px-4 py-2 border rounded">Cancel</button>
                    <button type="submit" :disabled="paymentForm.processing" class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
    <!-- Edit Car Modal -->
    <div v-if="showEditCarModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Edit Car</h3>
            <form @submit.prevent="updateCar">
                <div class="space-y-3">
                    <input v-model="editingCar.license_plate" type="text" placeholder="License Plate" class="w-full border rounded px-3 py-2" required>
                    <input v-model="editingCar.vin" type="text" placeholder="VIN" class="w-full border rounded px-3 py-2">
                    <input v-model="editingCar.make" type="text" placeholder="Make" class="w-full border rounded px-3 py-2" required>
                    <input v-model="editingCar.model" type="text" placeholder="Model" class="w-full border rounded px-3 py-2" required>
                    <input v-model="editingCar.year" type="number" placeholder="Year" class="w-full border rounded px-3 py-2" required>
                    <input v-model="editingCar.color" type="text" placeholder="Color" class="w-full border rounded px-3 py-2">
                    <select v-model="editingCar.status" class="w-full border rounded px-3 py-2" required>
                        <option value="available">Available</option>
                        <option value="leased">Leased</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="showEditCarModal = false" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Lease Modal -->
    <div v-if="showEditLeaseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Edit Lease</h3>
            <form @submit.prevent="updateLease">
                <div class="space-y-3">
                    <input v-model="editingLease.start_date" type="date" class="w-full border rounded px-3 py-2" required>
                    <input v-model="editingLease.end_date" type="date" class="w-full border rounded px-3 py-2">
                    <input v-model="editingLease.monthly_payment" type="number" step="0.01" placeholder="Monthly Payment" class="w-full border rounded px-3 py-2" required>
                    <select v-model="editingLease.status" class="w-full border rounded px-3 py-2" required>
                        <option value="active">Active</option>
                        <option value="ended">Ended</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="showEditLeaseModal = false" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Document Modal -->
    <div v-if="showEditDocumentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Edit Document</h3>
            <form @submit.prevent="updateDocument">
                <div class="space-y-3">
                    <input v-model="editingDocument.name" type="text" placeholder="Document Name" class="w-full border rounded px-3 py-2" required>
                    <input v-model="editingDocument.expiry_date" type="date" class="w-full border rounded px-3 py-2">
                    <select v-model="editingDocument.status" class="w-full border rounded px-3 py-2" required>
                        <option value="valid">Valid</option>
                        <option value="expiring">Expiring</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="showEditDocumentModal = false" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
                </div>
            </form>
        </div>
    </div>
</template>