<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3'
import { ref } from 'vue'

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const showPassword = ref(false)

const submit = () => {
    form.post(route('admin.login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <Head title="Administrative Access" />

    <div class="min-h-screen bg-slate-950 flex items-center justify-center p-6 selection:bg-rose-500 selection:text-white">
        <!-- Background Grid Effect -->
        <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
            <div class="absolute inset-0 bg-[linear-gradient(to_right,#80808012_1px,transparent_1px),linear-gradient(to_bottom,#80808012_1px,transparent_1px)] bg-[size:24px_24px]"></div>
            <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-rose-500/10 rounded-full blur-[120px]"></div>
        </div>

        <div class="w-full max-w-[420px] relative z-10">
            <!-- Branding -->
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-white/5 border border-white/10 rounded-[2rem] shadow-2xl mb-6 group hover:border-rose-500/30 transition-all duration-500">
                    <svg class="w-8 h-8 text-rose-500 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-black text-white tracking-tight uppercase">Admin Console</h1>
                <p class="text-slate-500 text-[10px] font-black uppercase tracking-[0.3em] mt-2">Restricted Governance Portal</p>
            </div>

            <!-- Login Card -->
            <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-[2.5rem] p-8 md:p-10 shadow-3xl">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 ml-1">Secure ID (Email)</label>
                        <input 
                            v-model="form.email"
                            type="email" 
                            required
                            class="w-full bg-black/40 border-white/5 focus:border-rose-500 focus:ring-rose-500/20 rounded-2xl py-4 px-6 text-sm text-white font-bold transition-all placeholder:text-slate-700"
                            placeholder="operator@metapilot.internal"
                        />
                        <p v-if="form.errors.email" class="mt-2 text-xs font-bold text-rose-500 italic">{{ form.errors.email }}</p>
                    </div>

                    <div class="relative">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 ml-1">Access Phrase</label>
                        <input 
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'" 
                            required
                            class="w-full bg-black/40 border-white/5 focus:border-rose-500 focus:ring-rose-500/20 rounded-2xl py-4 px-6 text-sm text-white font-bold transition-all placeholder:text-slate-700"
                            placeholder="••••••••••••"
                        />
                        <button 
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-4 top-[42px] text-slate-600 hover:text-rose-500 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="!showPassword" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path v-if="!showPassword" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                <path v-if="showPassword" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 012.183-3.078m5.758-2.867A10.012 10.012 0 0112 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-1.254 0-2.435-.292-3.487-.813m1.414-1.414a3 3 0 114.242-4.242" />
                            </svg>
                        </button>
                        <p v-if="form.errors.password" class="mt-2 text-xs font-bold text-rose-500 italic">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex items-center justify-between py-2">
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" v-model="form.remember" class="w-4 h-4 rounded-lg bg-black/40 border-white/10 text-rose-600 focus:ring-rose-500/20" />
                            <span class="text-[10px] font-black text-slate-600 uppercase tracking-widest group-hover:text-slate-400 transition-colors">Remember Station</span>
                        </label>
                    </div>

                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="w-full bg-rose-600 hover:bg-rose-500 disabled:bg-slate-800 disabled:text-slate-600 text-white py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] shadow-lg shadow-rose-900/20 transition-all active:scale-[0.98] flex items-center justify-center gap-3"
                    >
                        <span v-if="form.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        Initiate Secure Session
                    </button>
                </form>

                <div class="mt-10 pt-8 border-t border-white/5 flex flex-col items-center gap-4">
                    <p class="text-[9px] font-black text-slate-700 uppercase tracking-[0.2em] text-center">Unauthorized attempts are logged and reported to the forensic center.</p>
                </div>
            </div>
        </div>

        <!-- System Footer -->
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 text-[9px] font-black text-slate-800 uppercase tracking-[1em] whitespace-nowrap">
            MetaPilot Internal Governance v3.5
        </div>
    </div>
</template>

<style scoped>
input:-webkit-autofill,
input:-webkit-autofill:hover, 
input:-webkit-autofill:focus {
  -webkit-text-fill-color: white;
  -webkit-box-shadow: 0 0 0px 1000px #000 inset;
  transition: background-color 5000s ease-in-out 0s;
}
</style>
