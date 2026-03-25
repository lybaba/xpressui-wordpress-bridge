# Workflow Manifest Spec

Date: 2026-03-25

## Goal

Define a single `manifest.json` contract that:

- stays aligned with the current XPressUI exporter
- lets the WordPress bridge validate packages safely
- clearly separates `light` and `pro` capabilities
- becomes the base contract for the future native WordPress renderer

This is intended as a pragmatic `v2` evolution of the current export manifest, not a brand-new unrelated format.

## Design Principles

- One manifest only.
- Backward-friendly evolution from the current `console.export/v1`.
- The WordPress plugin must be able to validate compatibility without executing the workflow.
- `free` vs `pro` must be explicit in data, not inferred from runtime failures.
- Package validation must rely on declarative metadata first.

## Current Export Manifest

Today the exporter already emits a `manifest.json` with fields such as:

- `schemaVersion`
- `exportId`
- `projectId`
- `projectSlug`
- `projectName`
- `generatedAt`
- `xpressui`
- `artifacts`
- `checksums`
- `workflow`
- `integrationTargets`
- `meta`

That existing shape should remain the base.

## Proposed Manifest Evolution

Recommended schema version:

```json
"schemaVersion": "console.export/v2"
```

The existing fields stay, and the following sections are added.

## New Top-Level Sections

### `runtimeRequirements`

Declares which runtime tier is required to render the workflow correctly.

```json
"runtimeRequirements": {
  "tier": "light",
  "minimumPluginVersion": "1.1.0",
  "minimumRuntimeVersion": "0.90.0"
}
```

Rules:

- `tier` is one of `light` or `pro`
- `minimumPluginVersion` is optional but recommended
- `minimumRuntimeVersion` is optional but recommended

### `capabilities`

Declares the renderer and workflow capabilities needed by this package.

```json
"capabilities": {
  "components": [
    "text",
    "email",
    "textarea",
    "file"
  ],
  "features": [
    "multi-step",
    "email-notifications"
  ],
  "themeFeatures": [
    "basic-colors",
    "basic-radius"
  ]
}
```

Rules:

- `components` lists field/component types used by the workflow
- `features` lists workflow-level features
- `themeFeatures` lists visual/theming requirements

### `wordpressCompatibility`

Declares how the WordPress bridge should treat the package.

```json
"wordpressCompatibility": {
  "bridgeMode": "native-render",
  "shortcodeMode": "config-driven",
  "supportsBundledDemo": false,
  "requiresLicense": false
}
```

Rules:

- `bridgeMode` should become `native-render` for the new architecture
- `shortcodeMode` should become `config-driven`
- `requiresLicense` is `true` for pro-only workflows

## Recommended Full Example

```json
{
  "schemaVersion": "console.export/v2",
  "exportId": "api-project_123",
  "projectId": "project_123",
  "projectSlug": "loan-application",
  "projectName": "Loan Application",
  "generatedAt": "2026-03-25T10:15:00Z",
  "xpressui": {
    "version": "0.90.0",
    "target": "wordpress"
  },
  "runtimeRequirements": {
    "tier": "light",
    "minimumPluginVersion": "1.1.0",
    "minimumRuntimeVersion": "0.90.0"
  },
  "capabilities": {
    "components": [
      "text",
      "email",
      "tel",
      "textarea",
      "select",
      "radio-buttons",
      "checkboxes",
      "file"
    ],
    "features": [
      "multi-step",
      "submission-storage",
      "email-notifications"
    ],
    "themeFeatures": [
      "basic-colors",
      "basic-radius",
      "basic-sections"
    ]
  },
  "workflow": {
    "submissionMode": "multi-step-submit",
    "providerMode": "wordpress-bridge",
    "resumeSupport": "disabled",
    "documentHandling": "basic-upload",
    "submissionEndpoint": "__XPRESSUI_WORDPRESS_REST_URL__"
  },
  "wordpressCompatibility": {
    "bridgeMode": "native-render",
    "shortcodeMode": "config-driven",
    "supportsBundledDemo": false,
    "requiresLicense": false
  },
  "artifacts": {
    "config": "form.config.json",
    "project": "project.document.json",
    "readme": "README.md",
    "assetsDir": "assets"
  },
  "checksums": {
    "algorithm": "fnv1a-64",
    "files": {
      "form.config.json": "1234567890abcdef"
    }
  },
  "meta": {
    "contractVersion": 2,
    "projectSchemaVersion": 3,
    "projectStatus": "published",
    "assetCount": 2,
    "stepCount": 3,
    "fieldCount": 12
  }
}
```

## Light vs Pro Capability Matrix

### `light` tier

Recommended allowed `components`:

- `text`
- `email`
- `tel`
- `textarea`
- `select`
- `radio-buttons`
- `checkboxes`
- `file`
- `hidden`
- `html`

Recommended allowed `features`:

- `single-step`
- `multi-step`
- `submission-storage`
- `email-notifications`
- `basic-upload`
- `redirect-url`

Recommended allowed `themeFeatures`:

- `basic-colors`
- `basic-radius`
- `basic-sections`
- `basic-typography`

### `pro` tier

Recommended pro-only `components`:

- `product-list`
- `quiz`
- `select-image`
- `select-one`
- `select-multiple`
- `image-gallery`
- advanced interactive widgets

Recommended pro-only `features`:

- `conditional-logic`
- `advanced-branching`
- `crm-integration`
- `webhooks`
- `analytics`
- `resume-support`
- `advanced-document-handling`

Recommended pro-only `themeFeatures`:

- `advanced-backgrounds`
- `advanced-media-layouts`
- `premium-layout-presets`
- `advanced-choice-density`

## Validation Rules in the WordPress Plugin

The plugin should validate a package in this order:

1. `manifest.json` exists
2. `schemaVersion` is supported
3. `projectSlug` is a valid slug
4. required artifact files exist
5. `runtimeRequirements.tier` is supported by the installed plugin
6. all declared `components` are supported by the installed runtime tier
7. all declared `features` are supported
8. all declared `themeFeatures` are supported
9. if `requiresLicense = true`, require the pro plugin and a valid license

## Recommended Failure Modes

### In free plugin

If a package requires `pro`:

- reject installation with a clear admin error
- or install but mark as incompatible and never render it

Recommended admin message:

`This workflow requires XPressUI Pro capabilities and cannot run in the free bridge plugin.`

### In pro plugin

If the package requires `pro` but license is invalid:

- do not register the pro renderer extensions
- keep the site stable
- show an admin notice

Recommended admin message:

`This workflow requires an active XPressUI Pro license.`

## Packaging Rules for the New Architecture

For the future native-render model, the WordPress plugin should eventually require:

- `manifest.json`
- `form.config.json`
- optional `project.document.json`
- optional `assets/`

And should no longer require:

- `index.html`
- external runtime app files as the execution entrypoint

## Suggested Exporter Changes

In `services/api/app/exporter.py`:

1. keep the current manifest fields
2. add `runtimeRequirements`
3. add `capabilities`
4. add `wordpressCompatibility`
5. compute `components` from the actual field types
6. compute `features` from workflow config
7. compute `themeFeatures` from theme usage

## Suggested WordPress Plugin Changes

In the bridge plugin:

1. add manifest loading and validation helpers
2. validate package compatibility during ZIP installation
3. persist manifest metadata for installed workflows
4. use manifest data in the shortcode renderer
5. use manifest data to decide free vs pro compatibility

## Most Profitable Next Step

After this spec, the most profitable implementation step is:

1. add manifest generation in the exporter
2. add manifest validation in the WordPress plugin ZIP installer

That gives immediate value without waiting for the full native renderer refactor.
