import { describe, expect, it } from 'vitest';

import { sanitizeNavigableUrl } from './safeUrl';

describe('sanitizeNavigableUrl', () => {
    it('allows relative application paths', () => {
        expect(sanitizeNavigableUrl('/demo/notifications')).toBe('/demo/notifications');
    });

    it('allows safe absolute protocols', () => {
        expect(sanitizeNavigableUrl('https://stackbase.test/docs')).toBe('https://stackbase.test/docs');
        expect(sanitizeNavigableUrl('mailto:soporte@stackbase.test')).toBe('mailto:soporte@stackbase.test');
    });

    it('blocks javascript and invalid protocols', () => {
        expect(sanitizeNavigableUrl('javascript:alert(1)')).toBeNull();
        expect(sanitizeNavigableUrl('data:text/html,<script>alert(1)</script>')).toBeNull();
        expect(sanitizeNavigableUrl('')).toBeNull();
    });
});
