const ALLOWED_PROTOCOLS = new Set(['http:', 'https:', 'mailto:', 'tel:']);

export function sanitizeNavigableUrl(rawUrl) {
    if (typeof rawUrl !== 'string') {
        return null;
    }

    const trimmedUrl = rawUrl.trim();

    if (!trimmedUrl) {
        return null;
    }

    if (trimmedUrl.startsWith('/')) {
        return trimmedUrl;
    }

    try {
        const parsed = new URL(trimmedUrl, window.location.origin);

        if (!ALLOWED_PROTOCOLS.has(parsed.protocol)) {
            return null;
        }

        return parsed.href;
    } catch {
        return null;
    }
}
