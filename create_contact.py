import os

content = """import { Link } from "wouter";
import { useEffect, useState } from "react";
import { ArrowLeft, MapPin, Phone, Mail, Clock, Send, CheckCircle2, User, PhoneCall, Building } from "lucide-react";
import { toast } from "sonner";

export default function Contact() {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    dates: '',
    message: ''
  });
  const [submitting, setSubmitting] = useState(false);
  const [submitted, setSubmitted] = useState(false);

  useEffect(() => {
    window.scrollTo(0, 0);
  }, []);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    // Simulate network request
    setTimeout(() => {
      setSubmitting(false);
      setSubmitted(true);
      toast.success("Message sent successfully! We'll get back to you shortly.");
      setFormData({ name: '', email: '', phone: '', dates: '', message: '' });
      setTimeout(() => setSubmitted(false), 5000);
    }, 1500);
  };

  return (
    <div className="min-h-screen bg-[#fcfbfa] selection:bg-[var(--terracotta)] selection:text-white">
      {/* Header */}
      <header className="fixed inset-x-0 top-0 z-50 bg-white/95 backdrop-blur-md shadow-sm py-4 border-b border-[#e4d5c7]">
        <div className="container px-6 md:px-10 flex items-center justify-between mx-auto">
          <Link href="/">
            <a className="flex items-center gap-3">
              <img src="/assets/images/logo-mark.png" alt="Kutch Safari Resort" className="h-10 w-10 object-contain" />
              <div className="leading-tight">
                <span className="font-display text-base font-bold tracking-wide text-zinc-900 block">KUTCH SAFARI</span>
                <span className="text-[10px] uppercase tracking-[0.2em] text-zinc-600">Resort &middot; Bhuj</span>
              </div>
            </a>
          </Link>
          <Link href="/">
            <a className="flex items-center gap-2 text-sm font-medium text-zinc-600 hover:text-[var(--terracotta)] transition-colors uppercase tracking-widest">
              <ArrowLeft className="h-4 w-4" />
              Back to Home
            </a>
          </Link>
        </div>
      </header>

      {/* Hero Section */}
      <div className="relative pt-32 pb-16 md:pt-40 md:pb-24 overflow-hidden border-b border-[#e4d5c7]">
        <div className="absolute inset-0 z-0">
          <img src="/assets/images/new/kutch-safari-resort-authentic-stay.jpg" className="w-full h-full object-cover opacity-10" alt="Background pattern" />
          <div className="absolute inset-0 bg-gradient-to-b from-[#fcfbfa] via-transparent to-[#fcfbfa]" />
        </div>
        
        <div className="container relative z-10 px-4 mx-auto text-center max-w-3xl">
          <p className="text-sm font-semibold tracking-[0.2em] uppercase text-[var(--terracotta)] mb-4">We are here for you</p>
          <h1 className="text-5xl md:text-6xl font-display text-zinc-900 font-bold mb-6">Get in Touch</h1>
          <p className="text-lg text-zinc-600 leading-relaxed max-w-2xl mx-auto">
            Whether you are planning a grand family getaway, a romantic retreat, or need help crafting your itinerary for the Rann Utsav, our dedicated team is at your service.
          </p>
        </div>
      </div>

      {/* Main Content */}
      <div className="container mx-auto px-4 py-16 md:py-24">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-20 max-w-7xl mx-auto">
          
          {/* Left Column: Contact Details */}
          <div className="lg:col-span-5 flex flex-col gap-10 lg:pr-8">
            <div>
              <h2 className="text-3xl font-display font-semibold text-zinc-900 mb-6">Contact Details</h2>
              <p className="text-zinc-600 leading-relaxed mb-8">
                We'd love to hear from you. You can reach out directly via phone or WhatsApp for immediate reservations, or send us an email for detailed inquiries.
              </p>
            </div>

            <div className="space-y-8">
              <div className="flex gap-4 items-start group">
                <div className="w-12 h-12 bg-white rounded-full flex items-center justify-center border border-[#e4d5c7] shadow-sm text-[var(--terracotta)] group-hover:bg-[var(--terracotta)] group-hover:text-white transition-colors duration-300 shrink-0">
                  <Phone className="w-5 h-5" />
                </div>
                <div>
                  <p className="text-sm uppercase tracking-widest text-zinc-500 font-medium mb-1">Reservations & WhatsApp</p>
                  <a href="https://wa.me/919925238599" className="text-lg md:text-xl font-medium text-zinc-900 hover:text-[var(--terracotta)] transition-colors block">+91 9925238599</a>
                  <a href="tel:+919727783354" className="text-lg md:text-xl font-medium text-zinc-900 hover:text-[var(--terracotta)] transition-colors block mt-1">+91 9727783354</a>
                </div>
              </div>

              <div className="flex gap-4 items-start group">
                <div className="w-12 h-12 bg-white rounded-full flex items-center justify-center border border-[#e4d5c7] shadow-sm text-[var(--terracotta)] group-hover:bg-[var(--terracotta)] group-hover:text-white transition-colors duration-300 shrink-0">
                  <Mail className="w-5 h-5" />
                </div>
                <div>
                  <p className="text-sm uppercase tracking-widest text-zinc-500 font-medium mb-1">Email Address</p>
                  <a href="mailto:kutchsafaribhuj@yahoo.com" className="text-lg font-medium text-zinc-900 hover:text-[var(--terracotta)] transition-colors break-all">kutchsafaribhuj@yahoo.com</a>
                </div>
              </div>

              <div className="flex gap-4 items-start group">
                <div className="w-12 h-12 bg-white rounded-full flex items-center justify-center border border-[#e4d5c7] shadow-sm text-[var(--terracotta)] group-hover:bg-[var(--terracotta)] group-hover:text-white transition-colors duration-300 shrink-0">
                  <MapPin className="w-5 h-5" />
                </div>
                <div>
                  <p className="text-sm uppercase tracking-widest text-zinc-500 font-medium mb-1">Resort Address</p>
                  <p className="text-base text-zinc-800 leading-relaxed font-medium">
                    Kutch Safari Resort<br/>
                    Near Rudramata Dam, Khavda Road,<br/>
                    Bhuj, Kutch - 370001, Gujarat, India
                  </p>
                </div>
              </div>
            </div>
            
            <div className="mt-8 p-6 bg-white border border-[#e4d5c7] rounded-sm shadow-sm relative overflow-hidden">
              <div className="absolute top-0 right-0 w-32 h-32 bg-[var(--terracotta)]/5 rounded-full -translate-y-16 translate-x-16 blur-2xl pointer-events-none" />
              <div className="flex items-center gap-3 mb-3">
                <Clock className="w-5 h-5 text-[var(--terracotta)]" />
                <h3 className="font-semibold text-zinc-900 text-lg">Operating Hours</h3>
              </div>
              <p className="text-zinc-600 text-sm leading-relaxed">
                Reception is open 24/7 for our in-house guests.<br />
                <strong>Check-in:</strong> 12:00 PM | <strong>Check-out:</strong> 10:00 AM
              </p>
            </div>
          </div>

          {/* Right Column: Contact Form */}
          <div className="lg:col-span-7">
            <div className="bg-white border border-[#e4d5c7] p-8 md:p-12 shadow-sm rounded-sm relative">
              
              <div className="mb-8 border-b border-[#e4d5c7] pb-6">
                <h3 className="text-2xl font-display font-semibold text-zinc-900 mb-2">Send us a Message</h3>
                <p className="text-zinc-500 text-sm">Fill out the form below and we will get back to you as soon as possible.</p>
              </div>

              {submitted ? (
                <div className="py-16 flex flex-col items-center justify-center text-center animate-in fade-in duration-500">
                  <div className="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center text-green-600 mb-6 border border-green-100 shadow-sm">
                    <CheckCircle2 className="w-10 h-10" />
                  </div>
                  <h4 className="text-2xl font-display font-semibold text-zinc-900 mb-3">Message Sent!</h4>
                  <p className="text-zinc-600 max-w-sm">Thank you for reaching out. A member of our team will review your inquiry and contact you shortly.</p>
                  <button 
                    onClick={() => setSubmitted(false)}
                    className="mt-8 text-sm uppercase tracking-widest font-medium text-[var(--terracotta)] hover:text-black transition-colors"
                  >
                    Send another message
                  </button>
                </div>
              ) : (
                <form onSubmit={handleSubmit} className="space-y-6">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="space-y-2">
                      <label className="text-xs uppercase tracking-wider font-semibold text-zinc-600">Full Name</label>
                      <div className="relative">
                        <div className="absolute left-4 top-1/2 -translate-y-1/2 text-zinc-400"><User className="w-4 h-4" /></div>
                        <input required type="text" name="name" value={formData.name} onChange={handleChange} className="w-full pl-11 pr-4 py-3 bg-[#fcfbfa] border border-[#e4d5c7] focus:outline-none focus:border-[var(--terracotta)] focus:ring-1 focus:ring-[var(--terracotta)] transition-all rounded-sm text-sm" placeholder="John Doe" />
                      </div>
                    </div>
                    
                    <div className="space-y-2">
                      <label className="text-xs uppercase tracking-wider font-semibold text-zinc-600">Email Address</label>
                      <div className="relative">
                        <div className="absolute left-4 top-1/2 -translate-y-1/2 text-zinc-400"><Mail className="w-4 h-4" /></div>
                        <input required type="email" name="email" value={formData.email} onChange={handleChange} className="w-full pl-11 pr-4 py-3 bg-[#fcfbfa] border border-[#e4d5c7] focus:outline-none focus:border-[var(--terracotta)] focus:ring-1 focus:ring-[var(--terracotta)] transition-all rounded-sm text-sm" placeholder="john@example.com" />
                      </div>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="space-y-2">
                      <label className="text-xs uppercase tracking-wider font-semibold text-zinc-600">Phone Number</label>
                      <div className="relative">
                        <div className="absolute left-4 top-1/2 -translate-y-1/2 text-zinc-400"><PhoneCall className="w-4 h-4" /></div>
                        <input required type="tel" name="phone" value={formData.phone} onChange={handleChange} className="w-full pl-11 pr-4 py-3 bg-[#fcfbfa] border border-[#e4d5c7] focus:outline-none focus:border-[var(--terracotta)] focus:ring-1 focus:ring-[var(--terracotta)] transition-all rounded-sm text-sm" placeholder="+91 98765 43210" />
                      </div>
                    </div>
                    
                    <div className="space-y-2">
                      <label className="text-xs uppercase tracking-wider font-semibold text-zinc-600">Travel Dates (Optional)</label>
                      <div className="relative">
                        <div className="absolute left-4 top-1/2 -translate-y-1/2 text-zinc-400"><Clock className="w-4 h-4" /></div>
                        <input type="text" name="dates" value={formData.dates} onChange={handleChange} className="w-full pl-11 pr-4 py-3 bg-[#fcfbfa] border border-[#e4d5c7] focus:outline-none focus:border-[var(--terracotta)] focus:ring-1 focus:ring-[var(--terracotta)] transition-all rounded-sm text-sm" placeholder="e.g., Oct 15 - Oct 18" />
                      </div>
                    </div>
                  </div>

                  <div className="space-y-2">
                    <label className="text-xs uppercase tracking-wider font-semibold text-zinc-600">Your Message</label>
                    <textarea required name="message" value={formData.message} onChange={handleChange} rows={5} className="w-full p-4 bg-[#fcfbfa] border border-[#e4d5c7] focus:outline-none focus:border-[var(--terracotta)] focus:ring-1 focus:ring-[var(--terracotta)] transition-all rounded-sm text-sm resize-none" placeholder="Tell us about your requirements..."></textarea>
                  </div>

                  <button 
                    disabled={submitting} 
                    type="submit" 
                    className="w-full flex items-center justify-center gap-2 bg-[var(--terracotta)] text-white py-4 uppercase tracking-[0.15em] text-sm font-semibold hover:bg-[#b04838] transition-colors shadow-md shadow-orange-900/10 disabled:opacity-70 disabled:cursor-not-allowed rounded-sm"
                  >
                    {submitting ? 'Sending Message...' : (
                      <>
                        Submit Inquiry
                        <Send className="w-4 h-4 ml-2" />
                      </>
                    )}
                  </button>
                </form>
              )}
            </div>
          </div>
        </div>
      </div>
      
      {/* Footer minimal */}
      <footer className="bg-zinc-900 text-white/80 py-12 text-center border-t border-zinc-800">
        <p className="text-xs uppercase tracking-widest">&copy; 2026 Kutch Safari Resort. All rights reserved.</p>
      </footer>
    </div>
  );
}
"""

with open("client/src/pages/Contact.tsx", "w", encoding="utf-8") as f:
    f.write(content)
print("Created Contact.tsx")
