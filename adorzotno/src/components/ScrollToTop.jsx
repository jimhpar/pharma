'use client';

import { ArrowUp } from 'lucide-react';
import { useState, useEffect } from 'react';

export default function ScrollToTop() {
    const [isVisible, setIsVisible] = useState(false);

    useEffect(() => {
        const toggleVisibility = () => {
            window.scrollY > 150 ? setIsVisible(true) : setIsVisible(false);
        };
        window.addEventListener('scroll', toggleVisibility);
        return () => window.removeEventListener('scroll', toggleVisibility);
    }, []);

    const scrollToTop = () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    return (
        <button
            onClick={scrollToTop}
            className={`fixed right-6 z-30 bg-primary text-white p-3 md:p-4 rounded-full shadow-2xl transition-all duration-300 hover:bg-primary-dark flex items-center justify-center ${isVisible
                ? 'bottom-28 opacity-100 pointer-events-auto'
                : 'bottom-20 opacity-0 pointer-events-none'
                }`}
        >
            <ArrowUp size={26} />
        </button>
    );
}