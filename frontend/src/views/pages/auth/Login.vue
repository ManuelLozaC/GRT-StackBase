<script setup>
import { useAuthStore } from '@/stores/auth';
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const authStore = useAuthStore();
const identificador = ref('');
const password = ref('');
const error = ref('');

const cargando = computed(() => authStore.cargando);

async function iniciarSesion() {
    error.value = '';

    try {
        await authStore.iniciarSesion({
            identificador: identificador.value,
            password: password.value,
            device_name: 'frontend'
        });

        router.push({ name: 'dashboard' });
    } catch (err) {
        error.value = err?.response?.data?.mensaje || 'No se pudo iniciar sesión.';
    }
}
</script>

<template>
    <div class="min-h-screen min-w-[100vw] overflow-hidden bg-[radial-gradient(circle_at_top,_#e0f2fe,_#f8fafc_45%,_#dbeafe_100%)] flex items-center justify-center px-6">
        <div class="flex flex-col items-center justify-center">
            <div class="rounded-[40px] p-[2px] bg-[linear-gradient(160deg,_#0f172a,_#0ea5e9,_#ffffff)] shadow-2xl">
                <div class="w-full bg-white py-16 px-8 sm:px-16 rounded-[38px]">
                    <div class="text-center mb-8">
                        <div class="text-slate-900 text-3xl font-semibold mb-3">GRT StackBase</div>
                        <span class="text-slate-500 font-medium">Inicia sesión con tu correo o alias</span>
                    </div>

                    <form @submit.prevent="iniciarSesion" class="space-y-5">
                        <div>
                            <label for="identificador" class="block text-slate-900 text-xl font-medium mb-2">Correo o alias</label>
                            <InputText id="identificador" type="text" placeholder="mloza o mloza@grt.com.bo" class="w-full md:w-[30rem]" v-model="identificador" />
                        </div>

                        <div>
                            <label for="password1" class="block text-slate-900 font-medium text-xl mb-2">Contraseña</label>
                            <Password id="password1" v-model="password" placeholder="Contraseña" :toggleMask="true" fluid :feedback="false"></Password>
                        </div>

                        <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

                        <div class="rounded-2xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-600">Usuario inicial: <strong>mloza@grt.com.bo</strong> o <strong>mloza</strong></div>

                        <div class="rounded-2xl bg-sky-50 border border-sky-200 px-4 py-3 text-sm text-sky-800">Contraseña inicial: <strong>admin1984!</strong></div>

                        <Button :label="cargando ? 'Ingresando...' : 'Iniciar sesión'" class="w-full" type="submit" :loading="cargando"></Button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
