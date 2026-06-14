# Fabrication Carte Grise WordPress integration

This package gives you a ready-to-publish IntakeFlow workflow for WordPress.

## 📦 What you get
- **The Workflow Package**: A ready-to-publish, multi-step frontend flow.
- **WordPress Integration**: This pack is meant to run through the IntakeFlow WordPress plugins.

## 🚀 Install in WordPress
1. **Install the free bridge plugin**: Install and activate `IntakeFlow Bridge` from WordPress.org or download it from GitHub (`https://github.com/lybaba/xpressui-wordpress-bridge`).
2. **Install IntakeFlow Pro**: Purchase/download the Pro plugin from `https://app.intakeflow.dev/`, then install and activate it in WordPress.
3. **Upload this workflow pack**: Go to **IntakeFlow > Workflows** in `wp-admin` and upload this `.zip` file.
4. **Embed the workflow**: Create or edit a WordPress page and use the generated shortcode for this workflow.

### Shortcode Options
You can customize the workflow behavior directly via the shortcode:
```text
   [xpressui id="demande-carte-grise"]
   [xpressui id="demande-carte-grise" redirect="https://yoursite.com/thank-you/"]
```
- `redirect`: (Optional) URL to seamlessly redirect the user to after a successful submission.

---

## ✅ Quick Validation
- open the embedded workflow page
- submit one entry with at least one uploaded file
- confirm the success message matches the configured project message
- confirm the new submission in `Project Inbox` and the detail view in `wp-admin`
- confirm the uploaded files appear in the WordPress media library

## ⚙️ Operational Notes
- install the free bridge plugin first, then activate the Pro plugin before uploading this pack
- reuse the same bridge/pro plugin pair for every exported IntakeFlow project on the site
- each workflow can live on its own WordPress page
- the bridge plugin provides the runtime directly from plugin assets
- workflow package path: `/wp-content/uploads/xpressui/demande-carte-grise/`
- plugin shell URL: `/?xpressui_shell=demande-carte-grise`

## 🔒 Storage & Security
- exported visual assets can remain on stable public URLs
- end-user uploaded files should be stored in WordPress media storage

## 🛟 Support Boundary
- included: bridge install guidance, package placement guidance, expected admin behavior
- not included by default: custom theme work, custom plugin work, third-party conflict resolution

Expected response:
```json
{
  "success": true,
  "message": "Votre dossier de carte grise a \u00e9t\u00e9 soumis avec succ\u00e8s. Notre \u00e9quipe va proc\u00e9der \u00e0 la v\u00e9rification des pi\u00e8ces sous 24h.",
  "entryId": 123,
  "submissionId": "01JNY0F0P6T7Q3F2J6Q1M9J8TW",
  "files": [
    {
      "field": "ancienneCarteGrise",
      "attachmentId": 456,
      "url": "https://example.com/wp-content/uploads/.../identity-document.pdf"
    }
  ]
}
```

Resolved submission endpoint example: `rest_url('xpressui/v1/submit')`