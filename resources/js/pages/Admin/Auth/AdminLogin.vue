<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Mail, Lock, Zap } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/admin/login', {
        onError: () => form.reset('password'),
    });
};
</script>

<template>
    <div>
        <Head title="Admin Access | EV Lease" />

        <!-- Background image and overlay -->
        <div class="fixed inset-0 -z-10">
            <img 
                src="https://images.unsplash.com/photo-1593941707882-a5bba14938c7?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80" 
                alt="EV Fleet" 
                class="h-full w-full object-cover"
            />
            <div class="absolute inset-0 bg-gradient-to-br from-slate-900/95 via-slate-800/95 to-slate-900/95"></div>
            <!-- Subtle grid overlay -->
            <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>

        <div class="relative flex min-h-screen items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
            <div class="w-full max-w-md">
                <!-- Logo and brand -->
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center space-x-2 text-white mb-6">
                        <Zap class="h-8 w-8 text-blue-400" />
                        <span class="text-2xl font-light tracking-wider">EV<span class="font-bold text-blue-400">Lease</span></span>
                    </div>
                    <h2 class="text-3xl font-light text-white">Administrator Portal</h2>
                    <p class="mt-2 text-sm text-white/60">Secure access for fleet management</p>
                </div>

                <!-- Login Card -->
                <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-2xl shadow-2xl p-8 transition-all hover:border-blue-500/50">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Email -->
                        <div>
                            <Label for="email" class="block text-sm font-medium text-white/80 mb-2">
                                Email Address
                            </Label>
                            <div class="relative">
                                <Mail class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-white/40" />
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    autofocus
                                    autocomplete="email"
                                    placeholder="admin@evlease.com"
                                    class="w-full pl-10 bg-white/5 border-white/10 text-white placeholder:text-white/30 focus:border-blue-400 focus:ring-blue-400/20"
                                />
                            </div>
                            <InputError :message="form.errors.email" class="mt-1" />
                        </div>

                        <!-- Password -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <Label for="password" class="block text-sm font-medium text-white/80">
                                    Password
                                </Label>
                                <!-- Optional "Forgot password?" link – uncomment if needed -->
                                <!-- <Link href="#" class="text-sm text-blue-400 hover:text-blue-300">Forgot?</Link> -->
                            </div>
                            <div class="relative">
                                <Lock class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-white/40" />
                                <Input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="••••••••"
                                    class="w-full pl-10 bg-white/5 border-white/10 text-white placeholder:text-white/30 focus:border-blue-400 focus:ring-blue-400/20"
                                />
                            </div>
                            <InputError :message="form.errors.password" class="mt-1" />
                        </div>

                        <!-- Remember me -->
                        <div class="flex items-center">
                            <input
                                id="remember"
                                v-model="form.remember"
                                type="checkbox"
                                class="h-4 w-4 rounded border-white/20 bg-white/5 text-blue-600 focus:ring-blue-500/20"
                            />
                            <label for="remember" class="ml-2 block text-sm text-white/60">
                                Keep me signed in
                            </label>
                        </div>

                        <!-- Submit -->
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="relative w-full overflow-hidden bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-semibold py-2.5 rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50"
                        >
                            <LoaderCircle v-if="form.processing" class="mr-2 h-5 w-5 animate-spin inline" />
                            {{ form.processing ? 'Authenticating...' : 'Access Dashboard' }}
                        </Button>
                    </form>
                </div>

                <!-- Footer security notice -->
                <div class="mt-6 text-center">
                    <div class="inline-flex items-center space-x-2 text-xs text-white/40">
                        <span>🔒 256‑bit SSL encrypted</span>
                        <span class="w-1 h-1 rounded-full bg-white/20"></span>
                        <span>Audit‑ready logging</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>