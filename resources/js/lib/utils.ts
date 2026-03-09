import type { InertiaLinkProps } from '@inertiajs/react';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

/**
 * Get a display-friendly version of a URL.
 * If a label is provided, returns the label. Otherwise extracts the domain.
 */
export function displayUrl(url: string, label?: string | null): string {
    if (label) {
        return label;
    }

    try {
        const host = new URL(url).hostname;
        return host.replace(/^www\./, '');
    } catch {
        return url;
    }
}
