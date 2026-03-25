import { computed, reactive } from 'vue';

const state = reactive({
    httpError: null
});

let clearTimer = null;

function reportHttpError(error) {
    const status = error?.response?.status ?? null;

    if (status !== null && status < 500 && ![401, 403].includes(status)) {
        return;
    }

    state.httpError = {
        status,
        message: error?.response?.data?.mensaje ?? 'Ocurrio un error inesperado al comunicar con la API.',
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
