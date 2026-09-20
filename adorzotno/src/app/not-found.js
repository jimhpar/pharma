import { AlertTriangle, ChevronRight, Home, Search } from "lucide-react";
import Link from "next/link";

export default function NotFound() {
  return (
    <div>
      <main className="flex-1">
        <div className="flex items-center gap-2 text-sm text-gray-600 mb-6">
          <Link href="/">
            <button className="flex items-center gap-1 hover:text-primary cursor-pointer">
              <Home size={16} />
              Home
            </button>
          </Link>
          <ChevronRight size={16} />
          <span className="text-gray-800 font-semibold">Page Not Found</span>
        </div>

        <div className="bg-gradient-to-r from-primary to-blue-500 rounded-lg p-10 md:p-14 mb-8 text-white text-center">
          <div className="mx-auto mb-5 w-16 h-16 rounded-full bg-white/15 flex items-center justify-center">
            <AlertTriangle size={32} />
          </div>
          <h1 className="text-5xl md:text-6xl font-bold mb-3">404</h1>
          <h2 className="text-2xl md:text-3xl font-semibold mb-4">
            Oops! Page Not Found
          </h2>
          <p className="text-teal-100 max-w-2xl mx-auto">
            The page you are looking for may have been moved, renamed, or does
            not exist.
          </p>
        </div>

        <div className="bg-white rounded-lg shadow-md p-6 md:p-8">
          <h3 className="text-xl font-bold text-gray-800 mb-4">
            Try One of These
          </h3>
          <div className="grid sm:grid-cols-3 gap-3">
            <Link
              href="/"
              className="flex items-center justify-center gap-2 bg-primary text-white px-4 py-3 rounded-lg font-semibold hover:bg-secondary transition"
            >
              <Home size={18} />
              Go Home
            </Link>
            <Link
              href="/category"
              className="flex items-center justify-center gap-2 border border-primary text-primary px-4 py-3 rounded-lg font-semibold hover:bg-primary/5 transition"
            >
              <Search size={18} />
              Browse Category
            </Link>
            <Link
              href="/brand"
              className="flex items-center justify-center gap-2 border border-primary text-primary px-4 py-3 rounded-lg font-semibold hover:bg-primary/5 transition"
            >
              Explore Brands
            </Link>
          </div>
        </div>
      </main>
    </div>
  );
}
