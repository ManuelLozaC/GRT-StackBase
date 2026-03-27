<script setup>
import { sessionStore } from '@/core/auth/sessionStore';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const router = useRouter();
const route = useRoute();
const toast = useToast();

const email = ref('mloza@grt.com.bo');
const password = ref('admin1984!');
const checked = ref(true);
const loading = ref(false);
const errorMessage = ref('');
const submitLabel = computed(() => (loading.value ? 'Ingresando...' : 'Ingresar'));

async function submitLogin() {
    if (loading.value) {
        return;
    }

    loading.value = true;
    errorMessage.value = '';

    try {
        await sessionStore.login({
            email: email.value,
            password: password.value,
            device_name: checked.value ? 'frontend-remember' : 'frontend-session'
        });

        toast.add({
            severity: 'success',
            summary: 'Sesion iniciada',
            detail: 'Bienvenido a StackBase.',
            life: 2500
        });

        await router.push(route.query.redirect || '/');
    } catch (error) {
        errorMessage.value = error?.response?.data?.mensaje ?? 'Verifica tus credenciales e intentalo nuevamente.';

        toast.add({
            severity: 'error',
            summary: 'No se pudo iniciar sesion',
            detail: errorMessage.value,
            life: 4000
        });
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="min-h-screen min-w-[100vw] overflow-hidden bg-[radial-gradient(circle_at_top,_#e0f2fe,_#f8fafc_45%,_#dbeafe_100%)] flex items-center justify-center px-6">
        <div class="flex flex-col items-center justify-center">
            <div class="rounded-[40px] p-[2px] bg-[linear-gradient(160deg,_#0f172a,_#0ea5e9,_#ffffff)] shadow-2xl">
                <div class="w-full bg-white py-16 px-8 sm:px-16 rounded-[38px]">
                    <div class="text-center mb-8">
                        <svg viewBox="0 0 54 40" fill="none" xmlns="http://www.w3.org/2000/svg" class="mb-8 w-16 shrink-0 mx-auto">
                            <path
                                fill-rule="evenodd"
                                clip-rule="evenodd"
                                d="M17.1637 19.2467C17.1566 19.4033 17.1529 19.561 17.1529 19.7194C17.1529 25.3503 21.7203 29.915 27.3546 29.915C32.9887 29.915 37.5561 25.3503 37.5561 19.7194C37.5561 19.5572 37.5524 19.3959 37.5449 19.2355C38.5617 19.0801 39.5759 18.9013 40.5867 18.6994L40.6926 18.6782C40.7191 19.0218 40.7326 19.369 40.7326 19.7194C40.7326 27.1036 34.743 33.0896 27.3546 33.0896C19.966 33.0896 13.9765 27.1036 13.9765 19.7194C13.9765 19.374 13.9896 19.0316 14.0154 18.6927L14.0486 18.6994C15.0837 18.9062 16.1223 19.0886 17.1637 19.2467ZM33.3284 11.4538C31.6493 10.2396 29.5855 9.52381 27.3546 9.52381C25.1195 9.52381 23.0524 10.2421 21.3717 11.4603C20.0078 11.3232 18.6475 11.1387 17.2933 10.907C19.7453 8.11308 23.3438 6.34921 27.3546 6.34921C31.36 6.34921 34.9543 8.10844 37.4061 10.896C36.0521 11.1292 34.692 11.3152 33.3284 11.4538ZM44.2613 9.54743L40.9084 10.2176C37.9134 5.95821 32.9593 3.1746 27.3546 3.1746C21.7442 3.1746 16.7856 5.96385 13.7915 10.2305L10.4399 9.56057C13.892 3.83178 20.1756 0 27.3546 0C34.5281 0 40.8075 3.82591 44.2613 9.54743Z"
                                fill="var(--primary-color)"
                            />
                        </svg>
                        <div class="text-surface-900 dark:text-surface-0 text-3xl font-medium mb-4">StackBase</div>
                        <span class="text-muted-color font-medium">Inicia sesion para administrar el core y sus modulos</span>
                    </div>

                    <div class="mb-6 p-4 rounded-lg border border-primary-200 bg-primary-50 text-sm text-primary-900">
                        Usuario inicial: <b>mloza@grt.com.bo</b> o <b>mloza</b><br />
                        Password inicial: <b>admin1984!</b>
                    </div>

                    <form class="flex flex-col gap-4" @submit.prevent="submitLogin">
                        <div>
                            <label for="email1" class="block text-surface-900 dark:text-surface-0 text-xl font-medium mb-2">Correo o alias</label>
                            <InputText id="email1" v-model="email" type="text" placeholder="mloza@grt.com.bo o mloza" class="w-full" :disabled="loading" />
                        </div>

                        <div>
                            <label for="password1" class="block text-surface-900 dark:text-surface-0 font-medium text-xl mb-2">Contrasena</label>
                            <Password id="password1" v-model="password" placeholder="Contrasena" :toggleMask="true" fluid :feedback="false" :disabled="loading"></Password>
                        </div>

                        <div class="flex items-center justify-between mt-2 mb-2 gap-8">
                            <div class="flex items-center">
                                <Checkbox id="rememberme1" v-model="checked" binary class="mr-2" :disabled="loading"></Checkbox>
                                <label for="rememberme1">Recordar sesion</label>
                            </div>
                            <router-link to="/auth/forgot-password" class="font-medium no-underline ml-2 text-right text-primary">Olvide mi contrasena</router-link>
                        </div>

                        <Message v-if="errorMessage" severity="error" :closable="false">{{ errorMessage }}</Message>

                        <Button type="submit" :label="submitLabel" class="w-full" :loading="loading" :disabled="loading"></Button>
                    </form>

                    <div class="mt-6 text-center text-sm text-color-secondary">
                        <span>No tienes cuenta?</span>
                        <router-link to="/auth/register" class="ml-2 text-primary font-medium no-underline">Crear cuenta</router-link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
