import { defineStore } from 'pinia';
import api from '@/service/api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem('grt_token'),
        oficinaId: localStorage.getItem('grt_oficina_id'),
        usuario: JSON.parse(localStorage.getItem('grt_usuario') || 'null'),
        cargando: false
    }),
    getters: {
        estaAutenticado: (state) => Boolean(state.token && state.usuario)
    },
    actions: {
        async iniciarSesion(payload) {
            this.cargando = true;

            try {
                const { data } = await api.post('/auth/login', payload);
                const respuesta = data?.datos ?? {};

                this.token = respuesta.token;
                this.usuario = respuesta.usuario;
                this.oficinaId = respuesta.oficina_id_activa ?? null;

                localStorage.setItem('grt_token', this.token);
                localStorage.setItem('grt_usuario', JSON.stringify(this.usuario));

                if (this.oficinaId) {
                    localStorage.setItem('grt_oficina_id', this.oficinaId);
                }

                return data;
            } finally {
                this.cargando = false;
            }
        },
        async cargarSesion() {
            if (!this.token) {
                return null;
            }

            try {
                const { data } = await api.get('/auth/me');
                this.usuario = data?.datos?.usuario ?? null;
                localStorage.setItem('grt_usuario', JSON.stringify(this.usuario));
                return this.usuario;
            } catch (error) {
                this.cerrarSesionLocal();
                throw error;
            }
        },
        async cerrarSesion() {
            try {
                if (this.token) {
                    await api.post('/auth/logout');
                }
            } finally {
                this.cerrarSesionLocal();
            }
        },
        cerrarSesionLocal() {
            this.token = null;
            this.oficinaId = null;
            this.usuario = null;

            localStorage.removeItem('grt_token');
            localStorage.removeItem('grt_oficina_id');
            localStorage.removeItem('grt_usuario');
        }
    }
});
