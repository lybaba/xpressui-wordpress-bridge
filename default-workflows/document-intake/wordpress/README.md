# Document Intake WordPress pack

This package gives you a ready-to-publish document intake workflow for WordPress.

## 📦 What you get
- **The Workflow Package**: A ready-to-publish, multi-step frontend flow.
- **Project Inbox**: A readable, organized operator view inside `wp-admin`.

## 🚀 Install in 2 steps
1. **Install the Plugin**: Install and activate the [XPressUI plugin](https://wordpress.org/plugins/xpressui) from the WordPress plugin directory. The document-intake workflow is bundled with the plugin and installed automatically on activation — no manual upload required.
2. **Embed**: Copy the generated shortcode and paste it into any WordPress Page or Post.

### Shortcode Options
Embed the workflow directly in any page or post:
```text
   [xpressui id="document-intake"]
```

Use **XPressUI -> Workflows -> Project Settings** if you want to configure:
- the post-submit redirect URL
- whether the workflow title is displayed above the form
- whether the `* Required fields` note is displayed

---

## 🛠 Included Files (For Developers)
- `wordpress/shortcode-example.php`: PHP shortcode logic example
- `manifest.json`, `form.config.json`, `template.context.json`: Workflow metadata and render payload consumed by the plugin runtime

Workflow included:
1. Contact
2. Request
3. Files
4. Review and submit

## ✅ Quick Validation
- open the embedded workflow page
- submit one entry with at least one uploaded file
- confirm the success message matches the configured project message
- confirm the new submission in `Project Inbox` and the detail view in `wp-admin`
- confirm the uploaded files appear in the WordPress media library

## ⚙️ Operational Notes
- reuse the same bridge plugin for every exported XPressUI project on the site
- each workflow can live on its own WordPress page
- the form renders inline inside the WordPress page
- workflow package path: `/wp-content/uploads/xpressui/document-intake/`
- plugin shell URL: `/?xpressui_shell=document-intake`

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
  "message": "Your intake was sent successfully.",
  "entryId": 123,
  "submissionId": "01JNY0F0P6T7Q3F2J6Q1M9J8TW",
  "files": [
    {
      "field": "primaryDocument",
      "attachmentId": 456,
      "url": "https://example.com/wp-content/uploads/.../identity-document.pdf"
    }
  ]
}
```

Resolved submission endpoint example: `rest_url('xpressui/v1/submit')`
