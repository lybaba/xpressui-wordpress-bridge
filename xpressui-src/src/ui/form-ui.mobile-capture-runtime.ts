import QRCode from 'qrcode';

type TMobileCaptureHost = any;

const CAPTURE_ELIGIBLE_TYPES = new Set(['signature', 'camera-photo', 'qr-scan', 'document-scan']);
const POLL_INTERVAL_MS = 2000;
const POLL_TIMEOUT_MS = 10 * 60 * 1000; // 10 minutes

function isDesktopBrowser(): boolean {
  if (typeof window === 'undefined') return false;
  const hasCoarsePointer = window.matchMedia?.('(pointer: coarse)').matches;
  const isMobileUA = /Mobi|Android|iPhone|iPad/i.test(navigator.userAgent);
  return !hasCoarsePointer && !isMobileUA;
}

function getWordPressRestBase(): string {
  return (window as any).XPRESSUI_WORDPRESS_REST_URL?.replace('/xpressui/v1/submit', '/xpressui/v1') ?? '';
}

async function createCaptureSession(
  fieldName: string,
  fieldType: string,
  projectSlug: string,
): Promise<{ token: string; captureUrl: string } | null> {
  const base = getWordPressRestBase();
  if (!base) return null;
  try {
    const res = await fetch(`${base}/capture/session`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ fieldName, fieldType, projectSlug }),
    });
    if (!res.ok) return null;
    return await res.json();
  } catch {
    return null;
  }
}

async function pollCaptureSession(
  token: string,
): Promise<{ status: string; data?: string } | null> {
  const base = getWordPressRestBase();
  if (!base) return null;
  try {
    const res = await fetch(`${base}/capture/poll/${encodeURIComponent(token)}`);
    if (!res.ok) return null;
    return await res.json();
  } catch {
    return null;
  }
}

function findCaptureDialog(host: TMobileCaptureHost): HTMLDialogElement | null {
  return (
    host.closest('[data-template-zone="form_frame"]')?.querySelector('[data-mobile-capture-modal]') ??
    document.querySelector('[data-mobile-capture-modal]') ??
    null
  ) as HTMLDialogElement | null;
}

async function openCaptureModal(
  host: TMobileCaptureHost,
  fieldName: string,
  fieldType: string,
  projectSlug: string,
  onData: (data: string) => void,
): Promise<void> {
  const dialog = findCaptureDialog(host);
  if (!dialog) return;

  const qrImg = dialog.querySelector('[data-mobile-capture-modal-qr]') as HTMLImageElement | null;
  const statusText = dialog.querySelector('[data-mobile-capture-modal-status]') as HTMLElement | null;
  const closeBtn = dialog.querySelector('[data-mobile-capture-modal-close]') as HTMLButtonElement | null;
  if (!qrImg || !statusText) return;

  qrImg.removeAttribute('src');
  qrImg.removeAttribute('hidden');
  statusText.textContent = 'Waiting for capture…';
  statusText.style.color = '';

  const abort = new AbortController();
  const close = () => { dialog.close(); abort.abort(); };

  closeBtn?.addEventListener('click', close, { once: true, signal: abort.signal as any });
  dialog.addEventListener('click', (e) => { if (e.target === dialog) close(); }, { once: true, signal: abort.signal as any });

  dialog.showModal();

  const session = await createCaptureSession(fieldName, fieldType, projectSlug);
  if (!session) {
    statusText.textContent = 'Could not start capture session.';
    statusText.style.color = '#ef4444';
    return;
  }

  try {
    qrImg.src = await QRCode.toDataURL(session.captureUrl, { width: 200, margin: 2 });
  } catch {
    qrImg.toggleAttribute('hidden', true);
  }

  statusText.textContent = 'Scan the QR code with your phone camera…';

  let elapsed = 0;
  const interval = setInterval(async () => {
    if (abort.signal.aborted) { clearInterval(interval); return; }
    elapsed += POLL_INTERVAL_MS;
    if (elapsed > POLL_TIMEOUT_MS) {
      clearInterval(interval);
      statusText.textContent = 'Session expired. Please try again.';
      statusText.style.color = '#ef4444';
      return;
    }
    const result = await pollCaptureSession(session.token);
    if (result?.status === 'completed' && result.data) {
      clearInterval(interval);
      statusText.textContent = '✓ Capture received!';
      statusText.style.color = '#16a34a';
      onData(result.data);
      setTimeout(close, 1000);
    }
  }, POLL_INTERVAL_MS);

  abort.signal.addEventListener('abort', () => clearInterval(interval));
}

