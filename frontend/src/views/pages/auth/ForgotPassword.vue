<script setup>
import api from '@/service/api';
import FloatingConfigurator from '@/components/FloatingConfigurator.vue';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const toast = useToast();
const router = useRouter();
const email = ref('');
const loading = ref(false);
const previewToken = ref('');

async function submitForgotPassword() {
    loading.value = true;

    try {
        const response = await api.post('/v1/auth/forgot-password', {
            email: email.value
        });

        previewToken.value = response.data.meta?.debug_reset_token_preview ?? '';

        toast.add({
            severity: 'success',
            summary: 'Solicitud procesada',
            detail: 'Si el email existe, ya hay un token de recuperacion listo para pruebas locales.',
            life: 3000
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'No se pudo procesar',
            detail: error?.response?.data?.mensaje ?? 'Intenta nuevamente.',
            life: 4000
        });
    } finally {
        loading.value = false;
    }
}

async function openReset() {
    await router.push({
        name: 'resetPassword',
        query: {
            email: email.value,
            token: previewToken.value
        }
    });
}
</script>

<template>
    <FloatingConfigurator />
    <div class="auth-shell">
        <div class="auth-card">
            <div class="text-center mb-8">
                <div class="text-3xl font-medium mb-3">Recuperar password</div>
                <span class="text-color-secondary">Genera un token de recuperacion para pruebas locales del MVP.</span>
            </div>

            <form class="flex flex-col gap-4" @submit.prevent="submitForgotPassword">
                <InputText v-model="email" type="email" placeholder="Email" class="w-full" />
                <Button type="submit" label="Generar token" class="w-full" :loading="loading"></Button>
            </form>

            <div v-if="previewToken" class="token-box">
                <strong>Token de vista previa</strong>
                <code>{{ previewToken }}</code>
                <button class="token-link" @click="openReset">Ir a restablecer password</button>
            </div>

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

.token-box {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-top: 1.5rem;
    padding: 1rem;
    border-radius: 1rem;
    background: var(--surface-ground);
}

.token-box code {
    padding: 0.75rem;
    border-radius: 0.75rem;
    background: var(--surface-card);
    overflow: auto;
}

.token-link {
    border: 0;
    background: transparent;
    color: var(--primary-color);
    text-align: left;
    font-weight: 600;
    cursor: pointer;
    padding: 0;
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
