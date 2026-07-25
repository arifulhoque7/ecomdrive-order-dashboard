import type { SVGAttributes } from 'react';

/**
 * EcomDrive mark — an isometric parcel. The three faces are shaded from the
 * same currentColor so the logo inherits whatever it sits on, and the seam
 * across the lid reads as tape rather than decoration.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 32 32"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path
                d="M16 2.5 29 9.6 16 16.7 3 9.6 16 2.5Z"
                fill="currentColor"
            />
            <path
                d="M3 11.4 15 17.9v11.6L3 23V11.4Z"
                fill="currentColor"
                fillOpacity="0.55"
            />
            <path
                d="M29 11.4 17 17.9v11.6l12-6.5V11.4Z"
                fill="currentColor"
                fillOpacity="0.8"
            />
            <path
                d="M9.5 6 22.5 13.1l-2.4 1.3L7.1 7.3 9.5 6Z"
                fill="currentColor"
                fillOpacity="0.35"
            />
        </svg>
    );
}
