interface MobileCaptureConfig {
  relayUrl: string;
  fieldType: string;
}

function getConfig(): MobileCaptureConfig | null {
  const raw = (window as any).XPRESSUI_CAPTURE_CONFIG;
  if (!raw || typeof raw.relayUrl !== 'string' || typeof raw.fieldType !== 'string') return null;
  return raw as MobileCaptureConfig;
}

function setStatus(msg: string, state: 'idle' | 'success' | 'error' = 'idle'): void {
  const el = document.getElementById('status-msg');
  if (!el) return;
  el.textContent = msg;
  el.className = state === 'idle' ? 'cp-status' : `cp-status ${state}`;
}

async function relay(data: string, relayUrl: string, isFile = false): Promise<boolean> {
  try {
    let body: BodyInit;
    if (isFile) {
      const fd = new FormData();
      const blob = await (await fetch(data)).blob();
      fd.append('capture_file', blob, 'photo.jpg');
      body = fd;
    } else {
      body = JSON.stringify({ data });
    }
    const res = await fetch(relayUrl, {
      method: 'POST',
      ...(isFile ? {} : { headers: { 'Content-Type': 'application/json' } }),
      body,
    });
    return res.ok;
  } catch {
    return false;
  }
}

function stopStream(video: HTMLVideoElement): void {
  if (video.srcObject instanceof MediaStream) {
    (video.srcObject as MediaStream).getTracks().forEach((t) => t.stop());
  }
}

function initSignatureCapture(relayUrl: string): void {
  const canvas = document.getElementById('sig-canvas') as HTMLCanvasElement | null;
  const submitBtn = document.getElementById('submit-btn') as HTMLButtonElement | null;
  const clearBtn = document.querySelector<HTMLButtonElement>('.cp-btn-secondary');
  if (!canvas || !submitBtn) return;

  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  let drawing = false;
  ctx.strokeStyle = '#0f172a';
  ctx.lineWidth = 2;
  ctx.lineCap = 'round';

  const pos = (e: MouseEvent | TouchEvent) => {
    const r = canvas.getBoundingClientRect();
    const src = 'touches' in e ? e.touches[0] : e;
    return {
      x: (src.clientX - r.left) * (canvas.width / r.width),
      y: (src.clientY - r.top) * (canvas.height / r.height),
    };
  };

  canvas.addEventListener('mousedown', (e) => { drawing = true; const p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); });
  canvas.addEventListener('mousemove', (e) => { if (!drawing) return; const p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); });
  canvas.addEventListener('mouseup', () => { drawing = false; });
  canvas.addEventListener('touchstart', (e) => { e.preventDefault(); drawing = true; const p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); }, { passive: false });
  canvas.addEventListener('touchmove', (e) => { e.preventDefault(); if (!drawing) return; const p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); }, { passive: false });
  canvas.addEventListener('touchend', () => { drawing = false; });

  clearBtn?.addEventListener('click', () => { ctx.clearRect(0, 0, canvas.width, canvas.height); });

  submitBtn.addEventListener('click', async () => {
    submitBtn.disabled = true;
    setStatus('Sending…');
    const data = canvas.toDataURL('image/png');
    const ok = await relay(data, relayUrl);
    if (ok) {
      setStatus('✓ Signature sent! You can close this page.', 'success');
    } else {
      setStatus('Error. Please try again.', 'error');
      submitBtn.disabled = false;
    }
  });
}

function initPhotoCapture(relayUrl: string): void {
  const video = document.getElementById('video') as HTMLVideoElement | null;
  const canvas = document.getElementById('photo-canvas') as HTMLCanvasElement | null;
  const captureBtn = document.getElementById('capture-btn') as HTMLButtonElement | null;
  const flipBtn = document.getElementById('flip-btn') as HTMLButtonElement | null;
  if (!video || !canvas || !captureBtn) return;

  let facingMode: 'environment' | 'user' = 'environment';

  function startStream(): void {
    if (video!.srcObject instanceof MediaStream) {
      (video!.srcObject as MediaStream).getTracks().forEach((t) => t.stop());
    }
    navigator.mediaDevices.getUserMedia({ video: { facingMode }, audio: false })
      .then((stream) => { video!.srcObject = stream; })
      .catch(() => { setStatus('Camera access denied. Please allow camera and reload.', 'error'); captureBtn!.disabled = true; });
  }

  startStream();

  flipBtn?.addEventListener('click', () => {
    facingMode = facingMode === 'environment' ? 'user' : 'environment';
    startStream();
  });

  captureBtn.addEventListener('click', () => {
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;
    const c = canvas.getContext('2d');
    if (!c) return;
    c.drawImage(video, 0, 0);
    canvas.toBlob(async (blob) => {
      if (!blob) return;
      captureBtn.disabled = true;
      setStatus('Uploading…');
      const fd = new FormData();
      fd.append('capture_file', blob, 'photo.jpg');
      try {
        const res = await fetch(relayUrl, { method: 'POST', body: fd });
        if (res.ok) {
          setStatus('✓ Photo sent! You can close this page.', 'success');
          stopStream(video);
        } else {
          setStatus('Upload error. Please try again.', 'error');
          captureBtn.disabled = false;
        }
      } catch {
        setStatus('Network error.', 'error');
        captureBtn.disabled = false;
      }
    }, 'image/jpeg', 0.85);
  });
}

function initQrCapture(relayUrl: string): void {
  const video = document.getElementById('video') as HTMLVideoElement | null;
  if (!video) return;

  const BarcodeDetectorCtor = (window as any).BarcodeDetector as (new (opts: object) => { detect(src: HTMLVideoElement): Promise<Array<{ rawValue?: string }>> }) | undefined;
  if (!BarcodeDetectorCtor) {
    setStatus('QR scanning requires Chrome on Android or iOS 17+. Open in a supported browser.', 'error');
    return;
  }

  const detector = new BarcodeDetectorCtor({ formats: ['qr_code'] });
  let relayed = false;

  navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false })
    .then((stream) => {
      video.srcObject = stream;
      void video.play();

      const tick = () => {
        if (relayed || video.paused || video.ended) return;
        void detector.detect(video).then((codes) => {
          const code = codes.find((c) => c.rawValue)?.rawValue;
          if (code) {
            relayed = true;
            setStatus('Relaying QR code…');
            relay(code, relayUrl).then((ok) => {
              if (ok) {
                setStatus('✓ QR code sent! You can close this page.', 'success');
                stopStream(video);
              } else {
                setStatus('Relay error. Please try again.', 'error');
                relayed = false;
              }
            });
          } else {
            requestAnimationFrame(tick);
          }
        }).catch(() => requestAnimationFrame(tick));
      };

      video.addEventListener('playing', () => requestAnimationFrame(tick), { once: true });
    })
    .catch(() => setStatus('Camera access denied. Please allow camera and reload.', 'error'));
}

export function initMobileCapturePage(): void {
  const config = getConfig();
  if (!config) return;

  switch (config.fieldType) {
    case 'signature':
      initSignatureCapture(config.relayUrl);
      break;
    case 'qr-scan':
      initQrCapture(config.relayUrl);
      break;
    default:
      initPhotoCapture(config.relayUrl);
  }
}
