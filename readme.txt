=== Multi-Step Forms & Client Document Intake – XPressUI Bridge ===
Contributors: iakpressteam
Tags: document collection, client portal, file upload, intake form, multi-step form
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.0.93
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Collect files, documents, and client intake form submissions directly in WordPress. Inline rendering, team inbox, and workflow tracking included.

== Description ==

**XPressUI Bridge** lets you embed multi-step forms and document-intake workflows built with the [XPressUI console](https://intakeflow.dev/) directly in your WordPress pages.

Export a workflow package from the XPressUI console as a `.zip` file, upload it to WordPress in one click, then embed it anywhere using the `[xpressui]` shortcode. The form renders natively inside your page — no iframe, no external dependencies at runtime. Submissions are stored as private posts in a dedicated wp-admin inbox, directly in your site's database.

If you need advanced field types, direct Console Sync, or cloud workflow management, those are available by connecting the plugin to your IntakeFlow SaaS account at intakeflow.dev.

= Key features =

* **One-click installation** — upload the exported `.zip` file from the XPressUI console directly inside wp-admin.
* **Shortcode embed** — `[xpressui id="your-project-slug"]` works in any page, post, or block-editor paragraph block. The form renders inline, inheriting your theme's page layout.
* **Submission inbox** — all submissions land in a private wp-admin post list with status badges, filtering by project, status, and assignee, and detailed review metaboxes.
* **Status workflow** — mark submissions *New*, *In review*, or *Done* from the list or the detail view. Every status change is recorded in a per-submission history log.
* **Team assignment** — assign any WordPress user to a submission. The *My Queue* page shows each reviewer their personal backlog at a glance.
* **Email notifications** — configure a notification address per project and receive a plain-text summary email the moment a new submission arrives.
* **Post-submit redirect** — optionally redirect the visitor to a thank-you page after a successful submission. Configured per project from wp-admin.
* **File uploads** — uploaded files are stored as WordPress media attachments and linked back to their submission.
* **REST API endpoint** — submissions are received via a standard WordPress REST route (`POST /wp-json/xpressui/v1/submit`). No extra server configuration required.
* **Bundled runtime** — the XPressUI standard runtime is bundled inside the plugin. No JavaScript is loaded from the uploads directory or external CDNs.

= Who is this for? =

Businesses and developers who use the XPressUI console to build document-intake or multi-step application forms and want to manage the collected data inside their existing WordPress environment without an external SaaS inbox.

= The Ultimate Alternative for Team Intake =

Unlike generic form builders (like Contact Form 7, WPForms, or Gravity Forms), XPressUI Bridge is built specifically for **client intake** and **document collection**. 
* **No paywall on team inbox:** Assign submissions to specific WordPress users without upgrading to expensive Enterprise plans (unlike JotForm or Typeform).
* **No bloated iframes:** Forms render natively in clean, fast HTML/JS, inheriting your active theme's styling.
* **100% GDPR-compliant:** Your submissions and uploaded files are stored entirely in your local database. No data is stored on third-party servers.

== Installation ==

1. Download the plugin `.zip` from the WordPress Plugin Directory or from [intakeflow.dev](https://intakeflow.dev/).
2. In your WordPress dashboard, go to **Plugins › Add New › Upload Plugin**, then select the downloaded `.zip` file and click **Install Now**.
3. Click **Activate Plugin**.
4. In the XPressUI console, export your workflow as a package (`.zip`).
5. In wp-admin, go to **Submissions › Workflows** and upload the workflow package.
6. Insert `[xpressui id="your-project-slug"]` in any page or post to embed the form.

== Frequently Asked Questions ==

= Where do I get the workflow package (.zip) to upload? =

The recommended way is to design and export it from the XPressUI console at [intakeflow.dev](https://intakeflow.dev/). You can also create a minimal package by hand — see the next question.

= Can I create a workflow package without the XPressUI console? =

Yes. A minimal package only needs two files:

* `manifest.json` — declares the project slug and schema version.
* `form.config.json` — declares the form sections and fields.

The plugin automatically fills in the technical defaults (submission endpoint, provider mode, metadata) that the console normally generates. Example `manifest.json`:

  {
    "$schema": "console.export/v2",
    "projectSlug": "my-form",
    "projectName": "My Form"
  }

Example `form.config.json` (single-step, two fields):

  {
    "sections": {
      "custom": [
        { "type": "section", "name": "main", "label": "Contact" }
      ],
      "main": [
        { "type": "text",  "name": "name",  "label": "Full name",  "required": true },
        { "type": "email", "name": "email", "label": "Email",      "required": true }
      ]
    }
  }

Zip both files inside a folder named after the project slug (`my-form/manifest.json`, `my-form/form.config.json`), then upload the zip in **Submissions › Workflows**.

= What does the [xpressui] shortcode accept? =

* `id` (required) — the project slug, matching the uploaded package folder name.

Example: `[xpressui id="loan-application"]`

= Where are submissions stored? =

Submissions are stored as private WordPress posts of the custom post type `xpressui_submission`, directly in your site's database. No data is sent to external servers.

= Can I export or delete submissions? =

Submissions can be deleted directly from the wp-admin list (Trash → Delete permanently). When a submission is permanently deleted, its uploaded files are deleted too. Export and bulk actions are on the roadmap.

= Does the plugin send emails? =

Only if you configure a notification email address for a project under **Submissions › Workflows › Project Settings**. The plugin uses WordPress's built-in `wp_mail()` function, so it respects any SMTP plugin you have installed.

= What file types can submitters upload? =

File uploads are handled by `media_handle_upload()`, which respects the WordPress file type allow-list configured under **Settings › Media**. By default, this includes common document, image, and archive formats.

= Is the REST endpoint publicly accessible? =

Yes — the `/wp-json/xpressui/v1/submit` endpoint accepts POST requests without authentication. This is intentional: form submissions originate from visitors who are not logged in. Each submission is stored as a private post and is only visible to authorised users inside wp-admin.

= What happens to uploaded files when a submission is deleted? =

Uploaded files are stored as WordPress media attachments. When a submission is permanently deleted, the plugin also permanently deletes the files linked to that submission. Trashing a submission does not immediately remove the files; permanent deletion does.

= What happens if I reinstall or delete the plugin? =

Workflow packs can be reinstalled without deleting submissions. If you delete and reinstall the plugin itself, submissions are preserved by default. To permanently remove submission data during uninstall, define the `XPRESSUI_BRIDGE_DELETE_SUBMISSIONS_ON_UNINSTALL` constant and set it to `true` before deleting the plugin.

= Does the plugin call any external API at runtime? =

No. Once a workflow package is installed, the plugin operates entirely within your WordPress site. The XPressUI console at intakeflow.dev is only used to design and export packages — it is not contacted during form rendering or submission processing.

== External Services ==

This plugin can optionally connect to the IntakeFlow SaaS platform (hosted at intakeflow.dev) to enable real-time cloud synchronization, advanced field types, and centralized workflow management.

When connected to IntakeFlow:
* The plugin makes outbound HTTP requests to `https://api.intakeflow.dev` (or your custom console URL) to sync project schemas, download workflows, and verify subscription status.
* Outbound sync requests include an API Token (`X-Api-Token`) generated from your IntakeFlow dashboard.
* No visitor or submission data is transmitted to the IntakeFlow console unless specifically configured by the administrator for cloud backup or webhook routing.

A connection is entirely optional. The plugin functions fully offline for standard local workflow file uploads.

The bundled XPressUI standard runtime (JavaScript) is served directly from the plugin directory — it is never loaded from a CDN or external URL.

== Privacy ==

This plugin stores data submitted by your site visitors (form field values, uploaded files, and metadata such as submission timestamps). All data is stored locally in your WordPress database and media library. No data is transmitted to external servers. When a submission is permanently deleted, its linked uploaded files are deleted as well.

Users may request access to or deletion of their personal data. This plugin integrates with the WordPress Personal Data tools (**Tools › Erase Personal Data** and **Tools › Export Personal Data**).

For full details on what data is collected and how to manage it, refer to your site's privacy policy.

== Source Code ==

The full source code for this plugin is available at:
https://github.com/lybaba/xpressui-wordpress-bridge

= Bundled JavaScript runtime =

The file `runtime/xpressui-*.umd.js` is the compiled output of the XPressUI library. The unminified TypeScript source files used to produce this bundle are included in the `xpressui-src/` directory of this plugin.

To rebuild the runtime from those sources:

1. Navigate to the source directory: `cd xpressui-src`
2. Install dependencies: `npm install`
3. Build the runtime: `npm run build`
4. The output file is produced in `xpressui-src/dist/xpressui-*.umd.js`.


== Screenshots ==

1. The submission list with status badges, project filter, and row actions.
2. The submission detail view with payload fields, status workflow, and assignment panel.
3. The Manage Workflows page showing installed packages and project settings.
4. A workflow embedded in a page using the [xpressui] shortcode — inline rendering, no iframe.

== Changelog ==

= 1.0.93 =
* Frame style: new appearance option (Card / Plain) to drop the form's card frame and inherit the host theme's container, avoiding a double frame on themes that already wrap content in a card.
* Use the full plugin name "Multi-Step Forms & Client Document Intake – XPressUI Bridge" consistently with the header, description and wordpress.org listing.
* Packaging: exclude .gitattributes from the distributed ZIP (hidden files are not permitted on WordPress.org).

= 1.0.86 =
* Keep the plugin name "XPressUI Bridge" consistent across the plugin header, description and admin UI (menu, labels) to match the wordpress.org listing and slug.

= 1.0.85 =
* Rename the product from XPressUI to IntakeFlow in metadata and the admin UI; regenerate the translation template (POT).
* Restore print-only / download-only workflow settings support.
* Fix the mobile capture dialog (close on session failure, scope the dialog lookup to the form).
* Harden workflow ZIP validation and escape shortcode output with wp_kses.

= 1.0.83 =
* Remove SVG from the workflow ZIP allowed-extensions list; SVG files can embed JavaScript and are therefore code-like assets not permitted in user-uploaded packages.
* Remove bundled shortcode-example.php from the document-intake starter workflow to prevent executable code files from being written to the uploads directory on first install.
* Rebuild the bundled light runtime from the included xpressui-src sources during packaging.
* Include readable runtime sources in the WordPress.org package while excluding generated dist files and dependency folders.

= 1.0.81 =
* Fix xpressui-src/package.json build scripts: remove prebuild/build:css steps that required dev-only files; npm run build now correctly builds the light runtime only.

= 1.0.80 =
* Update plugin site URL to https://intakeflow.dev/.

= 1.0.79 =
* Sanitize uploaded file MIME types with sanitize_mime_type() in addition to sanitize_file_name() for file names.
* Fix field label resolution for camelCase field names (e.g. primaryDocument) in notification emails and admin preview.
* Store signature field values as WordPress media attachments; render URL-based signatures in emails and admin preview.
* Improve admin submission file display: image thumbnails (100×75 px grid) and document list with icons and links.
* Compiled PHP templates: eliminate non-WordPress (standalone) branches at compile time; wrap inline CSS with phpcs annotations.
* Bundled XPressUI light runtime updated to 1.0.14.

= 1.0.77 =
* Cleaned legacy Pro-related strings from the free package.
* Clarified public messaging around the free bridge and the separate Pro add-on.
* Internal cleanup to keep the WordPress.org build focused on the free operational bridge.

= 1.0.70 =
* Custom workflow ZIP upload is available in the free plugin.
* Removed license and Pro-only gating from the WordPress.org build.
* Packaging now keeps the readable `xpressui-src/` sources while excluding dependency folders from release archives.

= 1.0.21 =
* Native inline rendering: the [xpressui] shortcode now renders the form directly inside the WordPress page without an iframe.
* Bundled XPressUI light runtime served from plugin assets — no JavaScript loaded from uploads or external URLs.
* Form CSS scoped to the embed container to avoid conflicts with the active WordPress theme.
* Runtime and init script enqueued via wp_enqueue_script for correct dependency ordering and deduplication.

= 1.0.0 =
* Initial release.
* Custom post type for submissions with status workflow (New, In review, Done).
* Status history log per submission.
* Team assignment and My Queue page.
* Project Inbox overview page.
* [xpressui] shortcode embed with bundled runtime.
* REST endpoint for receiving submissions and file uploads.
* Per-project email notifications via wp_mail().
* Per-project post-submit redirect URL.
* Manage Workflows page with ZIP upload and project settings.

== Upgrade Notice ==

= 1.0.77 =
This release cleans up legacy Pro references in the free package and clarifies the public positioning of the free bridge.

= 1.0.70 =
This release removes WordPress.org-incompatible feature gating and keeps the readable runtime source files in release archives.

= 1.0.21 =
The shortcode now renders the form inline — no iframe. If you have custom CSS targeting the `.xpressui-embed-wrapper iframe`, update your styles to target `.xpressui-inline-embed` instead.

= 1.0.0 =
Initial release — no upgrade steps required.
