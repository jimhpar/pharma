import {
  Award,
  CheckCircle2,
  ChevronRight,
  Home,
  Shield,
  Target,
  Truck,
  Users,
} from "lucide-react";
import Link from "next/link";
import React from "react";

export const metadata = {
  title: "About Us | Adorzotno Limited",
  description:
    "Secure about us at Adorzotno Limited. Complete your order with safe payment options and fast delivery.",
  keywords: [
    "about us",
    "adorzotno",
    "online pharmacy about us",
    "secure payment",
    "medical products order",
  ],
};

export default function page() {
  return (
    <div>
      {/* About Us Content */}
      <main className="flex-1">
        {/* Breadcrumb */}
        <div className="flex items-center gap-2 text-sm text-gray-600 mb-6">
          <Link href={"/"}>
            <button className="flex items-center gap-1 hover:text-primary cursor-pointer">
              <Home size={16} />
              Home
            </button>
          </Link>
          <ChevronRight size={16} />
          <span className="text-gray-800 font-semibold">About Us</span>
        </div>

        {/* Hero Section */}
        <div className="bg-gradient-to-r from-primary to-blue-500 rounded-lg p-12 mb-8 text-white text-center">
          <h1 className="text-5xl font-bold mb-4">About AdorZotno</h1>
          <p className="text-xl text-teal-100 max-w-3xl mx-auto">
            Your trusted partner in healthcare, delivering quality medicines and
            medical supplies to your doorstep since 2010
          </p>
        </div>

        {/* Our Story */}
        <div className="bg-white rounded-lg shadow-md p-8 mb-8">
          <h2 className="text-3xl font-bold text-gray-800 mb-4">Our Story</h2>
          <div className="text-gray-600 space-y-4">
            <p>
              AdorZotno was founded in 2010 with a simple mission: to make
              healthcare accessible to everyone. What started as a small
              pharmacy has grown into one of the most trusted online healthcare
              platforms, serving millions of customers across the country.
            </p>
            <p>
              We understand that health is wealth, and accessing quality
              medicines should not be complicated. That is why we have built a
              platform that combines convenience, affordability, and reliability
              to bring healthcare right to your fingertips.
            </p>
            <p>
              Today, we partner with licensed pharmacies and certified
              healthcare providers to ensure that every product we deliver meets
              the highest quality standards. Our team of experienced pharmacists
              and healthcare professionals is dedicated to providing you with
              the best service possible.
            </p>
          </div>
        </div>

        {/* Mission, Vision, Values */}
        <div className="grid md:grid-cols-3 gap-6 mb-8">
          <div className="bg-white rounded-lg shadow-md p-6 text-center">
            <div className="bg-teal-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
              <Target className="text-primary" size={32} />
            </div>
            <h3 className="text-xl font-bold text-gray-800 mb-3">
              Our Mission
            </h3>
            <p className="text-gray-600">
              To provide accessible, affordable, and reliable healthcare
              solutions to everyone, everywhere.
            </p>
          </div>
          <div className="bg-white rounded-lg shadow-md p-6 text-center">
            <div className="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
              <Users className="text-blue-600" size={32} />
            </div>
            <h3 className="text-xl font-bold text-gray-800 mb-3">Our Vision</h3>
            <p className="text-gray-600">
              To become the most trusted healthcare platform, transforming the
              way people access medical care.
            </p>
          </div>
          <div className="bg-white rounded-lg shadow-md p-6 text-center">
            <div className="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
              <CheckCircle2 className="text-purple-600" size={32} />
            </div>
            <h3 className="text-xl font-bold text-gray-800 mb-3">Our Values</h3>
            <p className="text-gray-600">
              Quality, integrity, customer-centricity, and innovation drive
              everything we do.
            </p>
          </div>
        </div>

        {/* Why Choose Us */}
        <div className="bg-white rounded-lg shadow-md p-8 mb-8">
          <h2 className="text-3xl font-bold text-gray-800 mb-6">
            Why Choose AdorZotno?
          </h2>
          <div className="grid md:grid-cols-2 gap-6">
            <div className="flex gap-4">
              <div className="bg-teal-100 p-3 rounded-lg h-fit">
                <Shield className="text-primary" size={24} />
              </div>
              <div>
                <h3 className="font-semibold text-gray-800 mb-2">
                  Certified & Licensed
                </h3>
                <p className="text-gray-600 text-sm">
                  All our products are sourced from licensed manufacturers and
                  certified by regulatory authorities.
                </p>
              </div>
            </div>
            <div className="flex gap-4">
              <div className="bg-blue-100 p-3 rounded-lg h-fit">
                <Truck className="text-blue-600" size={24} />
              </div>
              <div>
                <h3 className="font-semibold text-gray-800 mb-2">
                  Fast Delivery
                </h3>
                <p className="text-gray-600 text-sm">
                  We deliver your orders quickly and securely, right to your
                  doorstep.
                </p>
              </div>
            </div>
            <div className="flex gap-4">
              <div className="bg-purple-100 p-3 rounded-lg h-fit">
                <Users className="text-purple-600" size={24} />
              </div>
              <div>
                <h3 className="font-semibold text-gray-800 mb-2">
                  Expert Support
                </h3>
                <p className="text-gray-600 text-sm">
                  Our team of licensed pharmacists is available 24/7 to answer
                  your questions.
                </p>
              </div>
            </div>
            <div className="flex gap-4">
              <div className="bg-orange-100 p-3 rounded-lg h-fit">
                <Award className="text-orange-600" size={24} />
              </div>
              <div>
                <h3 className="font-semibold text-gray-800 mb-2">
                  Quality Assured
                </h3>
                <p className="text-gray-600 text-sm">
                  Every product undergoes strict quality checks before reaching
                  you.
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Statistics */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
          <div className="bg-gradient-to-br from-teal-500 to-teal-600 rounded-lg p-6 text-white text-center">
            <div className="text-4xl font-bold mb-2">500K+</div>
            <div className="text-teal-100">Happy Customers</div>
          </div>
          <div className="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg p-6 text-white text-center">
            <div className="text-4xl font-bold mb-2">10K+</div>
            <div className="text-blue-100">Products</div>
          </div>
          <div className="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg p-6 text-white text-center">
            <div className="text-4xl font-bold mb-2">50+</div>
            <div className="text-purple-100">Cities Covered</div>
          </div>
          <div className="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg p-6 text-white text-center">
            <div className="text-4xl font-bold mb-2">14+</div>
            <div className="text-orange-100">Years of Service</div>
          </div>
        </div>
      </main>
    </div>
  );
}
