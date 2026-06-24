=== IntakeFlow Forms – Multi-Step Form Builder, File Upload & Client Intake ===
Contributors: iakpressteam
Tags: form builder, contact form, multi-step form, file upload, intake form
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.1.3
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Multi-step form builder for WordPress: contact forms, file upload, client intake & document collection, with a built-in submission inbox.

== Description ==

**IntakeFlow** is a multi-step form builder for WordPress for contact forms, file uploads, client intake and document-collection workflows — with a built-in submission inbox so you manage everything inside wp-admin.

**Already using Contact Form 7 or Gravity Forms?** Import and convert any existing CF7 or Gravity Forms form into an IntakeFlow workflow in a few clicks (field types, choices and required rules are preserved), then embed it with a shortcode.

Embed any form anywhere using the `[xpressui]` shortcode (or the Gutenberg block / Elementor widget). The form renders natively inside your page — no iframe, no external dependencies at runtime. Submissions are stored as private posts in a dedicated wp-admin inbox, directly in your site's database.

If you need advanced field types, direct Console Sync, or cloud workflow management, those are available by connecting the plugin to your IntakeFlow account at intakeflow.dev.

= Key features =

* **Import from Contact Form 7 & Gravity Forms** — migrate your existing forms in a few clicks; field types, choices and required rules are preserved.
* **Works with the block editor & page builders** — embed via the `[xpressui]` shortcode, the native Gutenberg block, or the Elementor widget.
* **One-click installation** — upload the exported `.zip` file from the IntakeFlow console directly inside wp-admin.
* **Shortcode embed** — `[xpressui id="your-project-slug"]` works in any page, post, or block-editor paragraph block. The form renders inline, inheriting your theme's page layout.
* **Submission inbox** — all submissions land in a private wp-admin post list with status badges, filtering by project, status, and assignee, and detailed review metaboxes.
* **Status workflow** — mark submissions *New*, *In review*, or *Done* from the list or the detail view. Every status change is recorded in a per-submission history log.
* **Team assignment** — assign any WordPress user to a submission. The *My Queue* page shows each reviewer their personal backlog at a glance.
* **Email notifications** — configure a notification address per project and receive a plain-text summary email the moment a new submission arrives.
* **Post-submit redirect** — optionally redirect the visitor to a thank-you page after a successful submission. Configured per project from wp-admin.
* **File uploads** — uploaded files are stored as WordPress media attachments and linked back to their submission.
* **REST API endpoint** — submissions are received via a standard WordPress REST route (`POST /wp-json/xpressui/v1/submit`). No extra server configuration required.
* **Bundled runtime** — the XPressUI standard runtime is bundled inside the plugin. No JavaScript is loaded from the uploads directory or external CDNs.
* **Product & booking catalogs** *(with a Console account)* — product and service / time-slot booking catalogs render server-side in WordPress (SEO-friendly, no iframe) with a built-in checkout step; orders are recorded in your submission inbox.

= Who is this for? =

Businesses and developers who use the IntakeFlow console to build document-intake or multi-step application forms and want to manage the collected data inside their existing WordPress environment without an external SaaS inbox.

= The Ultimate Alternative for Team Intake =

Unlike generic form builders (like Contact Form 7, WPForms, or Gravity Forms), IntakeFlow is built specifically for **client intake** and **document collection**. 
* **No paywall on team inbox:** Assign submissions to specific WordPress users without upgrading to expensive Enterprise plans (unlike JotForm or Typeform).
* **No bloated iframes:** Forms render natively in clean, fast HTML/JS, inheriting your active theme's styling.
* **100% GDPR-compliant:** Your submissions and uploaded files are stored entirely in your local database. No data is stored on third-party servers.

== Installation ==

