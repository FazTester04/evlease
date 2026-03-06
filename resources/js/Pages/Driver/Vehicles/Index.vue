<script setup>
import DriverLayout from '@/layouts/DriverLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Calendar, ShieldCheck, MapPin } from 'lucide-vue-next';

defineProps({
    vehicles: Array,
});
</script>

<template>
    <DriverLayout>
        <Head title="Available Vehicles" />

        <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-10">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="h-1 w-8 bg-emerald-600 rounded-full"></span>
                        <h4 class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Inventory Status</h4>
                    </div>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight">Vehicle Availability</h1>
                </div>
                <p class="text-[10px] font-black text-emerald-600 bg-emerald-50 border border-emerald-100 px-4 py-2 rounded-xl uppercase tracking-widest">
                    {{ vehicles.length }} Total Units
                </p>
            </div>

            <div v-if="vehicles.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div v-for="vehicle in vehicles" :key="vehicle.id" 
                    class="group bg-white rounded-[2rem] border border-slate-100 shadow-sm hover:border-emerald-200 transition-all duration-300">
                    
                    <div class="p-8">
                        <div class="flex justify-between items-start mb-8">
                            <div>
                                <h3 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none">
                                    {{ vehicle.make }} 
                                    <span class="text-emerald-600 block mt-1">{{ vehicle.model }}</span>
                                </h3>
                                <div class="mt-3 flex items-center gap-2">
                                    <span class="px-2 py-0.5 bg-slate-900 text-white text-[9px] font-black rounded uppercase tracking-widest">
                                        {{ vehicle.license_plate }}
                                    </span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">Registered Unit</span>
                                </div>
                            </div>
                            <div class="h-12 w-12 bg-slate-50 rounded-2xl flex items-center justify-center text-emerald-600 border border-slate-100">
                                <ShieldCheck class="w-6 h-6" />
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100/50 group-hover:bg-white transition-colors">
                                <div class="flex items-center gap-3">
                                    <Calendar class="w-4 h-4 text-slate-400" />
                                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Manufacturing Year</span>
                                </div>
                                <span class="text-sm font-black text-slate-900">{{ vehicle.year }}</span>
                            </div>

                            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100/50 group-hover:bg-white transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="h-4 w-4 rounded-full border border-white shadow-sm" :style="{ backgroundColor: vehicle.color }"></div>
                                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Exterior Color</span>
                                </div>
                                <span class="text-sm font-black text-slate-900 uppercase tracking-tighter">{{ vehicle.color }}</span>
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-slate-50 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Status: Available</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="bg-slate-50 border-2 border-dashed border-slate-200 p-20 rounded-[3rem] text-center">
                <h3 class="text-xl font-black text-slate-800 uppercase tracking-tight">No Units Available</h3>
                <p class="text-slate-400 font-medium mt-2 tracking-tight">All fleet vehicles are currently assigned to active leases.</p>
            </div>
        </div>
    </DriverLayout>
</template>

<style scoped>
.tracking-tighter { letter-spacing: -0.05em; }
</style>