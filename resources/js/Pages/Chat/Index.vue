<script setup>
import AdminLayout from '@/layouts/AdminLayout.vue'; // adjust if you have a shared layout
import { Link } from '@inertiajs/vue3';

defineProps({
    conversations: Array,
});
</script>

<template>
    <AdminLayout>
        <div class="max-w-3xl mx-auto py-10">
            <h1 class="text-2xl font-bold mb-6">Messages</h1>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div v-if="conversations.length">
                    <Link
                        v-for="conv in conversations"
                        :key="conv.user.id"
                        :href="route('chat.show', conv.user.id)"
                        class="flex items-center justify-between p-4 border-b hover:bg-gray-50"
                    >
                        <div>
                            <p class="font-semibold">{{ conv.user.name }}</p>
                            <p class="text-sm text-gray-500 truncate max-w-md">
                                {{ conv.last_message?.message }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span v-if="conv.unread_count > 0" class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                                {{ conv.unread_count }}
                            </span>
                            <span class="text-xs text-gray-400">
                                {{ conv.last_message?.created_at ? new Date(conv.last_message.created_at).toLocaleDateString() : '' }}
                            </span>
                        </div>
                    </Link>
                </div>
                <div v-else class="p-8 text-center text-gray-500">
                    No conversations yet.
                </div>
            </div>
        </div>
    </AdminLayout>
</template>