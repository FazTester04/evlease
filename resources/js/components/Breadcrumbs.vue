<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { BreadcrumbItemType } from '@/types';

defineProps<{
    breadcrumbs: BreadcrumbItemType[];
}>();
</script>

<template>
    <nav class="flex items-center text-sm text-muted-foreground">
        <template v-for="(item, index) in breadcrumbs" :key="index">
            <span v-if="index > 0" class="mx-2">/</span>
            <Link v-if="item.href" :href="item.href" class="hover:text-foreground">
                {{ item.title }}
            </Link>
            <span v-else>{{ item.title }}</span>
        </template>
    </nav>
</template>