# PHP Templates

This directory contains the PHP rendering templates used by the plugin.

- `runtime.php` — shared template helpers (stringify, context access, etc.).
- `form-fragment.php` — inline shortcode embed renderer (manually maintained).
- `core/` — PHP renderers for the form shell, fields, and partials.

`runtime.php` resolves the template root at `templates/core/` by default.
Set the `XPRESSUI_PHP_TEMPLATES_ROOT` environment variable to override the root for testing.
