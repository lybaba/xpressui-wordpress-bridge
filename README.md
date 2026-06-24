# IntakeFlow – Client Intake, Multi-Step Forms & Secure Document Collection

**Embed IntakeFlow workflow forms on your WordPress site and manage submissions from wp-admin.**

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
![WordPress: 6.0+](https://img.shields.io/badge/WordPress-6.0%2B-21759b)
![PHP: 8.0+](https://img.shields.io/badge/PHP-8.0%2B-777bb4)
![Downloads](https://img.shields.io/github/downloads/lybaba/xpressui-wordpress-bridge/total)

---

## Documentation & Live Walkthrough

For a complete, interactive step-by-step guide with screenshots and video demonstrations on how to use IntakeFlow, check out the [Live Walkthrough Page](https://lybaba.github.io/xpressui-wordpress-bridge/).

---

## What it does

Build multi-step forms and document-intake workflows in the IntakeFlow Console,
connect your WordPress site via API Token, sync your workflows, then embed them
anywhere with a shortcode.

```
[xpressui id="loan-application"]
```

Every submission lands in a private wp-admin inbox. Your team can review, assign, and track status without leaving WordPress.

The WordPress bridge is the main delivery path today. Over time, the same
builder/export pipeline is also intended to produce standalone packages for
non-WordPress hosts.

Need advanced field types, direct Console sync, or local workflow customization?
Connect your site to the SaaS Console using your API Token to sync and build complex workflows.

---

## Features

| Feature | Details |
|---|---|
| **Bundled starter** | `Document Intake` is ready immediately — no connection needed |
| **Console Sync** | Pull workflows directly from your IntakeFlow Console via API Token |
| **Shortcode embed** | `[xpressui id="slug"]` — works in pages, posts, and the block editor |
| **Submission inbox** | Private post list with project / status / assignee filters |
| **Status workflow** | *New → In review → Done*, with a full history log per submission |
| **Team assignment** | Assign any WP user; personal *My Queue* page per reviewer |
| **Email notifications** | Per-project notification address via `wp_mail()` |
| **Post-submit redirect** | Per-project thank-you page URL, returned to the runtime |
| **File uploads** | Stored as WP media attachments, linked to the submission |
| **REST endpoint** | `POST /wp-json/xpressui/v1/submit` — no extra server config |

---

## Requirements

- WordPress 6.0 or later
- PHP 8.0 or later
- An active IntakeFlow account and API Token (to sync custom workflows)

---

## Installation

### Manual installation

1. Download the latest release `.zip` from the [Releases page](../../releases).
2. In wp-admin go to **Plugins › Add New › Upload Plugin**, select the zip, click **Install Now**.
3. Activate the plugin.

### Use a bundled starter

The plugin ships with the `File Request` workflow ready to use — no upload required.

1. Go to **Submissions › Workflows** in wp-admin.
2. Copy the shortcode for `file-request` and paste it into any page or post.

### Sync a custom workflow package

1. In the WordPress dashboard, go to **Submissions › Workflows**.
2. Enter your API Token in the **Console Sync** section and save.
3. Click **Load from Console**, then click **Sync** next to your workflow.
4. Embed the form with `[xpressui id="your-project-slug"]`.

---

## Shortcode reference

```
[xpressui id="slug"]
```

| Attribute | Default | Description |
|---|---|---|
| `id` | *(required)* | Project slug — matches the uploaded package folder name |

---

## REST endpoint

Submissions are posted by the XPressUI runtime to:

```
POST /wp-json/xpressui/v1/submit
Content-Type: multipart/form-data
```

Key response fields:

```json
{
  "success": true,
  "entryId": 42,
  "submissionId": "abc-123",
  "files": [...],
  "redirectUrl": "https://example.com/thank-you"
}
```

`redirectUrl` is only included when configured under *Submissions › Workflows › Project Settings*.

---

## Project settings

Configure per-project options under **Submissions › Workflows › Project Settings**:

- **Notification email** — receive a plain-text email for each new submission.
- **Post-submit redirect URL** — redirect the visitor after a successful submission.

---

## Contributing

Pull requests are welcome. Please open an issue first to discuss significant changes.

This plugin is licensed under the [GPL-2.0-or-later](LICENSE) license. Contributions must be compatible with this license.

---

## License

GPL-2.0-or-later — see [LICENSE](LICENSE).

---

## Links

- Product page: [XpressUI](https://intakeflow.dev/document-intake/xpressui)
- Demo : [Document-Intake](https://intakeflow.dev/document-intake/)
- Support: [Contact](https://intakeflow.dev/document-intake/contact)
