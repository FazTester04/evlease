<script setup>
import { 
    Sidebar, 
    SidebarContent, 
    SidebarFooter, 
    SidebarHeader, 
    SidebarMenu, 
    SidebarMenuItem, 
    SidebarMenuButton,
    SidebarGroup,
    SidebarGroupLabel
} from '@/components/ui/sidebar';
import { Link, usePage, router } from '@inertiajs/vue3';
import { 
    LayoutDashboard, 
    FileStack, 
    WalletCards, 
    LogOut,
    ExternalLink,
    CarIcon,
    CreditCard,
    FileInput,
    MessageSquare
} from 'lucide-vue-next';
import NavUser from '@/components/NavUser.vue';
import AppLogo from '@/components/AppLogo.vue';
import { LoaderCircle, Mail, Lock, Leaf } from 'lucide-vue-next'

const page = usePage();

const navItems = [
    { title: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
    { title: 'Lease', href: '/driver/leases', icon: WalletCards },
    { title: 'Payments', href: '/driver/payments', icon: CreditCard }, 
     { title: 'Vehicle', href: '/driver/vehicles', icon: CarIcon },   
    { title: 'Documents', href: '/driver/documents', icon: FileInput },
    // { title: 'Messages', href: '/chat', icon: MessageSquare },

];

// Active link detection
const isActive = (href) => {
    return page.url === href || page.url.startsWith(href + '/');
};

// Logout function
const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="border-r border-slate-100">
        <SidebarHeader class="p-4">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child class="hover:bg-transparent">
                        <Link href="/dashboard" class="flex items-center gap-3">
                            <div class="flex aspect-square size-10 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-lg shadow-emerald-200">
                                <Leaf class="h-7 w-7 text-white" />
                            </div>
                            <div class="grid flex-1 text-left leading-tight">
                                <span class="truncate font-bold text-slate-900"> Xperts EV Fleet</span>
                                <span class="truncate text-xs font-medium text-slate-500">Driver Portal</span>
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup>
                <SidebarGroupLabel class="px-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                    Main Menu
                </SidebarGroupLabel>
                <SidebarMenu class="gap-1 px-2">
                    <SidebarMenuItem v-for="item in navItems" :key="item.title">
                        <SidebarMenuButton 
                            as-child 
                            :tooltip="item.title"
                            :class="[
                                'relative flex items-center gap-3 px-3 py-6 rounded-xl transition-all duration-200 group',
                                isActive(item.href) 
                                    ? 'bg-emerald-50 text-emerald-700 shadow-sm shadow-emerald-100/50' 
                                    : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'
                            ]"
                        >
                            <Link :href="item.href">
                                <component 
                                    :is="item.icon" 
                                    :class="[
                                        'size-5 transition-colors',
                                        isActive(item.href) ? 'text-emerald-600' : 'text-slate-400 group-hover:text-slate-600'
                                    ]" 
                                />
                                <span class="font-bold tracking-tight">{{ item.title }}</span>
                                
                                <div v-if="isActive(item.href)" class="absolute right-3 size-1.5 rounded-full bg-emerald-600"></div>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>

            <SidebarGroup class="mt-auto">
                <SidebarMenu class="px-2">
                    <SidebarMenuItem>
                        <SidebarMenuButton class="text-slate-400 hover:text-slate-600 transition-colors">
                            <a href="#" class="flex items-center gap-3 px-3">
                                <ExternalLink class="size-4" />
                                <span class="text-xs font-bold uppercase tracking-wider">Help Center</span>
                            </a>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter class="border-t border-slate-50 p-4">
            <SidebarMenu>
                <SidebarMenuItem>
                    <NavUser />
                </SidebarMenuItem>
                
                <SidebarMenuItem class="mt-2">
                    <button 
                        @click="logout"
                        class="flex w-full items-center gap-3 px-3 py-3 rounded-xl text-slate-500 hover:bg-rose-50 hover:text-rose-600 transition-all duration-200 group"
                    >
                        <div class="flex size-8 items-center justify-center rounded-lg bg-slate-50 group-hover:bg-rose-100 transition-colors">
                            <LogOut class="size-4" />
                        </div>
                        <span class="text-sm font-bold">Logout</span>
                    </button>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>
    </Sidebar>
</template>

<style scoped>
/* Optional: Smooth transition for the sidebar expand/collapse */
.sidebar-enter-active, .sidebar-leave-active {
    transition: all 0.3s ease;
}
</style>