import { useState } from "react";
import { Mail, MessageCircle, RotateCcw } from "lucide-react";

/**
 * Stay enquiry. The website is static (no server stores enquiries), so the form
 * hands the enquiry to the reservations desk the way they already work: it opens
 * WhatsApp with everything filled in, with email as the fallback. It never says
 * "sent" on its own: only WhatsApp or the guest's mail app sends it.
 */

const WHATSAPP = "919925238599";
const EMAIL = "kutchsafaribhuj@yahoo.com";

export const ROOM_CHOICES = ["No preference", "Kutchi AC Cottage", "Deluxe AC Cottage", "White Rann Camp (Swiss tent)"];

const field = "w-full p-4 bg-[#f8f5e2] border border-[#e4d5c7] rounded-sm text-sm";
const label = "block text-xs uppercase tracking-widest text-zinc-500 font-medium mb-2";

function today(): string {
  const d = new Date();
  return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
}

function prettyDate(iso: string): string {
  const [y, m, d] = iso.split("-").map(Number);
  return new Date(y, m - 1, d).toLocaleDateString("en-IN", { day: "numeric", month: "short", year: "numeric" });
}

export default function EnquiryForm({ room: initialRoom }: { room?: string }) {
  const [form, setForm] = useState({
    name: "",
    phone: "",
    email: "",
    arrival: "",
    departure: "",
    adults: "2",
    children: "0",
    room: ROOM_CHOICES.includes(initialRoom ?? "") ? initialRoom! : ROOM_CHOICES[0],
    message: "",
  });
  const [error, setError] = useState("");
  const [handedOff, setHandedOff] = useState<{ text: string } | null>(null);

  const set = (k: keyof typeof form) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) =>
    setForm({ ...form, [k]: e.target.value });

  function enquiryText(): string {
    const lines = ["Hello Kutch Safari Resort, I'd like to enquire about a stay.", "", `Name: ${form.name.trim()}`, `Phone: ${form.phone.trim()}`];
    if (form.email.trim()) lines.push(`Email: ${form.email.trim()}`);
    if (form.arrival) lines.push(`Dates: ${prettyDate(form.arrival)}${form.departure ? ` → ${prettyDate(form.departure)}` : ""}`);
    lines.push(`Guests: ${form.adults} adult${form.adults === "1" ? "" : "s"}${form.children !== "0" ? `, ${form.children} child${form.children === "1" ? "" : "ren"}` : ""}`);
    if (form.room !== ROOM_CHOICES[0]) lines.push(`Room: ${form.room}`);
    if (form.message.trim()) lines.push("", form.message.trim());
    return lines.join("\n");
  }

  const whatsappHref = (text: string) => `https://wa.me/${WHATSAPP}?text=${encodeURIComponent(text)}`;
  const mailHref = (text: string) =>
    `mailto:${EMAIL}?subject=${encodeURIComponent(`Stay enquiry — ${form.name.trim() || "website"}`)}&body=${encodeURIComponent(text)}`;

  function validate(): string {
    if (!form.name.trim()) return "Please enter your name.";
    if (form.phone.replace(/\D/g, "").length < 7) return "Please enter a phone number we can reach you on.";
    if (form.departure && !form.arrival) return "Please choose your arrival date too.";
    if (form.arrival && form.departure && form.departure <= form.arrival) return "Departure must be after arrival.";
    return "";
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    const problem = validate();
    setError(problem);
    if (problem) return;
    const text = enquiryText();
    window.open(whatsappHref(text), "_blank", "noopener");
    setHandedOff({ text });
  }

  if (handedOff) {
    return (
      <div className="py-6 text-center" role="status">
        <MessageCircle className="w-12 h-12 text-[var(--terracotta)] mx-auto mb-4" />
        <h3 className="text-2xl font-display font-semibold mb-3">Almost done: press Send in WhatsApp</h3>
        <p className="text-zinc-600 mb-8 max-w-md mx-auto">
          Your enquiry is written out in WhatsApp, ready for our reservations desk. It reaches us once you press Send there.
          No WhatsApp? Email it instead.
        </p>
        <div className="flex flex-col sm:flex-row gap-3 justify-center">
          <a href={whatsappHref(handedOff.text)} target="_blank" rel="noopener noreferrer" className="inline-flex items-center justify-center gap-2 bg-[var(--terracotta)] text-white px-6 py-3 uppercase tracking-widest text-sm font-semibold rounded-sm">
            <MessageCircle className="w-4 h-4" /> Open WhatsApp again
          </a>
          <a href={mailHref(handedOff.text)} className="inline-flex items-center justify-center gap-2 border border-[var(--terracotta)] text-[var(--terracotta)] px-6 py-3 uppercase tracking-widest text-sm font-semibold rounded-sm">
            <Mail className="w-4 h-4" /> Email it instead
          </a>
        </div>
        <button type="button" onClick={() => setHandedOff(null)} className="mt-6 inline-flex items-center gap-2 text-sm text-zinc-500 hover:text-zinc-900">
          <RotateCcw className="w-4 h-4" /> Change my enquiry
        </button>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit} noValidate className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label htmlFor="enq-name" className={label}>Full name *</label>
          <input id="enq-name" name="name" autoComplete="name" type="text" className={field} value={form.name} onChange={set("name")} />
        </div>
        <div>
          <label htmlFor="enq-phone" className={label}>Phone / WhatsApp *</label>
          <input id="enq-phone" name="phone" autoComplete="tel" type="tel" inputMode="tel" placeholder="+91 98250 12345" className={field} value={form.phone} onChange={set("phone")} />
        </div>
      </div>
      <div>
        <label htmlFor="enq-email" className={label}>Email (optional)</label>
        <input id="enq-email" name="email" autoComplete="email" type="email" className={field} value={form.email} onChange={set("email")} />
      </div>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label htmlFor="enq-arrival" className={label}>Arrival</label>
          <input id="enq-arrival" name="arrival" type="date" min={today()} className={field} value={form.arrival} onChange={set("arrival")} />
        </div>
        <div>
          <label htmlFor="enq-departure" className={label}>Departure</label>
          <input id="enq-departure" name="departure" type="date" min={form.arrival || today()} className={field} value={form.departure} onChange={set("departure")} />
        </div>
      </div>
      <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
        <div>
          <label htmlFor="enq-adults" className={label}>Adults</label>
          <select id="enq-adults" name="adults" className={field} value={form.adults} onChange={set("adults")}>
            {Array.from({ length: 20 }, (_, i) => String(i + 1)).map((n) => <option key={n}>{n}</option>)}
          </select>
        </div>
        <div>
          <label htmlFor="enq-children" className={label}>Children</label>
          <select id="enq-children" name="children" className={field} value={form.children} onChange={set("children")}>
            {Array.from({ length: 11 }, (_, i) => String(i)).map((n) => <option key={n}>{n}</option>)}
          </select>
        </div>
        <div className="col-span-2">
          <label htmlFor="enq-room" className={label}>Room</label>
          <select id="enq-room" name="room" className={field} value={form.room} onChange={set("room")}>
            {ROOM_CHOICES.map((r) => <option key={r}>{r}</option>)}
          </select>
        </div>
      </div>
      <div>
        <label htmlFor="enq-message" className={label}>Anything else? (optional)</label>
        <textarea id="enq-message" name="message" rows={4} placeholder="Questions, special occasions, travel plans…" className={`${field} resize-none`} value={form.message} onChange={set("message")} />
      </div>
      {error && <p className="text-sm text-red-700" role="alert">{error}</p>}
      <button type="submit" className="w-full inline-flex items-center justify-center gap-2 bg-[var(--terracotta)] text-white py-4 uppercase tracking-[0.15em] text-sm font-semibold rounded-sm hover:bg-[#b04838] transition-colors">
        <MessageCircle className="w-4 h-4" /> Send Enquiry on WhatsApp
      </button>
      <p className="text-center text-sm text-zinc-500">
        Prefer email? <a href={mailHref(enquiryText())} onClick={(e) => { const p = validate(); if (p) { e.preventDefault(); setError(p); } }} className="text-[var(--terracotta)] underline underline-offset-2">Send it by email</a>.
      </p>
    </form>
  );
}
