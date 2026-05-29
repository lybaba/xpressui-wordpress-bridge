type TMobileCaptureHost = any;

const CAPTURE_ELIGIBLE_TYPES = new Set(['signature', 'camera-photo', 'camera-photo-list', 'qr-scan', 'document-scan']);
const POLL_INTERVAL_MS = 2000;
const POLL_TIMEOUT_MS = 10 * 60 * 1000; // 10 minutes
const ACTIVE_CAPTURE_CLEANUP = new WeakMap<HTMLDialogElement, () => void>();

function isDesktopBrowser(): boolean {
  if (typeof window === 'undefined') return false;
  const hasCoarsePointer = window.matchMedia?.('(pointer: coarse)').matches;
  const isMobileUA = /Mobi|Android|iPhone|iPad/i.test(navigator.userAgent);
  return !hasCoarsePointer && !isMobileUA;
}

function getWordPressRestBase(): string {
  return (window as any).XPRESSUI_WORDPRESS_REST_URL?.replace('/xpressui/v1/submit', '/xpressui/v1') ?? '';
}

export function resolveDesktopCaptureMode(): 'remote-capture' | 'native-picker' {
  return getWordPressRestBase() ? 'remote-capture' : 'native-picker';
}

export function openNativeFilePicker(fieldWrap: HTMLElement, fn: string): boolean {
  const fileInput = fieldWrap.querySelector(`input[type="file"][data-name="${fn}"]`) as HTMLInputElement | null;
  if (!fileInput || typeof fileInput.click !== 'function') {
    return false;
  }

  // Ensure selecting a newly captured file with the same OS filename still fires `change`.
  fileInput.value = '';
  fileInput.click();
  return true;
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

function createQrImageUrl(value: string): string {
  return `https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=2&data=${encodeURIComponent(value)}`;
}

function findCaptureDialog(host: TMobileCaptureHost): HTMLDialogElement | null {
  return (
    host.closest('[data-template-zone="form_frame"]')?.querySelector('[data-mobile-capture-modal]') ??
    document.querySelector('[data-mobile-capture-modal]') ??
    null
  ) as HTMLDialogElement | null;
}

function showDialog(dialog: HTMLDialogElement): void {
  dialog.style.display = '';
  dialog.setAttribute('aria-hidden', 'false');
  if (typeof dialog.showModal === 'function') {
    try {
      dialog.showModal();
      return;
    } catch {
      dialog.setAttribute('open', '');
      return;
    }
  }
  dialog.setAttribute('open', '');
}

function hideDialog(dialog: HTMLDialogElement): void {
  dialog.style.display = 'none';
  dialog.setAttribute('aria-hidden', 'true');
}

function closeDialog(dialog: HTMLDialogElement): void {
  if (typeof dialog.close === 'function' && dialog.open) {
    dialog.close();
  } else {
    dialog.removeAttribute('open');
  }
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

  const previousCleanup = ACTIVE_CAPTURE_CLEANUP.get(dialog);
  if (previousCleanup) {
    previousCleanup();
  }

  const qrImg = dialog.querySelector('[data-mobile-capture-modal-qr]') as HTMLImageElement | null;
  const statusText = dialog.querySelector('[data-mobile-capture-modal-status]') as HTMLElement | null;
  const closeBtn = dialog.querySelector('[data-mobile-capture-modal-close]') as HTMLButtonElement | null;
  if (!qrImg || !statusText) return;

  qrImg.removeAttribute('src');
  qrImg.toggleAttribute('hidden', true);
  statusText.textContent = 'Generating QR code…';
  statusText.style.color = '';

  const abort = new AbortController();
  let cleanedUp = false;
  const cleanup = () => {
    if (cleanedUp) return;
    cleanedUp = true;
    hideDialog(dialog);
    abort.abort();
    if (ACTIVE_CAPTURE_CLEANUP.get(dialog) === cleanup) {
      ACTIVE_CAPTURE_CLEANUP.delete(dialog);
    }
  };
  const close = () => {
    closeDialog(dialog);
    cleanup();
  };

  closeBtn?.addEventListener('click', close, { once: true, signal: abort.signal as any });
  dialog.addEventListener('click', (e) => { if (e.target === dialog) close(); }, { once: true, signal: abort.signal as any });
  dialog.addEventListener('close', cleanup, { once: true });

  showDialog(dialog);
  ACTIVE_CAPTURE_CLEANUP.set(dialog, cleanup);

  const session = await createCaptureSession(fieldName, fieldType, projectSlug);
  if (!session) {
    statusText.textContent = 'Could not start capture session.';
    statusText.style.color = '#ef4444';
    return;
  }

  qrImg.src = createQrImageUrl(session.captureUrl);
  qrImg.removeAttribute('hidden');

  statusText.textContent = 'Scan the QR code with your phone camera…';

  let elapsed = 0;
  let pollInFlight = false;
  let captureConsumed = false;
  const interval = setInterval(async () => {
    if (abort.signal.aborted) { clearInterval(interval); return; }
    if (pollInFlight || captureConsumed) {
      return;
    }
    pollInFlight = true;
    elapsed += POLL_INTERVAL_MS;
    if (elapsed > POLL_TIMEOUT_MS) {
      pollInFlight = false;
      clearInterval(interval);
      statusText.textContent = 'Session expired. Please try again.';
      statusText.style.color = '#ef4444';
      return;
    }
    try {
      const result = await pollCaptureSession(session.token);
      if (captureConsumed) {
        return;
      }
      if (result?.status === 'completed' && result.data) {
        captureConsumed = true;
        clearInterval(interval);
        statusText.textContent = '✓ Capture received!';
        statusText.style.color = '#16a34a';
        onData(result.data);
        setTimeout(close, 1000);
      }
    } finally {
      pollInFlight = false;
    }
  }, POLL_INTERVAL_MS);

  abort.signal.addEventListener('abort', () => clearInterval(interval));
}

function resolveImageUrl(data: string): string {
  let imageUrl = data;
  if (data.charAt(0) === '{') {
    try {
      const parsed = JSON.parse(data) as { url?: string };
      if (parsed.url) imageUrl = parsed.url;
    } catch { /* keep data as-is */ }
  }
  if (window.location.protocol === 'https:' && imageUrl.startsWith('http://')) {
    imageUrl = 'https://' + imageUrl.slice(7);
  }
  return imageUrl;
}

function updateFileInputFiles(fieldWrap: HTMLElement, fn: string, files: File[]): void {
  const fileInput = fieldWrap.querySelector(`input[type="file"][data-name="${fn}"]`) as HTMLInputElement | null;
  if (!fileInput || typeof DataTransfer === 'undefined') return;
  const dt = new DataTransfer();
  for (const f of files) dt.items.add(f);
  fileInput.files = dt.files;
  fileInput.dispatchEvent(new Event('change', { bubbles: true }));
}

function openLightbox(fieldWrap: HTMLElement, imageUrl: string): void {
  const galleryDialog = (
    fieldWrap.closest('[data-template-zone="form_frame"]')?.querySelector('[data-product-gallery-modal]') ??
    document.querySelector('[data-product-gallery-modal]')
  ) as HTMLDialogElement | null;
  if (!galleryDialog) { window.open(imageUrl, '_blank', 'noopener'); return; }
  const mainImg = galleryDialog.querySelector('[data-product-gallery-main]') as HTMLImageElement | null;
  const titleEl = galleryDialog.querySelector('[data-product-gallery-title]') as HTMLElement | null;
  const thumbsEl = galleryDialog.querySelector('[data-product-gallery-thumbs]') as HTMLElement | null;
  if (mainImg) mainImg.src = imageUrl;
  if (titleEl) titleEl.textContent = '';
  if (thumbsEl) thumbsEl.replaceChildren();
  const abort = new AbortController();
  let cleanedUp = false;
  const cleanup = () => {
    if (cleanedUp) return;
    cleanedUp = true;
    hideDialog(galleryDialog);
    abort.abort();
  };
  const doClose = () => {
    closeDialog(galleryDialog);
    cleanup();
  };
  showDialog(galleryDialog);
  galleryDialog.querySelector('[data-product-gallery-close]')?.addEventListener('click', doClose, { once: true, signal: abort.signal as any });
  galleryDialog.addEventListener('click', (ev) => { if (ev.target === galleryDialog) doClose(); }, { once: true, signal: abort.signal as any });
  galleryDialog.addEventListener('close', cleanup, { once: true });
}

function createPhotoPlaceholder(fn: string, labelText: string): HTMLElement {
  const el = document.createElement('label');
  el.className = 'xpui-photo-thumb xpui-photo-thumb--placeholder';
  el.setAttribute('for', fn);
  el.setAttribute('data-photo-placeholder', fn);
  el.innerHTML = `<span class="xpui-photo-thumb-icon">📷</span><span class="xpui-photo-thumb-text">${labelText}</span>`;
  return el;
}

function prepareDesktopPlaceholder(placeholder: HTMLElement): void {
  placeholder.removeAttribute('for');
  placeholder.setAttribute('role', 'button');
  if (!placeholder.hasAttribute('tabindex')) {
    placeholder.setAttribute('tabindex', '0');
  }
}

function initCameraPhotoField(
  host: TMobileCaptureHost,
  fieldWrap: HTMLElement,
  fn: string,
  type: string,
  projectSlug: string,
): void {
  const isList = type === 'camera-photo-list';
  const grid = fieldWrap.querySelector(`[data-photo-grid="${fn}"]`) as HTMLElement | null;
  if (!grid) return;
  const maxPhotos = Math.max(1, Number.parseInt(grid.dataset.photoGridMax || '5', 10) || 5);

  const capturedFiles: File[] = [];
  const placeholderText = isList ? 'Add photo' : 'Capture on mobile';

  const getFileInput = (): HTMLElement | null =>
    grid.querySelector(`input[type="file"][data-name="${fn}"]`);

  // ── Mobile path: file input change → read blob → show thumbnail ──────────────
  if (!isDesktopBrowser()) {
    const fileInput = grid.querySelector(`input[type="file"][data-name="${fn}"]`) as HTMLInputElement | null;
    if (!fileInput) return;
    fileInput.addEventListener('change', (event) => {
      if (!event.isTrusted) return;
      const selectedFiles = Array.from(fileInput.files ?? []);
      const remainingSlots = Math.max(0, maxPhotos - capturedFiles.length);
      const maxNewFiles = isList ? remainingSlots : Math.min(1, remainingSlots);
      if (maxNewFiles <= 0) {
        fileInput.value = '';
        return;
      }

      selectedFiles.slice(0, maxNewFiles).forEach((file) => {
        const previewUrl = URL.createObjectURL(file);
        const placeholder = grid.querySelector(`[data-photo-placeholder="${fn}"]`) as HTMLElement | null;
        const thumb = document.createElement('div');
        thumb.className = 'xpui-photo-thumb xpui-photo-thumb--captured';
        const img = document.createElement('img');
        img.alt = '';
        img.src = previewUrl;
        img.onclick = () => openLightbox(fieldWrap, previewUrl);
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'xpui-photo-thumb-remove';
        removeBtn.setAttribute('aria-label', 'Remove photo');
        removeBtn.textContent = '×';
        thumb.appendChild(img);
        thumb.appendChild(removeBtn);
        if (placeholder) { grid.insertBefore(thumb, placeholder); placeholder.remove(); }
        else { grid.insertBefore(thumb, getFileInput()); }
        capturedFiles.push(file);
        updateFileInputFiles(fieldWrap, fn, capturedFiles);
        removeBtn.onclick = () => {
          const idx = capturedFiles.indexOf(file);
          if (idx !== -1) capturedFiles.splice(idx, 1);
          updateFileInputFiles(fieldWrap, fn, capturedFiles);
          URL.revokeObjectURL(previewUrl);
          // Restored placeholder naturally opens camera via label[for] — no JS needed.
          grid.insertBefore(createPhotoPlaceholder(fn, placeholderText), thumb);
          thumb.remove();
        };
      });
      // Allow another capture that may reuse the same filename.
      fileInput.value = '';
    });
    return;
  }

  // ── Desktop path: placeholder click → QR modal ───────────────────────────────
  Array.from(grid.querySelectorAll(`[data-photo-placeholder="${fn}"]`)).forEach((node) => {
    prepareDesktopPlaceholder(node as HTMLElement);
  });

  const handleCapture = async (data: string): Promise<void> => {
    const imageUrl = resolveImageUrl(data);

    // Find and remove the first empty placeholder.
    const placeholder = grid.querySelector(`[data-photo-placeholder="${fn}"]`) as HTMLElement | null;

    // Build the captured thumbnail.
    const thumb = document.createElement('div');
    thumb.className = 'xpui-photo-thumb xpui-photo-thumb--captured';

    const img = document.createElement('img');
    img.alt = '';
    img.src = imageUrl;
    img.onclick = () => openLightbox(fieldWrap, imageUrl);

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'xpui-photo-thumb-remove';
    removeBtn.setAttribute('aria-label', 'Remove photo');
    removeBtn.textContent = '×';

    thumb.appendChild(img);
    thumb.appendChild(removeBtn);

    // Replace the placeholder with the thumb (or append before the input if no placeholder left).
    if (placeholder) {
      grid.insertBefore(thumb, placeholder);
      placeholder.remove();
    } else {
      grid.insertBefore(thumb, getFileInput());
    }

    // Fetch blob and wire up the remove button.
    try {
      const blob = await fetch(imageUrl).then((r) => r.blob());
      const file = new File(
        [blob],
        isList ? `photo-${capturedFiles.length + 1}.jpg` : 'photo.jpg',
        { type: blob.type || 'image/jpeg' },
      );
      capturedFiles.push(file);
      updateFileInputFiles(fieldWrap, fn, capturedFiles);

      removeBtn.onclick = () => {
        const idx = capturedFiles.indexOf(file);
        if (idx !== -1) capturedFiles.splice(idx, 1);
        updateFileInputFiles(fieldWrap, fn, capturedFiles);
        // Restore a placeholder at this position so the slot is reusable.
        const restored = createPhotoPlaceholder(fn, placeholderText);
        prepareDesktopPlaceholder(restored);
        grid.insertBefore(restored, thumb);
        thumb.remove();
      };
    } catch {
      // Fetch failed — thumbnail is shown but file input won't be set.
      removeBtn.onclick = () => {
        const restored = createPhotoPlaceholder(fn, placeholderText);
        prepareDesktopPlaceholder(restored);
        grid.insertBefore(restored, thumb);
        thumb.remove();
      };
    }
  };

  // Use delegated click handling so capture keeps working even if placeholders
  // are re-rendered after the first capture/update cycle.
  fieldWrap.addEventListener('click', (e) => {
    const target = e.target as HTMLElement | null;
    const placeholder = target?.closest(`[data-photo-placeholder="${fn}"]`) as HTMLElement | null;
    if (!placeholder || !fieldWrap.contains(placeholder)) {
      return;
    }
    e.preventDefault(); // prevent label default file dialog on desktop
    if (resolveDesktopCaptureMode() === 'native-picker') {
      openNativeFilePicker(fieldWrap, fn);
      return;
    }
    if (isList && capturedFiles.length >= maxPhotos) {
      return;
    }
    void openCaptureModal(host, fn, type, projectSlug, (data) => void handleCapture(data));
  });
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

      const fn = fieldConfig.name;
      const fieldWrap = host.querySelector(`[data-field-name="${fn}"]`) as HTMLElement | null;
      if (!fieldWrap) return;

      const projectSlug = host.querySelector('[data-project-slug]')?.dataset?.projectSlug ?? '';

      // Photo-grid path: handles both mobile (file input change) and desktop (QR modal).
      if (fieldConfig.type === 'camera-photo' || fieldConfig.type === 'camera-photo-list') {
        initCameraPhotoField(host, fieldWrap, fn, fieldConfig.type, projectSlug);
        return;
      }

      // Button path for signature, qr-scan, document-scan: desktop only.
      if (!isDesktopBrowser()) return;
      const btn = fieldWrap.querySelector(`[data-mobile-capture-btn="${fn}"]`) as HTMLButtonElement | null;
      if (!btn) return;

      btn.removeAttribute('hidden');

      const uploadBox = fieldWrap.querySelector('.template-upload-box') as HTMLElement | null;
      if (uploadBox) uploadBox.classList.add('xpui-capture-only');

      btn.addEventListener('click', () => {
        openCaptureModal(host, fn, fieldConfig.type, projectSlug, (data) => {
          if (fieldConfig.type === 'signature') {
            applySignatureCaptureToCanvas(fieldWrap, fn, data);
            const input = fieldWrap.querySelector(`input[data-name="${fn}"]`) as HTMLInputElement | null;
            if (input) {
              input.value = data;
              input.dispatchEvent(new Event('input', { bubbles: true }));
            }
          } else {
            // qr-scan, document-scan
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
