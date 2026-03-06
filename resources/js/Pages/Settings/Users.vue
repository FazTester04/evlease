<script setup>
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';

defineProps({
    users: Array,
});

const updateRole = (userId, newRole) => {
    router.patch(route('admin.settings.users.role', userId), { role: newRole }, { preserveScroll: true });
};

const deleteUser = (userId) => {
    if (confirm('Delete this user? This action cannot be undone.')) {
        router.delete(route('admin.settings.users.destroy', userId), { preserveScroll: true });
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="User Management" />
        <div class="p-6 md:p-8">
            <h1 class="text-2xl font-bold mb-6">Users</h1>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="user in users" :key="user.id">
                            <td class="px-6 py-4">{{ user.name }}</td>
                            <td class="px-6 py-4">{{ user.email }}</td>
                            <td class="px-6 py-4">
                                <select
                                    @change="updateRole(user.id, $event.target.value)"
                                    class="border rounded px-2 py-1 text-sm"
                                    :disabled="user.id === $page.props.auth.user.id"
                                >
                                    <option value="admin" :selected="user.role === 'admin'">Admin</option>
                                    <option value="driver" :selected="user.role === 'driver'">Driver</option>
                                </select>
                            </td>
                            <td class="px-6 py-4">
                                <button
                                    v-if="user.id !== $page.props.auth.user.id"
                                    @click="deleteUser(user.id)"
                                    class="text-red-600 hover:text-red-800 text-sm font-medium"
                                >
                                    Delete
                                </button>
                                <span v-else class="text-gray-400 text-sm">Current</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>