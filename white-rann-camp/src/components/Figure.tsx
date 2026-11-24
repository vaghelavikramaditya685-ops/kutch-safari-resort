import type { Photo } from "@/data";

/** A photo, with its author and licence shown on the picture when it needs a credit. */
export default function Figure({ photo, className = "", imgClassName = "", eager = false }: {
  photo: Photo; className?: string; imgClassName?: string; eager?: boolean;
}) {
  return (
    <figure className={`relative overflow-hidden ${className}`}>
      <img src={photo.src} alt={photo.alt} loading={eager ? "eager" : "lazy"} decoding="async"
        className={`w-full h-full object-cover ${imgClassName}`} />
      {photo.credit && <figcaption><CreditTag photo={photo} /></figcaption>}
    </figure>
  );
}

export function CreditTag({ photo }: { photo: Photo }) {
  const c = photo.credit!;
  return (
    <span className="absolute z-30 bottom-2 right-2 max-w-[90%] bg-black/45 text-white/90 text-[10px] leading-tight px-2 py-1 rounded-sm backdrop-blur-sm">
      Photo: <a href={c.page} target="_blank" rel="noreferrer" className="underline underline-offset-2">{c.author}</a>,{" "}
      <a href={c.licenceUrl} target="_blank" rel="noreferrer" className="underline underline-offset-2">{c.licence}</a>
    </span>
  );
}

/** Page heading band used at the top of inner pages. */
export function PageHeader({ eyebrow, title, intro }: { eyebrow?: string; title: string; intro?: string }) {
  return (
    <div className="bg-rann-50 border-b border-rann-200 pt-24 pb-16">
      <div className="container mx-auto px-6 text-center max-w-3xl">
        {eyebrow && <p className="text-sm font-semibold tracking-[0.2em] uppercase text-rann-700 mb-4">{eyebrow}</p>}
        <h1 className="text-4xl md:text-5xl font-display font-bold text-zinc-900 mb-6">{title}</h1>
        {intro && <p className="text-lg text-zinc-600 font-light">{intro}</p>}
      </div>
    </div>
  );
}

/** Small section heading: blue eyebrow line above a display heading. */
export function SectionTitle({ eyebrow, title, center = false }: { eyebrow: string; title: string; center?: boolean }) {
  return (
    <div className={center ? "text-center" : ""}>
      <p className="text-sm font-semibold tracking-[0.2em] uppercase text-rann-700 mb-4">{eyebrow}</p>
      <h2 className="text-4xl md:text-5xl font-display font-bold text-zinc-900 mb-6">{title}</h2>
    </div>
  );
}
