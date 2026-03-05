import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path
                fillRule="evenodd"
                clipRule="evenodd"
                d="M12 2L3 7v10l9 5 9-5V7l-9-5zm0 2.18L5 8.09v1.82l7 3.89 7-3.89V8.09L12 4.18zM5 12.27v5.64l6 3.33v-5.64l-6-3.33zm8 3.33v5.64l6-3.33v-5.64l-6 3.33z"
            />
        </svg>
    );
}
