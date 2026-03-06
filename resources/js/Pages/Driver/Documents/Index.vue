<script setup>
import DriverLayout from '@/Layouts/DriverLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    FileText, 
    Plus, 
    ExternalLink, 
    Trash2, 
    Search,
    CheckCircle2,
    Clock,
    AlertCircle,
    FileSearch
} from 'lucide-vue-next';

const props = defineProps({
    documents: Object, // paginated
});

const formatDate = (date) => {
    return date ? new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';
};

const deleteDocument = (id) => {
    if (confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
        router.delete(route('driver.documents.destroy', id));
    }
};

const documentStatus = (doc) => {
    if (!doc.expiry_date) return null;
    const today = new Date();
    today.setHours(0,0,0,0);
    const expiry = new Date(doc.expiry_date);
    if (expiry < today) return 'expired';
    const diffDays = Math.ceil((expiry - today) / (1000*60*60*24));
    if (diffDays <= 30) return 'expiring';
    return 'valid';
};
</script>

<template>
    <DriverLayout>
        <Head title="My Documents" />

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 tracking-tight">My Documents</h1>
                    <p class="text-slate-500 mt-1">Manage and track the expiry of your lease paperwork.</p>
                </div>
                
                <Link 
                    :href="route('driver.documents.create')" 
                    class="inline-flex items-center justify-center space-x-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold transition-all shadow-md shadow-emerald-100 active:scale-95"
                >
                    <Plus class="h-5 w-5" />
                    <span>Upload New</span>
                </Link>
            </div>

            <div v-if="documents.data?.length" class="space-y-3">
                <!-- Updated header – removed Verification column -->
                <div class="hidden md:grid grid-cols-12 px-8 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest">
                    <div class="col-span-4">Document Name</div>
                    <div class="col-span-2 text-center">Type</div>
                    <div class="col-span-5 text-center">Expiry</div>
                    <div class="col-span-1"></div>
                </div>

                <div 
                    v-for="doc in documents.data" 
                    :key="doc.id" 
                    class="group bg-white border border-slate-200/60 rounded-2xl px-6 py-4 md:px-8 md:py-5 flex flex-col md:grid md:grid-cols-12 items-center gap-4 transition-all hover:shadow-lg hover:shadow-emerald-900/5 hover:border-emerald-200"
                >
                    <!-- Document Name (col-span-4) -->
                    <div class="col-span-4 flex items-center space-x-4 w-full">
                        <div class="h-12 w-12 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400 transition-colors group-hover:bg-emerald-50 group-hover:text-emerald-600">
                            <FileText class="h-6 w-6" />
                        </div>
                        <div class="overflow-hidden">
                            <a :href="'/storage/' + doc.file_path" target="_blank" class="text-sm font-bold text-slate-900 hover:text-emerald-600 flex items-center gap-2 truncate">
                                {{ doc.name }}
                                <ExternalLink class="h-3 w-3 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </a>
                            <p class="text-xs text-slate-400 flex items-center mt-1">
                                <span class="md:hidden mr-2">Lease:</span> {{ doc.lease?.car?.license_plate || 'No Lease Attached' }}
                            </p>
                            <!-- Upload date as a small note -->
                            <p class="text-[10px] text-slate-400 mt-1">
                                Uploaded: {{ formatDate(doc.created_at) }}
                            </p>
                        </div>
                    </div>

                    <!-- Type (col-span-2) -->
                    <div class="col-span-2 text-center w-full">
                        <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1 rounded-lg capitalize">
                            {{ doc.type.replace('_', ' ') }}
                        </span>
                    </div>

                    <!-- Expiry (col-span-5) – now wider, includes expiry date + status badge -->
                    <div class="col-span-5 text-center w-full space-y-1">
                        <div v-if="doc.expiry_date" class="text-sm font-medium text-slate-700">
                            {{ formatDate(doc.expiry_date) }}
                        </div>
                        <div v-else class="text-sm font-medium text-slate-300">—</div>

                        <!-- Expiry status badge -->
                        <div v-if="documentStatus(doc) === 'expired'" class="inline-flex items-center space-x-1.5 text-rose-700 bg-rose-50 px-3 py-1 rounded-full border border-rose-100 text-[10px] font-bold uppercase tracking-tight">
                            <AlertCircle class="h-3 w-3" />
                            <span>Expired</span>
                        </div>
                        <div v-else-if="documentStatus(doc) === 'expiring'" class="inline-flex items-center space-x-1.5 text-amber-700 bg-amber-50 px-3 py-1 rounded-full border border-amber-100 text-[10px] font-bold uppercase tracking-tight">
                            <Clock class="h-3 w-3" />
                            <span>Expiring soon</span>
                        </div>
                        <div v-else-if="documentStatus(doc) === 'valid'" class="inline-flex items-center space-x-1.5 text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-100 text-[10px] font-bold uppercase tracking-tight">
                            <CheckCircle2 class="h-3 w-3" />
                            <span>Valid</span>
                        </div>
                    </div>

                    <!-- Actions (col-span-1) -->
                    <div class="col-span-1 flex justify-end w-full">
                        <button 
                            @click="deleteDocument(doc.id)" 
                            class="p-2 text-slate-300 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all"
                            title="Delete Document"
                        >
                            <Trash2 class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <!-- Pagination (unchanged) -->
                <div v-if="documents.links?.length > 3" class="mt-10 flex justify-center items-center space-x-2">
                    <template v-for="(link, key) in documents.links" :key="key">
                        <div v-if="link.url === null" 
                             class="px-4 py-2 text-sm text-slate-300 border border-transparent select-none"
                             v-html="link.label" />
                        <Link v-else 
                              :href="link.url" 
                              class="px-4 py-2 text-sm font-bold rounded-xl transition-all"
                              :class="link.active ? 'bg-emerald-600 text-white shadow-md shadow-emerald-100' : 'text-slate-500 hover:bg-white border border-transparent hover:border-slate-200'"
                              v-html="link.label" />
                    </template>
                </div>
            </div>

            <div v-else class="bg-white border-2 border-dashed border-slate-200 rounded-[2.5rem] p-20 text-center">
                <div class="inline-flex items-center justify-center h-24 w-24 rounded-full bg-slate-50 mb-8">
                    <FileSearch class="h-12 w-12 text-slate-200" />
                </div>
                <h3 class="text-xl font-bold text-slate-900">No documents found</h3>
                <p class="text-slate-500 mt-2 max-w-sm mx-auto">
                    You haven't uploaded any documents yet. Upload your driver's license or lease agreements to get started.
                </p>
                <Link :href="route('driver.documents.create')" class="mt-8 inline-flex items-center text-emerald-600 font-bold hover:text-emerald-700">
                    <Plus class="h-4 w-4 mr-2" /> Upload your first document
                </Link>
            </div>
        </div>
    </DriverLayout>
</template>

<style scoped>
/* Staggered Entrance Animation */
.group {
    animation: slideIn 0.4s ease-out forwards;
    opacity: 0;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateX(-10px); }
    to { opacity: 1; transform: translateX(0); }
}

/* Add a slight delay for each item for a "cascade" effect */
.group:nth-child(1) { animation-delay: 0.05s; }
.group:nth-child(2) { animation-delay: 0.1s; }
.group:nth-child(3) { animation-delay: 0.15s; }
</style>