import React from "react";

export default function Newsletter() {
  return (
    <div>
      <div className="mt-12 bg-gradient-to-r from-primary to-blue-500 rounded-lg p-8 text-white text-center">
        <h2 className="text-3xl font-bold mb-3">Subscribe to Our Newsletter</h2>
        <p className="text-teal-100 mb-6">
          Get exclusive deals and health tips delivered to your inbox
        </p>
        <div className="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
          <input
            type="email"
            placeholder="Enter your email"
            className="flex-1 px-4 py-3 border-2 border-white rounded-lg focus:outline-none text-gray-200"
          />
          <button className="bg-white text-primary px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
            Subscribe
          </button>
        </div>
      </div>
    </div>
  );
}
