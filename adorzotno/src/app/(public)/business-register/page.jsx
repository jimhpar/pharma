import B2bRegisterForm from '@/components/modules/auth/B2bRegisterForm'
import { ChevronRight, FileCheck2, Home, LayoutGrid, Store } from 'lucide-react'
import Link from 'next/link'
import React from 'react'

export default function BusinessRegisterPage() {
    return (
        <div>
            <div className="mb-5 overflow-hidden rounded-xl bg-gradient-to-br from-white via-slate-50 to-primary/5 px-4 py-3 md:px-6 md:py-3 border border-slate-100">
                <div className="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                    <Link
                        href="/"
                        className="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 font-medium text-slate-600 transition-colors hover:text-primary"
                    >
                        <Home size={16} />
                        Home
                    </Link>
                    <ChevronRight size={16} className="text-slate-300" />
                    <span className="inline-flex items-center gap-2 font-semibold text-primary">
                        {/* <LayoutGrid size={16} /> */}
                        B2B Register
                    </span>
                </div>
            </div>

            <div className="my-4 lg:my-8 grid md:grid-cols-2 gap-4 lg:gap-8">

                <div>
                    <div className="mx-auto">

                        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                            <div className="bg-gradient-to-r from-primary/10 via-sky-50 to-secondary/10 px-6 py-8">
                                <span className="inline-flex rounded-full border border-primary/15 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-primary">
                                    Grow With Adorzotno
                                </span>
                                <h2 className="mt-4 text-3xl font-bold leading-tight text-slate-900">
                                    Register your business in a few simple steps
                                </h2>
                                <p className="mt-3 max-w-md text-sm leading-7 text-slate-600">
                                    Join our partner network and submit your verified business
                                    information to start selling or collaborating faster.
                                </p>
                            </div>

                            <div className="space-y-5 px-6 py-6">
                                <div className="grid gap-3">
                                    <div className="flex items-start gap-4 rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
                                        <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                            <Store size={20} />
                                        </span>
                                        <div>
                                            <p className="text-sm font-bold text-slate-900">
                                                For pharmacies and licensed businesses
                                            </p>
                                            <p className="mt-1 text-sm leading-6 text-slate-500">
                                                We support pharmacy registrations as well as other
                                                approved business types.
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex items-start gap-4 rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
                                        <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                            <FileCheck2 size={20} />
                                        </span>
                                        <div>
                                            <p className="text-sm font-bold text-slate-900">
                                                Upload your business license
                                            </p>
                                            <p className="mt-1 text-sm leading-6 text-slate-500">
                                                Share the correct drug or trade license document for
                                                verification.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>

                <div>
                    <B2bRegisterForm />
                </div>

            </div>

        </div>
    )
}
