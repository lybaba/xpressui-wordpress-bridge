# WordPress.org Assets

These files are uploaded via SVN to the plugin's `/assets/` directory on WordPress.org.
They are **versionless** and **NOT** included in the plugin ZIP.

**Deploy:** automatically on each release (`build-plugin.yml`, `ASSETS_DIR: .wordpress-org`),
or on demand via the `Update WordPress.org Assets` workflow (`update-wp-assets.yml`,
`workflow_dispatch`). Just drop the PNGs here, commit, and run/release.

Filenames are fixed by the WordPress.org convention — do not rename.

## Current (live)

- `icon-128x128.png` (128×128) · `icon-256x256.png` (256×256)
- `banner-772x250.png` (772×250) · `banner-1544x500.png` (1544×500)
- `screenshot-1.png` … `screenshot-4.png` (2048px wide — submission list, detail, workflows, inline form)

## To add before 1.1.2

### New screenshots (catalog / booking / checkout)
Keep **2048px wide** like the existing ones; PNG; clean demo data; same chrome/zoom for a consistent set.

| File | What to capture (from the demo) |
|---|---|
| `screenshot-5.png` | **Product catalog storefront** — the product grid rendered server-side in a WordPress page (SEO-friendly, no iframe). |
| `screenshot-6.png` | **Manual payment checkout step** — bank-transfer details (IBAN/BIC), the generated payment reference, and the proof-of-payment upload dropzone. |
| `screenshot-7.png` | **Service / time-slot booking** — the booking list with a selectable slot, leading into checkout. |

Readme captions are already prepared — they go into `readme.txt` `== Screenshots ==` the moment
these PNGs are committed (until then, do NOT add the captions or wp.org shows broken entries):

```
5. Product catalog storefront rendered natively in WordPress — server-side, SEO-friendly, no iframe.
6. Checkout with the manual payment step: bank-transfer details, a generated payment reference, and proof-of-payment upload.
7. Service / time-slot booking — the chosen slot is carried into the checkout step.
```

### Banner refresh (optional but recommended)
- `banner-772x250.png` (772×250) · `banner-1544x500.png` (1544×500, retina — same artwork, 2×).
- Broaden the message beyond intake: e.g. tagline "Forms · Intake · Booking · Checkout".
- IntakeFlow accent (the violet used in the app, ~#6C5CE7 / the checkout buttons) on a clean light or dark field.
- **Keep text minimal and away from the lower-left** — WordPress.org overlays the plugin name + author over the banner there.

### Icon refresh (low priority — current is recent)
- `icon-128x128.png` (128×128) · `icon-256x256.png` (256×256). `icon.svg` also accepted.
- The IntakeFlow mark; must stay legible at 32–36px (search/listing size). Avoid fine text.

## Checklist
- [ ] `screenshot-5.png` (catalog storefront)
- [ ] `screenshot-6.png` (manual payment checkout)
- [ ] `screenshot-7.png` (time-slot booking)
- [ ] (opt) refreshed `banner-772x250.png` + `banner-1544x500.png`
- [ ] (opt) refreshed `icon-128x128.png` + `icon-256x256.png`
- [ ] add the 3 captions to `readme.txt` `== Screenshots ==`
- [ ] commit + release 1.1.2 (or run `Update WordPress.org Assets` for image-only changes)
