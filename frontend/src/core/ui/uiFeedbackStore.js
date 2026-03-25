import { computed, reactive } from 'vue';

const state = reactive({
    httpError: null
});

let clearTimer = null;

function friendlyMessage(status, errorCode, fallback) {
    if (status === 401) {
        return 'Tu sesion ya no es valida. Inicia sesion nuevamente para continuar.';
    }

    if (status === 403) {
        return 'No tienes permisos para realizar esta accion.';
    }

    if (status === 429) {
        return 'Se alcanzo el limite de intentos. Espera un momento antes de reintentar.';
    }

    if (status >= 500 || errorCode === 'internal_error') {
        return 'El sistema registro un incidente tecnico. Puedes compartir el request ID con soporte.';
    }

    return fallback;
}

function reportHttpError(error) {
    const status = error?.response?.status ?? null;

    if (status !== null && status < 500 && ![401, 403].includes(status)) {
        return;
    }

    state.httpError = {
        status,
        message: friendlyMessage(status, error?.response?.data?.meta?.error_code ?? null, error?.response?.data?.mensaje ?? 'Ocurrio un error inesperado al comunicar con la API.'),
        requestId: error?.response?.data?.meta?.request_id ?? error?.response?.headers?.['x-request-id'] ?? null,
        errorCode: error?.response?.data?.meta?.error_code ?? null,
        errorLogId: error?.response?.data?.meta?.error_log_id ?? null,
        at: new Date().toISOString()
    };

    if (clearTimer) {
        clearTimeout(clearTimer);
    }

    clearTimer = setTimeout(() => {
        state.httpError = null;
    }, 6000);
}

function clearHttpError() {
    state.httpError = null;

    if (clearTimer) {
        clearTimeout(clearTimer);
        clearTimer = null;
    }
}

export const uiFeedbackStore = {
    state,
    httpError: computed(() => state.httpError),
    reportHttpError,
    clearHttpError
};
