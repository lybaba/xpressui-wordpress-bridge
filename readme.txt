=== XPressUI Bridge ===
Contributors: iakpressteam
Tags: form, submission, workflow, document intake, multi-step
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.0.44
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed any XPressUI workflow on your WordPress site with a shortcode and review incoming submissions straight from wp-admin.

== Description ==

**XPressUI Bridge** lets you embed multi-step forms and document-intake workflows built with the [XPressUI console](https://xpressui.iakpress.com/) directly in your WordPress pages.

Export a workflow package from the XPressUI console as a `.zip` file, upload it to WordPress in one click, then embed it anywhere using the `[xpressui]` shortcode. The form renders natively inside your page — no iframe, no external dependencies at runtime. Submissions are stored as private posts in a dedicated wp-admin inbox, directly in your site's database.

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
* **Bundled runtime** — the XPressUI light runtime is bundled inside the plugin. No JavaScript is loaded from the uploads directory or external CDNs.

= Who is this for? =

Businesses and developers who use the XPressUI console to build document-intake or multi-step application forms and want to manage the collected data inside their existing WordPress environment without an external SaaS inbox.

== Installation ==

1. Download the plugin `.zip` from the WordPress Plugin Directory or from [xpressui.iakpress.com](https://xpressui.iakpress.com/).
2. In your WordPress dashboard, go to **Plugins › Add New › Upload Plugin**, then select the downloaded `.zip` file and click **Install Now**.
3. Click **Activate Plugin**.
4. In the XPressUI console, export your workflow as a package (`.zip`).
5. In wp-admin, go to **Submissions › Workflows** and upload the workflow package.
6. Insert `[xpressui id="your-project-slug"]` in any page or post to embed the form.

== Frequently Asked Questions ==

= Where do I get the workflow package (.zip) to upload? =

You build and export it in the XPressUI console at [xpressui.iakpress.com](https://xpressui.iakpress.com/). The console lets you design multi-step forms and document-intake workflows without code, then export them as self-contained packages.

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

No. Once a workflow package is installed, the plugin operates entirely within your WordPress site. The XPressUI console at xpressui.iakpress.com is only used to design and export packages — it is not contacted during form rendering or submission processing.

== External Services ==

This plugin does **not** make any outbound HTTP requests at runtime. The XPressUI console (hosted at xpressui.iakpress.com) is a separate design tool used to export workflow packages. It is not contacted by this plugin during normal operation on your site.

The bundled XPressUI light runtime (JavaScript) is served directly from the plugin directory — it is never loaded from a CDN or external URL.

== Privacy ==

This plugin stores data submitted by your site visitors (form field values, uploaded files, and metadata such as submission timestamps). All data is stored locally in your WordPress database and media library. No data is transmitted to external servers. When a submission is permanently deleted, its linked uploaded files are deleted as well.

Users may request access to or deletion of their personal data. This plugin integrates with the WordPress Personal Data tools (**Tools › Erase Personal Data** and **Tools › Export Personal Data**).

For full details on what data is collected and how to manage it, refer to your site's privacy policy.

== Source Code ==

The full source code for this plugin is available at:
https://github.com/lybaba/xpressui-wordpress-bridge

= Bundled JavaScript runtime =

The file `runtime/xpressui-light-*.umd.js` is the compiled output of the XPressUI library
(free tier). The unminified TypeScript source files used to produce this bundle are included
in the `xpressui-src/` directory of this plugin.

To rebuild the runtime from those sources:

1. Navigate to the source directory: `cd xpressui-src`
2. Install dependencies: `npm install`
3. Build the light runtime: `npm run build:dist:light`
4. The output file is produced in `xpressui-src/dist/xpressui-light-*.umd.js`.


== Screenshots ==

1. The submission list with status badges, project filter, and row actions.
2. The submission detail view with payload fields, status workflow, and assignment panel.
3. The Manage Workflows page showing installed packages and project settings.
4. A workflow embedded in a page using the [xpressui] shortcode — inline rendering, no iframe.

== Changelog ==

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

= 1.0.21 =
The shortcode now renders the form inline — no iframe. If you have custom CSS targeting the `.xpressui-embed-wrapper iframe`, update your styles to target `.xpressui-inline-embed` instead.

= 1.0.0 =
Initial release — no upgrade steps required.
