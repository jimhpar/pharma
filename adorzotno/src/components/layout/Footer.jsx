import { Mail, MapPin, Phone } from 'lucide-react'
import Image from 'next/image'
import Link from 'next/link'
import React from 'react'
export default function Footer() {
    return (
        <div>
            <footer className="mt-8 bg-gray-800 text-white">
                <div className="container mx-auto px-4 py-8 pb-24 md:pb-8">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                        <div>
                            <Link href="/"> <Image src="/images/AdorzotnoLogo.png" alt="adorzotno Logo" width={200} height={60} />
                            </Link>
                            <p className="text-gray-300"> Your trusted online pharmacy for quality healthcare products and medicines. </p> </div>
                        <div>
                            <h4 className="font-semibold text-lg mb-4">Quick Links</h4>
                            <ul className="space-y-2 font-semibold text-gray-300">
                                <li><Link href="/about" className="hover:text-white">About Us</Link></li>
                                <li><Link href="/contact" className="hover:text-white">Contact</Link></li>
                                <li><Link href="/brand" className="hover:text-white">Brand</Link></li>
                                <li><Link href="/faq" className="hover:text-white">Faqs</Link></li>
                                <li><Link href="/business-register" className="hover:text-white">Register the Pharmacy</Link></li>
                            </ul>
                        </div>
                        <div>
                            <h4 className="font-semibold text-lg mb-4">Customer Service</h4>
                            <ul className="space-y-2 font-semibold text-gray-300">
                                <li><Link href="#" className="hover:text-white">Shipping Info</Link></li>
                                <li><Link href="#" className="hover:text-white">Returns</Link></li>
                                <li><Link href="#" className="hover:text-white">Privacy Policy</Link></li>
                                <li><Link href="#" className="hover:text-white">Terms & Conditions</Link></li>
                            </ul>
                        </div>
                        <div>
                            <h4 className="font-semibold text-lg mb-4">Contact Us</h4>
                            <ul className="space-y-2 font-semibold text-gray-300">
                                <li className="flex items-center gap-2"> <Phone size={16} /> +1-800-ADORZOTNO </li>
                                <li className="flex items-center gap-2"> <Mail size={16} /> support@adorzotno.health </li>
                                <li className="flex items-center gap-2"> <MapPin size={26} /> H# 18/A (4th Floor), R# Avenue-1, Block# C, Mirpur-2, Dhaka-1216 </li>
                            </ul>
                        </div>
                    </div>

                    {/* Bottom */}
                    <div className="border-t border-slate-400 mt-10 pt-6 text-center text-white">
                        © 2026 <span className="font-medium text-white">Adorzotno Limited</span>. All rights reserved. Design & Developed By <Link className="text-primary font-bold hover:text-secondary" href={'https://techcloudltd.com/'} target="_blank">Tech Cloud Ltd.</Link>
                    </div>
                </div>
            </footer>
        </div>
    )
}
