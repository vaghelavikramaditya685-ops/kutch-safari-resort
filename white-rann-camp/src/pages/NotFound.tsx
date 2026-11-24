import { Link } from "wouter";
import Navbar from "@/components/Navbar";
import Footer from "@/components/Footer";

export default function NotFound() {
  return (
    <div className="min-h-screen">
      <Navbar />
      <section className="py-32 text-center px-6">
        <p className="text-xs font-semibold uppercase tracking-[0.3em] text-rann-600 mb-4">404</p>
        <h1 className="text-5xl font-bold text-rann-900 mb-4">Page not found</h1>
        <p className="text-zinc-600 mb-10">The page you were looking for isn't here.</p>
        <Link href="/" className="bg-rann-700 hover:bg-rann-800 text-white px-8 py-4 uppercase tracking-[0.18em] text-sm font-semibold rounded-sm">Back to the camp</Link>
      </section>
      <Footer />
    </div>
  );
}
