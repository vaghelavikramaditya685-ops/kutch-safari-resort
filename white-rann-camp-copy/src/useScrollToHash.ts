import { useEffect } from "react";

/** Opens the page at the section named in the address (/tents#tariff, /plan-your-visit#faq). */
export default function useScrollToHash() {
  useEffect(() => {
    const id = window.location.hash.slice(1);
    const target = id ? document.getElementById(id) : null;
    if (target) target.scrollIntoView({ behavior: "instant" });
  }, []);
}
