<script setup lang="ts">
import { ref } from 'vue';
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
    Car, 
    KeyRound, 
    CreditCard, 
    Users2, 
    FileStack, 
    LogOut,
    Settings,
    MessageSquare,
    Calendar,
    ChevronDown,
    Wrench
} from 'lucide-vue-next';
import NavUser from '@/components/NavUser.vue';
import { Leaf } from 'lucide-vue-next';

const page = usePage();
const openSettings = ref(false); // controls dropdown

const mainNavItems = [
    { title: 'Dashboard', href: '/admin/ev-dashboard', icon: LayoutDashboard },
    { title: 'Overdue', href: '/admin/overdue-payments', icon: Calendar },
    { title: 'Drivers', href: '/admin/drivers', icon: Users2 },
    { title: 'Vehicles', href: '/admin/cars', icon: Car },
    { title: 'Leases', href: '/admin/leases', icon: KeyRound },
    { title: 'Payments', href: '/admin/payments', icon: CreditCard },
    { title: 'Maintenance', href: '/admin/maintenance', icon: Wrench }, 
    { title: 'Documents', href: '/admin/documents', icon: FileStack },
    // { title: 'Messages', href: '/chat', icon: MessageSquare }, 
];

const settingsChildren = [
    // { title: 'Late Fee', href: '/admin/settings/late-fee', icon: Settings },
    { title: 'Users', href: '/admin/settings/users', icon: Users2 },
];

const isActive = (href: string) => page.url === href || page.url.startsWith(href + '/');

const logout = () => router.post(route('logout'));
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="border-r border-slate-100">
        <SidebarHeader class="p-4">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child class="hover:bg-transparent">
                        <Link href="/ev-dashboard" class="flex items-center gap-3">
                            <div class="flex aspect-square size-10 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-lg shadow-emerald-200">
                                <Leaf class="size-6" />
                            </div>
                            <div class="grid flex-1 text-left leading-tight">
                                <span class="truncate font-bold text-slate-900">Xperts EV Fleet</span>
                                <span class="truncate text-xs font-medium text-slate-500">Admin Control</span>
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup>
                <SidebarGroupLabel class="px-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                    Fleet Management
                </SidebarGroupLabel>
                <SidebarMenu class="gap-1 px-2">
                    <!-- Main nav items -->
                    <SidebarMenuItem v-for="item in mainNavItems" :key="item.title">
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
                                <component :is="item.icon" :class="[
                                    'size-5 transition-colors',
                                    isActive(item.href) ? 'text-emerald-600' : 'text-slate-400 group-hover:text-slate-600'
                                ]" />
                                <span class="font-bold tracking-tight">{{ item.title }}</span>
                                <div v-if="isActive(item.href)" class="absolute right-3 size-1.5 rounded-full bg-emerald-600 shadow-[0_0_8px_rgba(5,150,105,0.5)]"></div>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <!-- Settings dropdown -->
                    <SidebarMenuItem>
                        <button 
                            @click="openSettings = !openSettings"
                            class="flex items-center justify-between w-full px-3 py-6 rounded-xl text-slate-500 hover:bg-slate-50 hover:text-slate-900 transition-all"
                        >
                            <span class="flex items-center gap-3">
                                <Settings class="size-5 text-slate-400" />
                                <span class="font-bold tracking-tight">Settings</span>
                            </span>
                            <ChevronDown class="size-4 transition-transform" :class="{ 'rotate-180': openSettings }" />
                        </button>
                    </SidebarMenuItem>

                    <!-- Settings children (only shown when open) -->
                    <template v-if="openSettings">
                        <SidebarMenuItem v-for="child in settingsChildren" :key="child.title" class="pl-8">
                            <SidebarMenuButton 
                                as-child 
                                :tooltip="child.title"
                                :class="[
                                    'relative flex items-center gap-3 px-3 py-6 rounded-xl transition-all duration-200 group',
                                    isActive(child.href) 
                                        ? 'bg-emerald-50 text-emerald-700 shadow-sm shadow-emerald-100/50' 
                                        : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'
                                ]"
                            >
                                <Link :href="child.href">
                                    <component :is="child.icon" :class="[
                                        'size-5 transition-colors',
                                        isActive(child.href) ? 'text-emerald-600' : 'text-slate-400 group-hover:text-slate-600'
                                    ]" />
                                    <span class="font-bold tracking-tight">{{ child.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </template>
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