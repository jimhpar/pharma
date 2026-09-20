'use client'
import { ChevronRight, MessageCircle, Phone, X } from 'lucide-react';
import React, { useState } from 'react'

export default function LiveChatWidget({ chatOpen, setChatOpen, chatExpanded }) {
  return (
    <>
      {/* Chat Bubble Button */}
      {!chatOpen && (
        <button
          onClick={() => setChatOpen(true)}
          className="max-md:hidden fixed bottom-6 right-6 bg-primary text-white p-4 rounded-full shadow-2xl hover:bg-primary transition z-30 animate-bounce"
        >
          <MessageCircle size={28} />
        </button>
      )}

      {/* Chat Options Panel */}
      {chatOpen && !chatExpanded && (
        <div className="fixed bottom-6 right-6 bg-white rounded-2xl shadow-2xl z-30 w-80 overflow-hidden animate-slideUp">
          <div className="bg-teal-600 p-4 text-white flex items-center justify-between">
            <div className="flex items-center gap-3">
              <MessageCircle size={24} />
              <div>
                <h3 className="font-bold">Chat with us!</h3>
                <p className="text-xs text-teal-100">We are online</p>
              </div>
            </div>
            <button
              onClick={() => setChatOpen(false)}
              className="p-1 hover:bg-teal-700 rounded transition"
            >
              <X size={20} />
            </button>
          </div>
          <div className="p-6 space-y-4">
            <p className="text-gray-600 text-sm mb-4">How would you like to chat with us?</p>

            {/* WhatsApp Option */}
            <a
              href="https://wa.me/18002633227"
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center gap-3 p-4 bg-green-50 hover:bg-green-100 rounded-lg transition group"
            >
              <div className="bg-green-500 p-3 rounded-full">
                <svg className="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                </svg>
              </div>
              <div className="flex-1">
                <h4 className="font-semibold text-gray-800">WhatsApp</h4>
                <p className="text-xs text-gray-600">Chat on WhatsApp</p>
              </div>
              <ChevronRight className="text-gray-400 group-hover:text-green-600" size={20} />
            </a>

            {/* Messenger Option */}
            <a
              href="https://m.me/medeasy"
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center gap-3 p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition group"
            >
              <div className="bg-blue-600 p-3 rounded-full">
                <svg className="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 0C5.373 0 0 4.975 0 11.111c0 3.497 1.745 6.616 4.472 8.652V24l4.086-2.242c1.09.301 2.246.464 3.442.464 6.627 0 12-4.974 12-11.111C24 4.975 18.627 0 12 0zm1.191 14.963l-3.055-3.26-5.963 3.26L10.732 8l3.131 3.259L19.752 8l-6.561 6.963z" />
                </svg>
              </div>
              <div className="flex-1">
                <h4 className="font-semibold text-gray-800">Messenger</h4>
                <p className="text-xs text-gray-600">Chat on Facebook</p>
              </div>
              <ChevronRight className="text-gray-400 group-hover:text-blue-600" size={20} />
            </a>

            {/* Phone Option */}
            <a
              href="tel:+18002633227"
              className="flex items-center gap-3 p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition group"
            >
              <div className="bg-purple-600 p-3 rounded-full">
                <Phone className="text-white" size={24} />
              </div>
              <div className="flex-1">
                <h4 className="font-semibold text-gray-800">Call Us</h4>
                <p className="text-xs text-gray-600">+1-800-MEDCARE</p>
              </div>
              <ChevronRight className="text-gray-400 group-hover:text-purple-600" size={20} />
            </a>
          </div>
        </div>
      )}

      <style jsx>{`
          @keyframes slideUp {
            from {
              transform: translateY(20px);
              opacity: 0;
            }
            to {
              transform: translateY(0);
              opacity: 1;
            }
          }
          .animate-slideUp {
            animation: slideUp 0.3s ease-out;
          }
        `}</style>
    </>
  );
};