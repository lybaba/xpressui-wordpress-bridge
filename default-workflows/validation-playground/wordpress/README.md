# Validation Playground WordPress pack

This package gives you a ready-to-publish workflow for testing field validation in WordPress.

## What you get
- A multi-step workflow covering the most useful field types
- Upload fields for file type and file size validation
- Choice fields for min/max selection testing
- A normal WordPress inbox flow after submit

## Recommended use
Use this starter to verify:
- text validation (`min`, `max`, `pattern`)
- number and price validation (`min`, `max`, `step`)
- multi-choice validation (`min choices`, `max choices`)
- file validation (`accept`, `max file size`)
- standard required/optional behavior

## Install
1. Install and activate `XPressUI Bridge`.
2. Upload the `validation-playground` workflow package in `XPressUI -> Workflows`.
3. Create a page and embed:

```text
[xpressui id="validation-playground"]
```

Optional redirect:

```text
[xpressui id="validation-playground" redirect="https://yoursite.com/thank-you/"]
```

### Included with Pro

If `XPressUI WordPress Bridge PRO` is active, this workflow can also be bundled automatically
and appears under:

- `XPressUI -> Workflows -> Included Pro Tools`

## Included steps
1. Contact
2. Validation
3. Documents
4. Review

## Included field coverage
- text
- email
- tel
- radio-buttons
- number
- price
- date
- time
- checkboxes
- select-multiple
- url
- upload-image
- file
- textarea
