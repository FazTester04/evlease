<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Mail, Lock, Leaf } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onError: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 font-sans antialiased selection:bg-emerald-100 selection:text-emerald-900">
        <Head title="Sign In | EV Lease" />

        <div class="fixed inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-[10%] -right-[10%] w-[40%] h-[40%] rounded-full bg-emerald-500/5 blur-[120px]"></div>
            <div class="absolute -bottom-[10%] -left-[10%] w-[40%] h-[40%] rounded-full bg-emerald-500/10 blur-[120px]"></div>
        </div>

        <div class="relative flex flex-col items-center justify-center min-h-screen px-4">
            
            <div class="mb-10 flex flex-col items-center">
                <div class="h-12 w-12 bg-emerald-600 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-200 mb-4">
                    <Leaf class="h-7 w-7 text-white" />
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    EV<span class="text-emerald-600">Lease</span>
                </h1>
            </div>

            <div class="w-full max-w-[400px]">
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200/60 p-8 md:p-10">
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold text-slate-900">Welcome back</h2>
                        <p class="text-slate-500 text-sm mt-1">Enter your details to manage your fleet.</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <div class="space-y-2">
                            <Label for="email" class="text-xs font-semibold uppercase tracking-wider text-slate-500 ml-1">
                                Email Address
                            </Label>
                            <div class="relative group">
                                <Mail class="absolute left-3 top-3 h-4 w-4 text-slate-400 transition-colors group-focus-within:text-emerald-600" />
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    placeholder="name@company.com"
                                    class="pl-10 h-11 border-slate-200 focus:border-emerald-500 focus:ring-emerald-500/10 rounded-xl transition-all"
                                />
                            </div>
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between px-1">
                                <Label for="password" class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Password
                                </Label>
                                <a href="#" class="text-xs font-medium text-emerald-600 hover:text-emerald-700">Forgot?</a>
                            </div>
                            <div class="relative group">
                                <Lock class="absolute left-3 top-3 h-4 w-4 text-slate-400 transition-colors group-focus-within:text-emerald-600" />
                                <Input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    required
                                    placeholder="••••••••"
                                    class="pl-10 h-11 border-slate-200 focus:border-emerald-500 focus:ring-emerald-500/10 rounded-xl transition-all"
                                />
                            </div>
                            <InputError :message="form.errors.password" />
                        </div>

                        <div class="flex items-center space-x-2 px-1">
                            <input
                                id="remember"
                                v-model="form.remember"
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                            />
                            <label for="remember" class="text-sm text-slate-600 cursor-pointer select-none">
                                Keep me logged in
                            </label>
                        </div>

                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full h-11 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl transition-all duration-200 shadow-md shadow-emerald-100 active:scale-[0.98]"
                        >
                            <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                            {{ form.processing ? 'Verifying...' : 'Sign In' }}
                        </Button>
                    </form>
                </div>

                <div class="mt-8 text-center">
                    <p class="text-xs text-slate-400 font-medium tracking-wide italic">
                        Secured by EVLease Systems &bull; 2026
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Optional: Adding a very subtle fade-in animation */
.bg-white {
    animation: slideUp 0.5s ease-out;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>