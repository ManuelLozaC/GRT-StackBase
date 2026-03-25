<script setup>
import { notificationStore } from '@/core/notifications/notificationStore';
import api from '@/service/api';
import { reactive } from 'vue';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

const form = reactive({
    title: 'Proceso completado',
    message: 'La demo genero una notificacion interna correctamente.',
    level: 'info',
    action_url: '/demo/notifications'
});

async function createNotification() {
    await api.post('/v1/demo/notifications', form);
    await notificationStore.loadNotifications();

    toast.add({
        severity: 'success',
        summary: 'Notificacion creada',
        detail: 'La notificacion demo ya aparece en la bandeja interna.',
        life: 2500
    });
}

async function markAsRead(notification) {
    if (notification.read_at) {
        return;
    }

    await notificationStore.markAsRead(notification.uuid);
}

async function markAllAsRead() {
    await notificationStore.markAllAsRead();

    toast.add({
        severity: 'success',
        summary: 'Bandeja actualizada',
        detail: 'Todas las notificaciones quedaron marcadas como leidas.',
        life: 2500
    });
}

function formatDate(value) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('es-BO', {
        dateStyle: 'medium',
        timeStyle: 'short'
    }).format(new Date(value));
}

function resolveSeverity(level) {
    if (level === 'success') {
        return 'success';
    }

    if (level === 'warning') {
        return 'warning';
    }

    if (level === 'danger') {
        return 'danger';
    }

    return 'info';
}
</script>

<template>
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <div class="card flex flex-col gap-3">
                <Tag severity="warning" value="Demo Module / Notifications" class="w-fit" />
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div>
                        <h2 class="m-0">Demo funcional de notificaciones</h2>
                        <p class="m-0 text-color-secondary">
                            Valida notificaciones internas, bandeja del usuario, marcado de lectura y contador de no leidas.
                        </p>
                    </div>
                    <div class="demo-notification-summary">
                        <div>
                            <strong>{{ notificationStore.state.items.length }}</strong>
                            <span>notificaciones</span>
                        </div>
                        <div>
                            <strong>{{ notificationStore.unreadCount }}</strong>
                            <span>sin leer</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="card flex flex-col gap-4">
                <div>
                    <h3 class="m-0 mb-2">Generar notificacion demo</h3>
                    <p class="m-0 text-sm text-color-secondary">
                        Crea una notificacion interna para el usuario actual en la organizacion activa.
                    </p>
                </div>

                <input v-model="form.title" type="text" class="demo-input" placeholder="Titulo" />
                <textarea v-model="form.message" rows="4" class="demo-textarea" placeholder="Mensaje"></textarea>

                <label class="demo-field">
                    <span>Nivel</span>
                    <select v-model="form.level" class="demo-select">
                        <option value="info">Info</option>
                        <option value="success">Success</option>
                        <option value="warning">Warning</option>
                        <option value="danger">Danger</option>
                    </select>
                </label>

                <input v-model="form.action_url" type="text" class="demo-input" placeholder="URL de accion opcional" />

                <button class="demo-primary-button" @click="createNotification">
                    Crear notificacion
                </button>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <div class="card">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-4">
                    <div>
                        <h3 class="m-0">Bandeja interna</h3>
                        <p class="m-0 text-sm text-color-secondary">
                            Esta misma bandeja alimenta la campanita del layout y sirve como base para futuras preferencias y canales.
                        </p>
                    </div>
                    <div class="demo-actions">
                        <button class="demo-secondary-button" :disabled="notificationStore.state.loading" @click="notificationStore.loadNotifications">
                            {{ notificationStore.state.loading ? 'Actualizando...' : 'Actualizar' }}
                        </button>
                        <button class="demo-secondary-button" :disabled="notificationStore.unreadCount === 0" @click="markAllAsRead">
                            Marcar todas como leidas
                        </button>
                    </div>
                </div>

                <div v-if="notificationStore.state.items.length === 0" class="demo-empty-state">
                    Todavia no hay notificaciones. Genera una desde la demo para probar el flujo.
                </div>

                <div v-else class="demo-notification-list">
                    <article v-for="notification in notificationStore.state.items" :key="notification.uuid" class="demo-notification-card" :class="{ unread: !notification.read_at }">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold">{{ notification.title }}</div>
                                <div class="text-sm text-color-secondary">
                                    {{ formatDate(notification.created_at) }}
                                </div>
                            </div>
                            <Tag :severity="resolveSeverity(notification.level)" :value="notification.level" />
                        </div>

                        <p class="text-sm mb-3 mt-3">{{ notification.message }}</p>

                        <div class="demo-card-footer">
                            <span class="text-sm text-color-secondary">
                                {{ notification.read_at ? `Leida ${formatDate(notification.read_at)}` : 'Pendiente de lectura' }}
                            </span>

                            <div class="demo-actions">
                                <router-link v-if="notification.action_url" :to="notification.action_url" class="demo-link">
                                    Abrir accion
                                </router-link>
                                <button v-if="!notification.read_at" class="demo-secondary-button" @click="markAsRead(notification)">
                                    Marcar leida
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.demo-notification-summary {
    display: flex;
    gap: 1rem;
}

.demo-notification-summary > div {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 7rem;
    padding: 0.9rem 1rem;
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    background: linear-gradient(135deg, var(--surface-card), var(--surface-ground));
}

.demo-notification-summary strong {
    font-size: 1.35rem;
}

.demo-notification-summary span {
    font-size: 0.85rem;
    color: var(--text-color-secondary);
}

.demo-input,
.demo-textarea,
.demo-select {
    width: 100%;
    border: 1px solid var(--surface-border);
    border-radius: 0.85rem;
    padding: 0.85rem 1rem;
    background: var(--surface-card);
    color: var(--text-color);
}

.demo-field,
.demo-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.demo-field {
    flex-direction: column;
}

.demo-primary-button,
.demo-secondary-button {
    border: 0;
    border-radius: 999px;
    padding: 0.75rem 1rem;
    font-weight: 600;
    cursor: pointer;
}

.demo-primary-button {
    background: var(--primary-color);
    color: var(--primary-contrast-color);
}

.demo-secondary-button {
    background: var(--surface-200);
    color: var(--text-color);
}

.demo-primary-button:disabled,
.demo-secondary-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.demo-empty-state,
.demo-notification-card {
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    padding: 1rem;
    background: var(--surface-ground);
}

.demo-notification-list {
    display: grid;
    gap: 1rem;
}

.demo-notification-card {
    background: var(--surface-card);
}

.demo-notification-card.unread {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 1px color-mix(in srgb, var(--primary-color) 35%, transparent);
}

.demo-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}

.demo-link {
    color: var(--primary-color);
    font-weight: 600;
    text-decoration: none;
}
</style>
