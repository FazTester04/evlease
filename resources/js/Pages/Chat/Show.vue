<script setup>
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted, nextTick } from 'vue';

const props = defineProps({
    otherUser: Object,
    messages: Array,
});

const form = useForm({
    message: '', // recipient_id is NOT in the form object – we'll use a hidden input
});

const messagesContainer = ref(null);
const messagesEnd = ref(null);

const sendMessage = () => {
    if (!form.message.trim()) return;
    form.post(route('chat.store'), {
        preserveScroll: false,
        onSuccess: () => {
            form.message = '';
            scrollToBottom();
        },
    });
};

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesEnd.value) {
            messagesEnd.value.scrollIntoView({ behavior: 'smooth' });
        }
    });
};

onMounted(() => {
    scrollToBottom();
});
</script>

<template>
    <AdminLayout>
        <Head :title="`Chat with ${otherUser.name}`" />

        <div class="max-w-3xl mx-auto py-10">
            <div class="flex items-center gap-4 mb-4">
                <Link :href="route('chat.index')" class="text-indigo-600 hover:underline">← Back</Link>
                <h1 class="text-xl font-semibold">{{ otherUser.name }}</h1>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div ref="messagesContainer" class="h-96 overflow-y-auto p-6 space-y-4">
                    <div v-for="msg in messages" :key="msg.id">
                        <div class="flex" :class="msg.sender_id === $page.props.auth.user.id ? 'justify-end' : 'justify-start'">
                            <div :class="[
                                'max-w-md px-4 py-2 rounded-lg',
                                msg.sender_id === $page.props.auth.user.id
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-gray-100 text-gray-800'
                            ]">
                                <p>{{ msg.message }}</p>
                                <p class="text-xs opacity-75 mt-1">{{ new Date(msg.created_at).toLocaleTimeString() }}</p>
                            </div>
                        </div>
                    </div>
                    <div ref="messagesEnd" />
                </div>

                <form @submit.prevent="sendMessage" class="border-t p-4 flex gap-2">
                    <!-- Hidden input ensures recipient_id is sent -->
                    <input type="hidden" name="recipient_id" :value="otherUser.id" />

                    <input
                        v-model="form.message"
                        type="text"
                        placeholder="Type your message..."
                        class="flex-1 border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        required
                    />
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                    >
                        Send
                    </button>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>