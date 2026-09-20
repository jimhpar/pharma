import {
  Building2,
  ChevronRight,
  Home,
  Mail,
  MapPin,
  Phone,
  Send,
} from "lucide-react";
import Link from "next/link";
import React from "react";

export const metadata = {
  title: "Contact | Adorzotno Limited",
  description:
    "Secure contact at Adorzotno Limited. Complete your order with safe payment options and fast delivery.",
  keywords: [
    "contact",
    "adorzotno",
    "online pharmacy contact",
    "secure payment",
    "medical products order",
  ],
};

export default function page() {
  return (
    <div>
      {/* Contact Content */}
      <main className="flex-1">
        {/* Breadcrumb */}
        <div className="flex items-center gap-2 text-sm text-gray-600 mb-6">
          <Link href={"/"}>
            <button className="flex items-center gap-1 hover:text-primary">
              <Home size={16} />
              Home
            </button>
          </Link>
          <ChevronRight size={16} />
          <span className="text-gray-800 font-semibold">Contact Us</span>
        </div>

        {/* Hero Section */}
        <div className="bg-gradient-to-r from-primary to-blue-500 rounded-lg p-12 mb-8 text-white text-center">
          <h1 className="text-5xl font-bold mb-4">Get in Touch</h1>
          <p className="text-xl text-teal-100 max-w-3xl mx-auto">
            Have questions? We are here to help. Reach out to us anytime!
          </p>
        </div>

        <div className="grid md:grid-cols-3 gap-6 mb-8">
          {/* Contact Info Cards */}
          <div className="bg-white rounded-lg shadow-md p-6 text-center">
            <div className="bg-teal-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
              <Phone className="text-primary" size={28} />
            </div>
            <h3 className="text-lg font-bold text-gray-800 mb-2">Call Us</h3>
            <p className="text-gray-600 mb-2">Mon-Fri: 9AM - 6PM</p>
            <Link
              href="tel:+18002633227"
              className="text-primary font-semibold hover:text-secondary"
            >
              +1-800-ADORZOTNO
            </Link>
          </div>

          <div className="bg-white rounded-lg shadow-md p-6 text-center">
            <div className="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
              <Mail className="text-blue-600" size={28} />
            </div>
            <h3 className="text-lg font-bold text-gray-800 mb-2">Email Us</h3>
            <p className="text-gray-600 mb-2">
              We will respond within 24 hours
            </p>
            <Link
              href="mailto:support@adorzotno.health"
              className="text-primary font-semibold hover:text-secondary"
            >
              support@adorzotno.health
            </Link>
          </div>

          <div className="bg-white rounded-lg shadow-md p-6 text-center">
            <div className="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
              <Building2 className="text-secondary" size={28} />
            </div>
            <h3 className="text-lg font-bold text-gray-800 mb-2">Visit Us</h3>
            <p className="text-gray-600 mb-2">Our Main Office</p>
            <p className="text-primary font-semibold">
              123 Health Street
              <br />
              Medical City, MC 12345
            </p>
          </div>
        </div>

        {/* Contact Form */}
        <div className="bg-white rounded-lg shadow-md p-8 mb-8">
          <h2 className="text-3xl font-bold text-gray-800 mb-6">
            Send Us a Message
          </h2>
          <form className="space-y-6">
            <div className="grid md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">
                  First Name *
                </label>
                <input
                  type="text"
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                  placeholder="John"
                />
              </div>
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">
                  Last Name *
                </label>
                <input
                  type="text"
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                  placeholder="Doe"
                />
              </div>
            </div>

            <div className="grid md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">
                  Email *
                </label>
                <input
                  type="email"
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                  placeholder="john@example.com"
                />
              </div>
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">
                  Phone
                </label>
                <input
                  type="tel"
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                  placeholder="+1 (555) 000-0000"
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-2">
                Subject *
              </label>
              <input
                type="text"
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="How can we help you?"
              />
            </div>

            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-2">
                Message *
              </label>
              <textarea
                rows="5"
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="Tell us more about your inquiry..."
              ></textarea>
            </div>

            <button
              type="submit"
              className="w-full md:w-auto bg-primary text-white px-8 py-3 rounded-lg hover:bg-secondary transition font-semibold flex items-center justify-center gap-2"
            >
              <Send size={20} />
              Send Message
            </button>
          </form>
        </div>

        {/* Map Section */}
        <div className="bg-white rounded-lg shadow-md p-8">
          <h2 className="text-3xl font-bold text-gray-800 mb-6">
            Find Us Here
          </h2>
          <div className="rounded-lg overflow-hidden h-96 shadow-lg">
            <iframe
              src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3650.8289847623843!2d90.36446407592128!3d23.79771358666969!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755c12f97592a55%3A0x5b764a4d6a0b7cc8!2sMirpur%202%2C%20Dhaka!5e0!3m2!1sen!2sbd!4v1234567890123!5m2!1sen!2sbd"
              width="100%"
              height="100%"
              style={{ border: 0 }}
              allowFullScreen=""
              loading="lazy"
              referrerPolicy="no-referrer-when-downgrade"
              title="Adorzotno Location"
            ></iframe>
          </div>
          <div className="mt-6 flex items-start gap-3 text-gray-700">
            <MapPin size={24} className="text-primary flex-shrink-0 mt-1" />
            <div>
              <p className="font-semibold text-lg">Our Address</p>
              <p className="text-gray-600">
                H# 18/A (4th Floor), R# Avenue-1, Block# C, Mirpur-2, Dhaka-1216
              </p>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}
