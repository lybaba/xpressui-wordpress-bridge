# Generated PHP Templates

This directory hosts the PHP templates compiled from the canonical Jinja export templates under:

- `services/api/app/templates/export/`

The current chain is:

1. Jinja source stays the source of truth.
2. `python3 scripts/compile-export-templates-to-php.py` recompiles the export shell templates.
3. The generated PHP files land in `templates/generated/`.
4. `templates/runtime.php` provides the shared helpers used by those generated templates.
5. `templates/render-compiled-template.php` is a CLI helper used by parity tests.

Validation:

- `services/api/.env/bin/python -m unittest services/api/app/test_jinja_php_parity.py`
- `python3 scripts/generate-wordpress-pot.py`

Current status:

- The compiler is intended for the export HTML shell templates and partials.
- The free WordPress bridge only ships the `light` template subset that matches the supported field/runtime perimeter.
- Pro-only field partials are intentionally excluded from this generated tree.
- `export/generated/*.j2` are intentionally excluded from this pipeline for now.
- The automated test currently enforces generated-template sync and normalized HTML parity between Jinja and PHP renders for light-compatible workflows.
- Byte-for-byte whitespace parity is still a follow-up item.
- WordPress translation extraction is generated from plugin PHP sources and compiled templates into `languages/xpressui-bridge.pot`.
