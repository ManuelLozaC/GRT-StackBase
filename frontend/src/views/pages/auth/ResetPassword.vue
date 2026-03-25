<script setup>
import api from '@/service/api';
import FloatingConfigurator from '@/components/FloatingConfigurator.vue';
import { useToast } from 'primevue/usetoast';
import { reactive, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const form = reactive({
    email: route.query.email || '',
    token: route.query.token || '',
    password: '',
    password_confirmation: ''
});

watch(
    () => route.query,
    (query) => {
        form.email = query.email || '';
        form.token = query.token || '';
    }
);

async function submitResetPassword() {
    try {
        await api.post('/v1/auth/reset-password', form);

        toast.add({
            severity: 'success',
            summary: 'Password actualizada',
            detail: 'Ahora ya puedes iniciar sesion con la nueva credencial.',
            life: 3000
        });

        await router.push('/auth/login');
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'No se pudo restablecer',
            detail: error?.response?.data?.mensaje ?? 'Verifica email, token y password.',
            life: 4000
        });
    }
}
</script>

<template>
    <FloatingConfigurator />
    <div class="auth-shell">
        <div class="auth-card">
            <div class="text-center mb-8">
                <div class="text-3xl font-medium mb-3">Restablecer password</div>
                <span class="text-color-secondary">Completa el token y define una nueva password para la cuenta.</span>
            </div>

            <form class="flex flex-col gap-4" @submit.prevent="submitResetPassword">
                <InputText v-model="form.email" type="email" placeholder="Email" class="w-full" />
                <InputText v-model="form.token" type="text" placeholder="Token de recuperacion" class="w-full" />
                <Password v-model="form.password" placeholder="Nueva password" :toggleMask="true" fluid :feedback="false"></Password>
                <Password v-model="form.password_confirmation" placeholder="Confirmar nueva password" :toggleMask="true" fluid :feedback="false"></Password>

                <Button type="submit" label="Restablecer password" class="w-full"></Button>
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
