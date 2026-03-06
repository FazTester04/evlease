<script setup>
import DriverLayout from '@/layouts/DriverLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
    ChevronLeft, 
    FileUp, 
    ShieldCheck, 
    CreditCard, 
    FileText, 
    CheckCircle2, 
    AlertCircle,
    Loader2,
    PlusCircle,
    Calendar    // <-- imported for expiry date icon
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps({
    leases: Array,
    preselectedLeaseId: Number,
});

const form = useForm({
    lease_id: props.preselectedLeaseId || '',
    type: '',
    file: null,
    expiry_date: '',   // <-- new field
});

const fileName = ref('');
const isDragging = ref(false);

const docTypes = [
    { id: 'driving_license', label: 'Driving License', icon: CreditCard },
    { id: 'road_tax', label: 'Road Tax', icon: ShieldCheck },
    { id: 'insurance', label: 'Insurance', icon: FileText },
];

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.file = file;
        fileName.value = file.name;
    }
};

const submit = () => {
    form.post(route('driver.documents.store'), {
        onSuccess: () => {
            form.reset('file');
            fileName.value = '';
        },
    });
};
</script>

<template>
    <DriverLayout>
        <Head title="Upload Document" />

        <div class="max-w-2xl mx-auto px-4 py-10">
            <div class="mb-10">
                <Link :href="route('driver.documents.index')" 
                    class="inline-flex items-center text-sm font-bold text-slate-500 hover:text-emerald-600 transition-colors group">
                    <ChevronLeft class="size-4 mr-1 group-hover:-translate-x-1 transition-transform" />
                    Back to Documents
                </Link>
                <h1 class="text-4xl font-black text-slate-900 mt-2 tracking-tight">Upload Vault</h1>
                <p class="text-slate-500 font-medium">Keep your fleet compliance documents up to date.</p>
            </div>

            <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
                <form @submit.prevent="submit" class="p-8 md:p-12 space-y-10">
                    
                    <!-- Step 1: Select Type -->
                    <div class="space-y-4">
                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Step 1: Select Type</label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <button v-for="type in docTypes" :key="type.id" 
                                type="button"
                                @click="form.type = type.id"
                                :class="[
                                    'flex flex-col items-center justify-center p-6 rounded-3xl border-2 transition-all space-y-3',
                                    form.type === type.id 
                                        ? 'border-emerald-500 bg-emerald-50 text-emerald-600 shadow-inner' 
                                        : 'border-slate-100 bg-slate-50 text-slate-400 hover:border-slate-200'
                                ]"
                            >
                                <component :is="type.icon" :class="['size-8', form.type === type.id ? 'animate-bounce' : '']" />
                                <span class="text-xs font-black uppercase tracking-tight">{{ type.label }}</span>
                            </button>
                        </div>
                        <p v-if="form.errors.type" class="text-xs text-rose-500 font-bold flex items-center gap-1">
                            <AlertCircle class="size-3" /> {{ form.errors.type }}
                        </p>
                    </div>

                    <!-- Step 2: Link to Vehicle -->
                    <div v-if="leases.length" class="space-y-4">
                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Step 2: Link to Vehicle</label>
                        <div class="relative">
                            <select v-model="form.lease_id" 
                                class="w-full pl-6 pr-12 py-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-700 focus:ring-2 focus:ring-emerald-500 appearance-none transition-all cursor-pointer">
                                <option value="">General (Not linked to a lease)</option>
                                <option v-for="lease in leases" :key="lease.id" :value="lease.id">
                                    {{ lease.car?.license_plate }} — {{ lease.car?.model }}
                                </option>
                            </select>
                            <PlusCircle class="absolute right-5 top-1/2 -translate-y-1/2 size-5 text-slate-300 pointer-events-none" />
                        </div>
                    </div>

                    <!-- Step 3: Expiry Date (optional) -->
                    <div class="space-y-4">
                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Step 3: Expiry Date (optional)</label>
                        <div class="relative">
                            <input 
                                type="date" 
                                v-model="form.expiry_date" 
                                class="w-full pl-6 pr-4 py-4 bg-slate-50 border-none rounded-2xl font-medium text-slate-700 focus:ring-2 focus:ring-emerald-500 transition-all"
                                :min="new Date().toISOString().split('T')[0]"
                            />
                            <Calendar class="absolute right-5 top-1/2 -translate-y-1/2 size-5 text-slate-300 pointer-events-none" />
                        </div>
                        <p v-if="form.errors.expiry_date" class="text-xs text-rose-500 font-bold flex items-center gap-1">
                            <AlertCircle class="size-3" /> {{ form.errors.expiry_date }}
                        </p>
                    </div>

                    <!-- Step 4: Attach File -->
                    <div class="space-y-4">
                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Step 4: Attach File</label>
                        <div 
                            @dragover.prevent="isDragging = true" 
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="isDragging = false; handleFileChange($event)"
                            :class="[
                                'relative group border-2 border-dashed rounded-[2rem] p-12 transition-all flex flex-col items-center justify-center text-center',
                                isDragging ? 'bg-emerald-50 border-emerald-400 scale-[0.98]' : 'bg-slate-50 border-slate-200 hover:bg-white hover:border-emerald-300'
                            ]"
                        >
                            <input type="file" @change="handleFileChange" accept=".pdf,.jpg,.jpeg,.png" 
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required />
                            
                            <div v-if="!fileName" class="space-y-4">
                                <div class="size-20 bg-white rounded-[2rem] shadow-sm flex items-center justify-center text-slate-400 group-hover:text-emerald-500 transition-colors mx-auto">
                                    <FileUp class="size-10" />
                                </div>
                                <div>
                                    <p class="text-lg font-black text-slate-900">Drop file here</p>
                                    <p class="text-sm font-medium text-slate-400">or click to browse from gallery</p>
                                </div>
                            </div>

                            <div v-else class="space-y-4 animate-in zoom-in duration-300">
                                <div class="size-20 bg-emerald-100 rounded-[2rem] flex items-center justify-center text-emerald-600 mx-auto">
                                    <CheckCircle2 class="size-10" />
                                </div>
                                <div>
                                    <p class="text-lg font-black text-emerald-900 truncate max-w-[250px] mx-auto">{{ fileName }}</p>
                                    <p class="text-xs font-bold text-emerald-500 uppercase tracking-widest">Selected & Ready</p>
                                </div>
                            </div>
                        </div>
                        <p v-if="form.errors.file" class="text-xs text-rose-500 font-bold flex items-center gap-1">
                            <AlertCircle class="size-3" /> {{ form.errors.file }}
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-6">
                        <button type="submit" :disabled="form.processing || !form.type || !form.file" 
                            class="w-full py-5 bg-slate-900 hover:bg-emerald-600 text-white rounded-3xl font-black text-lg shadow-xl shadow-slate-200 transition-all active:scale-95 flex items-center justify-center gap-3 disabled:opacity-30">
                            <Loader2 v-if="form.processing" class="size-6 animate-spin" />
                            {{ form.processing ? 'Syncing Vault...' : 'Upload Document' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </DriverLayout>
</template>