type TSignatureRuntimeHost = any;

export function createSignatureRuntime(host: TSignatureRuntimeHost) {
  return {
    initField(fieldConfig: { name: string }) {
      const fn = fieldConfig.name;
      const wrap = host.querySelector(
        `[data-signature-field="${fn}"]`,
      ) as HTMLElement | null;
      if (!wrap || wrap.dataset.sigReady) return;
      wrap.dataset.sigReady = "1";

      const canvas = wrap.querySelector(
        `[data-signature-canvas="${fn}"]`,
      ) as HTMLCanvasElement | null;
      const input = host.querySelector(
        `input[data-name="${fn}"]`,
      ) as HTMLInputElement | null;
      const clearBtn = wrap.querySelector(
        `[data-signature-clear="${fn}"]`,
      ) as HTMLButtonElement | null;
      const hint = wrap.querySelector(
        `[data-signature-hint="${fn}"]`,
      ) as HTMLElement | null;

      if (!canvas || !input) return;

      const ctx = canvas.getContext("2d");
      if (!ctx) return;

      let drawing = false;
      ctx.strokeStyle = "#1a1a2e";
      ctx.lineWidth = 2;
      ctx.lineCap = "round";
      ctx.lineJoin = "round";

      const pos = (e: MouseEvent | TouchEvent) => {
        const r = canvas.getBoundingClientRect();
        const src = "touches" in e ? e.touches[0] : e;
        return {
          x: (src.clientX - r.left) * (canvas.width / r.width),
          y: (src.clientY - r.top) * (canvas.height / r.height),
        };
      };

      const start = (e: MouseEvent | TouchEvent) => {
        e.preventDefault();
        drawing = true;
        const p = pos(e);
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
      };

      const move = (e: MouseEvent | TouchEvent) => {
        if (!drawing) return;
        e.preventDefault();
        const p = pos(e);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
        if (hint) hint.style.display = "none";
        input.value = canvas.toDataURL("image/png");
        input.dispatchEvent(new Event("input", { bubbles: true }));
      };

      const end = () => {
        drawing = false;
      };

      canvas.addEventListener("mousedown", start as EventListener);
      canvas.addEventListener("mousemove", move as EventListener);
      canvas.addEventListener("mouseup", end);
      canvas.addEventListener("mouseleave", end);
      canvas.addEventListener("touchstart", start as EventListener, {
        passive: false,
      });
      canvas.addEventListener("touchmove", move as EventListener, {
        passive: false,
      });
      canvas.addEventListener("touchend", end);

      if (clearBtn) {
        clearBtn.addEventListener("click", () => {
          ctx.clearRect(0, 0, canvas.width, canvas.height);
          input.value = "";
          input.dispatchEvent(new Event("input", { bubbles: true }));
          if (hint) hint.style.display = "";
        });
      }
    },
  };
}