async function applyCameraPhotoCaptureToField(fieldWrap: HTMLElement, fn: string, data: string): Promise<void> {
  // data may be a JSON string {"attachmentId":X,"url":"..."} from the relay
  let imageUrl = data;
  if (data.charAt(0) === '{') {
    try {
      const parsed = JSON.parse(data) as { url?: string };
      if (parsed.url) imageUrl = parsed.url;
    } catch { /* keep data as-is */ }
  }

  const preview = fieldWrap.querySelector(`[data-camera-capture-preview="${fn}"]`) as HTMLImageElement | null;
  if (preview && imageUrl) {
    preview.src = imageUrl;
    preview.removeAttribute('hidden');
  }

  const fileInput = fieldWrap.querySelector(`input[type="file"][data-name="${fn}"]`) as HTMLInputElement | null;
  if (fileInput && typeof DataTransfer !== 'undefined') {
    try {
      const blob = await fetch(imageUrl).then((r) => r.blob());
      const file = new File([blob], 'photo.jpg', { type: blob.type || 'image/jpeg' });
      const dt = new DataTransfer();
      dt.items.add(file);
      fileInput.files = dt.files;
      fileInput.dispatchEvent(new Event('change', { bubbles: true }));
    } catch {
      // fetch or DataTransfer failed — preview only
    }
  }
}

function applySignatureCaptureToCanvas(fieldWrap: HTMLElement, fn: string, dataUrl: string): void {
  const canvas = fieldWrap.querySelector(`[data-signature-canvas="${fn}"]`) as HTMLCanvasElement | null;
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  if (!ctx) return;
  const img = new Image();
  img.onload = () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
  };
  img.src = dataUrl;
  const hint = fieldWrap.querySelector(`[data-signature-hint="${fn}"]`) as HTMLElement | null;
  if (hint) hint.style.display = 'none';
}

export function createMobileCaptureRuntime(host: TMobileCaptureHost) {
  return {
    initField(fieldConfig: { name: string; type: string }) {
      if (!CAPTURE_ELIGIBLE_TYPES.has(fieldConfig.type)) return;
      if (!isDesktopBrowser()) return;

      const fn = fieldConfig.name;
      const fieldWrap = host.querySelector(`[data-field-name="${fn}"]`) as HTMLElement | null;
      if (!fieldWrap) return;

      const btn = fieldWrap.querySelector(`[data-mobile-capture-btn="${fn}"]`) as HTMLButtonElement | null;
      if (!btn) return;

      btn.removeAttribute('hidden');

      // For camera-photo: hide the file upload box on desktop — live capture via mobile is the only path.
      if (fieldConfig.type === 'camera-photo') {
        const uploadBox = fieldWrap.querySelector('.template-upload-box') as HTMLElement | null;
        if (uploadBox) uploadBox.classList.add('xpui-capture-only');
      }

      const projectSlug = host.querySelector('[data-project-slug]')?.dataset?.projectSlug ?? '';

      btn.addEventListener('click', () => {
        openCaptureModal(host, fn, fieldConfig.type, projectSlug, (data) => {
          if (fieldConfig.type === 'signature') {
            applySignatureCaptureToCanvas(fieldWrap, fn, data);
            const input = fieldWrap.querySelector(`input[data-name="${fn}"]`) as HTMLInputElement | null;
            if (input) {
              input.value = data;
              input.dispatchEvent(new Event('input', { bubbles: true }));
            }
          } else if (fieldConfig.type === 'camera-photo') {
            void applyCameraPhotoCaptureToField(fieldWrap, fn, data);
          } else {
            const input = fieldWrap.querySelector(`input[data-name="${fn}"]`) as HTMLInputElement | null;
            if (input) {
              input.value = data;
              input.dispatchEvent(new Event('input', { bubbles: true }));
            }
          }
        });
      });
    },
  };
}
