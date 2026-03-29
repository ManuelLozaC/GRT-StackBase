import { afterEach, beforeEach, vi } from 'vitest';

beforeEach(() => {
    localStorage.clear();
    sessionStorage.clear();
    document.documentElement.className = '';
});

afterEach(() => {
    vi.clearAllMocks();
});
