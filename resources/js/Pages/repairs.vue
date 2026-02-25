<template>
    <AppSidebarLayout>
        <div class="p-6 md:p-8 bg-slate-50 min-h-screen">
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Repairs & Maintenance</h1>
            <p class="text-sm text-slate-500 mt-2">This section is under construction. Check back soon!</p>
            <div class="mt-8 bg-white border border-slate-200 rounded-xl shadow-sm p-8 text-center">
                <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <p class="text-slate-500">Repair tracking features coming soon.</p>
            </div>
        </div>
    </AppSidebarLayout>
</template>

<script setup>
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
</script>

<style scoped>
@reference "tailwindcss";
/* No extra styles needed – all handled by Tailwind */
</style>