1. Download the plugin `.zip` from the WordPress Plugin Directory or from [intakeflow.dev](https://intakeflow.dev/).
2. In your WordPress dashboard, go to **Plugins › Add New › Upload Plugin**, then select the downloaded `.zip` file and click **Install Now**.
3. Click **Activate Plugin**.
4. In your WordPress dashboard, go to **Submissions › Workflows** and enter your API Token in the **Console Sync** section (generate it in your Console under Profile → API Tokens).
5. Click **Load from Console** and then **Sync** next to the workflow you want to import.
6. Insert `[xpressui id="your-project-slug"]` in any page or post to embed the form.

== Frequently Asked Questions ==

= Is IntakeFlow free? =

Yes. The WordPress plugin is free and open-source. You can build, import and embed forms and review submissions in your wp-admin inbox at no cost. A free or paid account at intakeflow.dev is optional and only needed for cloud sync and advanced field types (signatures, document scanning, time-slot booking). Free accounts include up to 100 cloud-synced submissions per month.

= Can I import my Contact Form 7 or Gravity Forms forms? =

Yes. Go to **Submissions › Workflows › Import**, pick any Contact Form 7 or Gravity Forms form, and convert it to an IntakeFlow workflow. Field types (text, email, phone, number, date, URL, file, select, checkbox, radio), choices and required rules are preserved, and you get a ready-to-embed shortcode. It works with or without a Console connection.

= Can I take bookings or sell products? =

Yes, with an IntakeFlow Console account. Product and service / time-slot booking catalogs render natively in your WordPress pages (server-side, SEO-friendly — no iframe) with a checkout step. Customers can pay by bank transfer / mobile money (uploading a payment proof) or by card (processed by the IntakeFlow Console via Stripe), and each order is recorded in your wp-admin submission inbox.

= How do I import my workflows from the Console? =

Connect your WordPress site to the IntakeFlow Console using your API Token in the **Console Sync** section of the Workflows admin page. You can then sync all your custom workflows with a single click.

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

Workflow packs can be re-synced without deleting submissions. If you delete and reinstall the plugin itself, submissions are preserved by default. To permanently remove submission data during uninstall, define the `XPRESSUI_BRIDGE_DELETE_SUBMISSIONS_ON_UNINSTALL` constant and set it to `true` before deleting the plugin.

= Does the plugin call any external API at runtime? =

No. Once a workflow package is installed, the plugin operates entirely within your WordPress site. The IntakeFlow console at intakeflow.dev is only used to design and export packages — it is not contacted during form rendering or submission processing.

== External Services ==

This plugin can optionally connect to the IntakeFlow SaaS platform (hosted at intakeflow.dev) to enable real-time cloud synchronization, advanced field types, and centralized workflow management.

When connected to IntakeFlow:
* The plugin makes outbound HTTP requests to `https://app.intakeflow.dev` (or the custom console URL you configure under Settings) to verify your subscription status, sync project schemas, and download workflow packages.
* Outbound requests include an API Token (`X-Api-Token`) generated from your IntakeFlow dashboard so the service can identify your account.
* When you connect an account, the plugin sends the token to validate it and to look up the owner of the account.
* When you use the Form Importer with an account connected, the structure of the imported Contact Form 7 / Gravity Forms form (field names, labels and types — not visitor submissions) is sent to the console to create the corresponding workflow, which is then downloaded back to this site.
* No visitor or submission data is transmitted to the IntakeFlow console unless specifically configured by the administrator for cloud backup or webhook routing.
* When you use a product / booking catalog with checkout, the order details (cart or chosen slot, and the payment proof or card payment) are sent to the IntakeFlow Console, which owns payment and order status; a copy of the order is also recorded locally in your wp-admin inbox.

A connection is required to sync custom workflows. The bundled starter workflow functions fully offline without a connection.

Use of the IntakeFlow service is governed by its terms and privacy policy:
* Terms of Service: https://intakeflow.dev/terms
* Privacy Policy: https://intakeflow.dev/privacy

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
5. Product catalog storefront rendered natively in WordPress — server-side, SEO-friendly, no iframe.
6. Checkout with the manual payment step: bank-transfer details, a generated payment reference, and proof-of-payment upload.
7. Service / time-slot booking — the chosen slot is carried into the checkout step.

== Changelog ==

= 1.1.3 =
* Fix: Imported forms (e.g. Contact Form 7) no longer disappear from the Workflows list after connecting the site to the IntakeFlow Console.
* Improve: The Sync Logs "Email delivery log" now records admin notification emails for trial / unconnected forms too, so a test submission visibly confirms email is going out, with a sent counter on the Submissions screen.
* Improve: Streamlined first-run onboarding guidance for new sites.
* Improve: Removed noisy, unreliable status badges from the Workflows list pending a more accurate sync signal.
* Fix: Admin scripts now cache-bust on update so changes load reliably.

= 1.1.2 =
* Improve: Plugin Directory listing — documented the product & service/time-slot booking catalogs with native WordPress checkout (new FAQ + feature), refreshed the banner and screenshots, and added screenshots of the catalog storefront, manual payment checkout, and booking.

= 1.1.1 =
* Fix: The manual payment step at checkout now works end-to-end — proof-of-payment upload (drag & drop / browse), the generated payment reference, and the IBAN/BIC/reference copy buttons. The 1.1.0 build bundled an older runtime that broke these; this release ships the corrected runtime.

= 1.1.0 =
* New: Manual payment step at checkout — bank-transfer details with a server-generated payment reference and one-click copy for IBAN / BIC / reference, plus a proof-of-payment upload (parity with the IntakeFlow Console checkout).
* New: Send admin notification and submitter confirmation emails via the IntakeFlow Console (SaaS) for better deliverability (SPF/DKIM/DMARC), with a per-workflow "Send emails via SaaS" toggle. Submission delivery now shows the mail recipient and a "SaaS cloud" status.
* New: Embed a hosted link from the Gutenberg block or the Elementor / page-builder widget via the project + link selector.
* Improve: Catalog orders are now recorded on the WordPress submission (cart items, total, payment method) even when paying by file upload.
* Fix: Payment-proof copy / info icons now render correctly (SVG viewBox preserved through sanitization).
* Fix: Submission JSON metadata (payload, status history, event log, uploaded files) is stored without slash corruption.
* Fix: Remove orphaned hosted-link configurations left under a previous project after a link moves between projects.
* Fix: Resolve all Plugin Check / PHPCS nonce findings with proper fixes (no suppressions).

= 1.0.99 =
* Fix: Resolve a fatal error ("Path cannot be empty") that could occur when rendering an [xpressui] shortcode, caused by a missing catalog file path after the 1.0.98 filesystem refactor. Workflows without a fronted catalog now render normally again.

= 1.0.98 =
* Improve: Form Importer now keeps phone, number, date and URL fields from Contact Form 7 and Gravity Forms as their proper input types instead of converting them to plain text.
* Improve: Importer admin scripts and styles are now enqueued instead of being printed inline.
* Improve: Workflow package files are written through the WordPress Filesystem API.
* Improve: External service usage (the IntakeFlow Console) is documented with Terms of Service and Privacy Policy links.

= 1.0.97 =
* New: Headless product & time-slot booking catalogs render server-side in WordPress (SEO + faster load) on hosted-link pages.
* New: Booking summary shown inside the checkout form for time-slot/booking catalogs.
* New: "Sync All Workflows" with a live progress bar; hosted-links synchronization & webhooks.
* New: Embedded console with secure single sign-on plus "Edit on IntakeFlow" deep links.
* Improve: Many catalog/checkout layout fixes on WordPress themes — grid/card overlap, spacing and alignment, mobile stacking, sticky-header overlap, empty-cart checkout, consistent top spacing.
* Improve: Payment-proof (bank transfer / RIB) field; option to disable archive file types in validation.
* Fix: Resolved all Plugin Check / PHPCS findings with proper fixes (no suppressions).

= 1.0.96 =
* Use secure transient database storage for admin notices instead of raw query parameters to pass Plugin Check.

= 1.0.95 =
* Rename plugin to IntakeFlow – Client Intake, Multi-Step Forms & Secure Document Collection.
* Remove manual ZIP upload feature in favor of secure API Console Sync.

= 1.0.94 =
* SaaS migration to unified standalone wordpress plugin with API token connection.

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
