# XPressUI WordPress Bridge — Pro Extension Guide

This document describes how to build `xpressui-wordpress-bridge-pro`, the companion
plugin that unlocks pro-only features in the free bridge plugin.

---

## Plugin identity

| Field | Value |
|---|---|
| Plugin slug | `xpressui-wordpress-bridge-pro` |
| Depends on | `xpressui-wordpress-bridge` (free, must be active) |
| Text domain | `xpressui-bridge-pro` |

---

## Activation contract

The pro plugin must hook into the free plugin's filters **on `plugins_loaded`** (or
earlier). The free plugin checks these filters at runtime; late hooks will not be seen.

```php
add_action( 'plugins_loaded', function () {
    // Tell the free bridge that the pro extension is active.
    add_filter( 'xpressui_bridge_is_pro_extension_active', '__return_true' );

    // Optional: validate a stored license key and return true if valid.
    add_filter( 'xpressui_bridge_has_valid_pro_license', function ( $is_valid, $settings ) {
        $key = $settings['licenseKey'] ?? '';
        return my_pro_validate_license( $key );
    }, 10, 2 );
} );
```

If the pro plugin is installed but `xpressui_bridge_is_pro_extension_active` is not
hooked, the free plugin behaves as if pro is absent.

---

## Feature gates (current — v1.0.6)

| Feature | Free | Pro |
|---|---|---|
| Bundled default workflow (document-intake) | ✅ | ✅ |
| View & manage submissions | ✅ | ✅ |
| Project settings (notify email, redirect URL) | ✅ | ✅ |
| **Install custom workflow packs (.zip)** | ❌ | ✅ |
| *(future)* Multiple workflows | ❌ | ✅ |
| *(future)* Advanced submission handling | ❌ | ✅ |

The upload form in wp-admin is hidden for the free tier and replaced by an upgrade CTA.
Server-side: the zip upload POST handler also checks `xpressui_is_pro_extension_active()`
and returns a `WP_Error` if the pro extension is absent — bypassing the UI is blocked.

---

## Helper functions available to the pro plugin

These are public functions defined by the free bridge that the pro plugin can call:

```php
// Check tiers
xpressui_is_pro_extension_active() : bool
xpressui_has_valid_pro_license()    : bool
xpressui_get_current_runtime_tier() : string  // 'pro' | 'light'

// Workflow management
xpressui_get_installed_workflow_slugs()  : array
xpressui_is_installed_workflow( $slug )  : bool
xpressui_is_bundled_workflow( $slug )    : bool
xpressui_get_workflow_package_dir( $slug ) : string
xpressui_get_workflow_package_url( $slug ) : string
xpressui_get_workflows_base_dir()        : string
xpressui_get_workflows_base_url()        : string

// Rendering
xpressui_render_compiled_workflow_shell_html( $slug ) : string
xpressui_can_render_compiled_workflow_shell( $slug )  : bool

// License settings
xpressui_get_license_settings()              : array
xpressui_update_license_settings( array $s ) : void
xpressui_get_masked_license_key()            : string
```

---

## Constants available to the pro plugin

```php
XPRESSUI_BRIDGE_VERSION          // e.g. '1.0.6'
XPRESSUI_BRIDGE_DIR              // absolute path to the free plugin dir, trailing slash
XPRESSUI_BRIDGE_URL              // URL to the free plugin dir, trailing slash
XPRESSUI_BRIDGE_BUNDLED_WORKFLOWS_DIR  // absolute path to default-workflows/
```

---

## Upgrade CTA URL

Currently set to: `https://lybaba.github.io/iakpress-console/`

Update this URL in `includes/admin-pages.php` when the public product page is ready.

---

## Dependency check (recommended)

Add this to the pro plugin's activation hook to prevent activation without the free bridge:

```php
register_activation_hook( __FILE__, function () {
    if ( ! function_exists( 'xpressui_is_pro_extension_active' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die(
            esc_html__( 'XPressUI Pro requires the XPressUI WordPress Bridge plugin to be installed and active.', 'xpressui-bridge-pro' ),
            esc_html__( 'Plugin dependency missing', 'xpressui-bridge-pro' ),
            [ 'back_link' => true ]
        );
    }
} );
```
