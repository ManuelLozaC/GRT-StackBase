<script setup>
import { sessionStore } from '@/core/auth/sessionStore';
import { useToast } from 'primevue/usetoast';
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const toast = useToast();

const loading = ref(false);
const form = reactive({
    name: '',
    email: '',
    organization_name: '',
    password: '',
    password_confirmation: '',
    device_name: 'frontend-register'
});

async function submitRegister() {
    loading.value = true;

    try {
        await sessionStore.register(form);

        toast.add({
            severity: 'success',
            summary: 'Cuenta creada',
            detail: 'Tu workspace inicial ya esta listo.',
            life: 2500
        });

        await router.push('/');
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'No se pudo crear la cuenta',
            detail: error?.response?.data?.mensaje ?? 'Revisa los datos enviados.',
            life: 4000
        });
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="auth-shell">
        <div class="auth-card">
            <div class="text-center mb-8">
                <div class="text-3xl font-medium mb-3">Crear cuenta</div>
                <span class="text-color-secondary">Registra un usuario nuevo y crea su workspace inicial.</span>
            </div>

            <form class="flex flex-col gap-4" @submit.prevent="submitRegister">
                <InputText v-model="form.name" type="text" placeholder="Nombre completo" class="w-full" />
                <InputText v-model="form.email" type="email" placeholder="Email" class="w-full" />
                <InputText v-model="form.organization_name" type="text" placeholder="Nombre del workspace" class="w-full" />
                <Password v-model="form.password" placeholder="Password" :toggleMask="true" fluid :feedback="false"></Password>
                <Password v-model="form.password_confirmation" placeholder="Confirmar password" :toggleMask="true" fluid :feedback="false"></Password>

                <Button type="submit" label="Crear cuenta" class="w-full" :loading="loading"></Button>
            </form>

            <div class="auth-links">
                <router-link to="/auth/login">Volver al login</router-link>
            </div>
        </div>
    </div>
</template>

<style scoped>
.auth-shell {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    background: linear-gradient(180deg, var(--surface-50), var(--surface-200));
}

.auth-card {
    width: min(100%, 32rem);
    background: var(--surface-card);
    border-radius: 2rem;
    padding: 2.5rem;
    box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
}

.auth-links {
    margin-top: 1.5rem;
    text-align: center;
}

.auth-links a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 600;
}
</style>
