import { useCallback, useEffect, useState } from "react";

/**
 * Tracks whether a horizontally-scrollable container currently has more
 * content to the right that isn't visible yet — i.e. it's actually
 * overflowing and hasn't been scrolled all the way to the end. Meant to
 * drive a CSS mask-image fade hinting "there's more, keep scrolling";
 * returns false (no fade) once there's nothing further to reveal.
 *
 * Also measures the table's <thead> height and exposes it as the
 * --table-header-height CSS custom property on the wrapper, so the fade's
 * mask (see global-styles/tables.scss) can carve out the header and leave
 * it fully opaque regardless of scroll position — header height isn't a
 * constant (depends on font-size/zoom/wrapping), so this has to be measured
 * rather than hardcoded.
 *
 * Returns a callback ref rather than accepting one, so the effect re-runs
 * when the scrollable element actually mounts — several callers render
 * this element behind a loading conditional (skeleton -> real table), and
 * a plain RefObject wouldn't re-trigger the effect once `.current` changes
 * from null to the real node after that first render.
 */
export function useScrollFade(): [(node: HTMLElement | null) => void, boolean] {
  const [wrapper, setWrapper] = useState<HTMLElement | null>(null);
  const [showFade, setShowFade] = useState(false);

  const ref = useCallback((node: HTMLElement | null) => {
    setWrapper(node);
  }, []);

  useEffect(() => {
    if (!wrapper) {
      setShowFade(false);
      return;
    }

    const table = wrapper.querySelector("table");
    const thead = wrapper.querySelector("thead");

    let rafId: number | null = null;
    const update = () => {
      rafId = null;
      const { scrollWidth, clientWidth, scrollLeft } = wrapper;
      const isOverflowing = scrollWidth > clientWidth + 1;
      const isAtEnd = scrollLeft + clientWidth >= scrollWidth - 1;
      setShowFade(isOverflowing && !isAtEnd);

      if (thead) {
        wrapper.style.setProperty("--table-header-height", `${thead.getBoundingClientRect().height}px`);
      }
    };
    const scheduleUpdate = () => {
      if (rafId !== null) return;
      rafId = requestAnimationFrame(update);
    };

    update();

    wrapper.addEventListener("scroll", scheduleUpdate, { passive: true });
    window.addEventListener("resize", scheduleUpdate);

    // Catches column show/hide, pagination, data loading, etc. — anything
    // that changes the table's own rendered width without necessarily
    // firing a window resize. Observing the wrapper itself wouldn't work:
    // its visible size never changes, only its content's does.
    let resizeObserver: ResizeObserver | null = null;
    if (table) {
      resizeObserver = new ResizeObserver(scheduleUpdate);
      resizeObserver.observe(table);
    }

    return () => {
      if (rafId !== null) cancelAnimationFrame(rafId);
      wrapper.removeEventListener("scroll", scheduleUpdate);
      window.removeEventListener("resize", scheduleUpdate);
      resizeObserver?.disconnect();
    };
  }, [wrapper]);

  return [ref, showFade];
}
