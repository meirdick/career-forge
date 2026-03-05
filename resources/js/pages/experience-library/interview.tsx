import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { index as careerChatIndex } from '@/routes/career-chat';

/**
 * @deprecated This page is replaced by Career Chat. The server route redirects,
 * but this component exists as a fallback.
 */
export default function Interview() {
    useEffect(() => {
        router.visit(careerChatIndex().url);
    }, []);

    return null;
}
