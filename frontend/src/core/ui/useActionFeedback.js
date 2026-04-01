import { useToast } from 'primevue/usetoast';

function messageFromError(error, fallback) {
    return error?.response?.data?.mensaje ?? error?.message ?? fallback;
}

export function useActionFeedback() {
    const toast = useToast();

    function showSuccess(summary, detail, life = 3000) {
        toast.add({
            severity: 'success',
            summary,
            detail,
            life
        });
    }

    function showInfo(summary, detail, life = 2600) {
        toast.add({
            severity: 'info',
            summary,
            detail,
            life
        });
    }

    function showWarn(summary, detail, life = 3200) {
        toast.add({
            severity: 'warn',
            summary,
            detail,
            life
        });
    }

    function showError(summary, error, fallback = 'Intenta nuevamente.', life = 4200) {
        toast.add({
            severity: 'error',
            summary,
            detail: messageFromError(error, fallback),
            life
        });
    }

    async function run(task, options = {}) {
        const { pending, success, error } = options;

        if (pending) {
            showInfo(pending.summary, pending.detail, pending.life);
        }

        try {
            const result = await task();

            if (success) {
                showSuccess(success.summary, success.detail, success.life);
            }

            return result;
        } catch (failure) {
            if (error !== false) {
                showError(error?.summary ?? 'No se pudo completar la accion', failure, error?.fallback, error?.life);
            }

            throw failure;
        }
    }

    return {
        messageFromError,
        showError,
        showInfo,
        showSuccess,
        showWarn,
        run
    };
}
