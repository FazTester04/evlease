<script setup>
import { SidebarProvider, SidebarInset, SidebarTrigger } from '@/components/ui/sidebar';
import AppSidebar from '@/components/DriverSidebar.vue'; // This is the component we just redesigned
</script>

<template>
    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <header class="flex h-16 shrink-0 items-center gap-2 border-b border-slate-100 px-4">
                <SidebarTrigger class="-ml-1 text-slate-500" />
                
                <div class="h-4 w-[1px] bg-slate-200 mx-2"></div>
                
                <span class="text-sm font-medium text-slate-600">Driver Portal</span>
            </header>

            <main class="flex-1 bg-slate-50/50">
                <slot />
            </main>
        </SidebarInset>
    </SidebarProvider>
</template>