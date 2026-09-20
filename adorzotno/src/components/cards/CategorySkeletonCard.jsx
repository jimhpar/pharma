"use client"

import React from 'react'

export default function CategorySkeletonCard() {
    return (
        <div className="relative rounded-2xl overflow-hidden h-40 bg-gray-200 animate-pulse">
            {/* Simulated gradient shimmer at bottom */}
            <div className="absolute inset-0 bg-gradient-to-t from-gray-300 to-transparent" />
            {/* Emoji placeholder */}
            <div className="absolute inset-0 flex flex-col items-center justify-end pb-4 gap-2">
                <div className="w-8 h-8 bg-gray-300 rounded-full" />
                <div className="w-20 h-3 bg-gray-300 rounded-full" />
            </div>
        </div>
    )
}
