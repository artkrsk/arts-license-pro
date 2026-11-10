# Arts License Pro

License management for Arts WordPress plugins and themes. Ships as **two coupled artifacts from
one source tree**: a PHP library (`Arts\LicensePro\`, Composer package `arts/license-pro`) that a
host plugin/theme instantiates, and a WordPress-admin React app (`@arts/license-pro`) that renders
the license UI. The PHP side handles storage, the remote licensing API, and WordPress update
integration; the JS side is the admin panel that talks back through `admin-ajax.php`.

## Mental model

- A host plugin/theme calls `new \Arts\LicensePro\Plugin( $config )`. `Plugin` wires the pieces:
  `Manager` (license state + DI root) → `API` (remote calls) + `Storage` (wp_options), plus
  `Frontend` (admin assets), `AJAX` (admin-ajax handlers), and either `PluginUpdates` **or**
  `ThemeUpdates` (mutually exclusive, chosen by config).
- The remote API is EDD-style Software Licensing: responses use a `license` field that `API`
  remaps to an internal `status` (`status === 'valid'` is the single source of truth for validity).
- PHP → JS handoff is one localized blob. Each `Frontend` instance registers into the
  `arts/license-pro/instances_data` filter; on enqueue the filter is fired once and localized as
  `window.artsLicenseProInstances` (keyed by product slug). JS `auto-mount` reads it and mounts
  React components into placeholder DOM nodes; user actions go back via `LicenseAPIAdapter` →
  admin-ajax → `AJAX` → `Manager`.
- Multiple products on one screen share a single enqueued bundle and a single instances blob.

## Layout

- `src/php/` — the library, namespace `Arts\LicensePro\`.
  - `Plugin.php` — public entry point / façade.
  - `Includes/` — `Manager`, `API`, `Storage`, `PluginUpdates`, `ThemeUpdates`, `Frontend`, `AJAX`.
  - `Includes/Interfaces/` — `ManagerInterface`, `APIInterface`, `StorageInterface` (DI seams;
    `Manager` accepts injected `Storage`/`API`).
- `src/ts/` — admin React app.
  - `index.ts` — bootstrap: auto-mount on DOM ready + public exports.
  - `core/` — `api/` (`LicenseAPIAdapter`), `components/` (`LicensePanel`, `LicenseForm`,
    `ProBadge`), `hooks/` (`useLicense`), `auto-mount.ts`, `interfaces/`, `types/`.
  - `global.d.ts` — declares the WordPress-provided `wp` global.
- `src/styles/` — Sass, compiled to `dist/index.css`.
- `__build__/` — git submodule (`arts-public-builder`), the esbuild-based build runner.
- `dist/` and `src/php/libraries/` — build output (gitignored where noted below).

## Commands

- PHP quality gates: `composer check` (runs `phpcs` + `phpstan`), or `composer phpcs` /
  `composer phpstan` individually; `composer fix` runs phpcbf. Standards: `phpcs.xml`,
  `phpstan.neon` (both baseline clean).
- Build / watch: `pnpm build` / `pnpm dev` (delegate to `node __build__/src/index.js`). The build
  externalizes React and every `@wordpress/*` dep to `wp.*` globals — nothing is bundled.
- TS typecheck: `pnpm exec tsc --noEmit` (config has pre-existing deprecation warnings for
  `baseUrl` / `moduleResolution` / `downlevelIteration`).
- JS tests: Vitest (`vitest.config.js`). Run it directly (`pnpm exec vitest`) — see Gotchas.

## Public PHP API (`Arts\LicensePro\Plugin`)

- `__construct( array $config )` — keys: `product_slug` (required); `product_type`
  (`plugin`|`theme`); `api_base_url` (default `https://artemsemkin.com/wp-json`); `purchase_url`,
  `support_url`, `renew_support_url`; `plugin_file` + `update_uri` (plugin updates) **or**
  `theme_slug` (theme updates); `icons`, `banners` (plugin update UI). Construction wires
  everything; on bad config it records a `WP_Error` and no-ops.
- `is_license_active(): bool`
- `render_license_panel( array $args = [], bool $return = false ): string|void` — emits a
  `.arts-license-pro-license-panel-root` mount node.
- `render_pro_badge( array $args = [], bool $return = false ): string|void` — emits a
  `.arts-license-pro-badge-root` mount node; `$args` is JSON-encoded into `data-config`.
- `get_manager(): Manager` (throws `RuntimeException` if uninitialized) · `get_errors(): ?WP_Error`.

## WordPress hook / event contracts (exact names)

- **Filter** `arts/license-pro/instances_data` — `Frontend::add_instance_data` registers on it;
  `Frontend::enqueue_scripts` fires it to build the localized data. This is the PHP↔JS contract.
- **AJAX actions** (require `manage_options` + nonce): `wp_ajax_{product_slug}_license_activate`,
  `_deactivate`, `_check`.
- **Plugin updates** (`PluginUpdates`): `plugins_api` (99), `update_plugins_{update_uri}` (99),
  `in_plugin_update_message-{plugin_id}` (10); cache cleared on
  `update_option_/delete_option_{product_slug}_license_data` and `_license_key`.
- **Theme updates** (`ThemeUpdates`): `pre_set_site_transient_update_themes`,
  `delete_site_transient_update_themes`, `load-update-core.php`, `upgrader_process_complete` (999);
  same option-change cache-clear hooks.

## Frozen identifiers

- Text domain: `arts-license-pro`.
- Script + style handle: `arts-license-pro-admin`; UMD global: `ArtsLicensePro`; localized JS
  global: `artsLicenseProInstances`.
- Script deps (WordPress-provided): `react`, `react-dom`, `wp-element`, `wp-components`, `wp-i18n`,
  `wp-date`.
- Options: `{product_slug}_license_key`, `{product_slug}_license_data`. Nonce action:
  `{product_slug}_license_nonce`.
- Update transients: `{plugin_slug}_update_data` (1h), `{theme_slug}-update-response` (1d).
- Admin assets: `src/php/libraries/arts-license-pro/` → `index.umd.js`, `index.css`, enqueued via
  `plugins_url()` relative to `Plugin.php`.
- Remote API shape: `POST {api_base_url}/{activate|deactivate|check}/{product_slug}/{product_type}`
  and `.../update/{product_slug}/{product_type}`, both with `?key=&url=`.

## Gotchas / invariants

- **WordPress's React, not bundled.** JS reads globals via `wp.element` (`createElement`, `render`,
  `useState`), `wp.components`, `wp.i18n`, `wp.date`. The `@wordpress/*` entries in `package.json`
  are types/externals; the build maps them to `wp.*`. Never `import` React for runtime.
- **Key stored apart from data.** `Storage` keeps the license key in `_key` and everything else in
  `_data`. `get_data()` re-injects `license_key` into the returned array; `set_data()` strips it
  first. Don't expect `license_key` inside the stored `_data` option.
- **Best-effort remote, authoritative local.** `deactivate()` clears local data even if the remote
  call fails; `check()` falls back to stored data when the API errors.
- **`fetch_update_info()` casts to object but keeps `sections` an array** — WordPress expects the
  update object's `sections` as an array, so the `(object)` cast is deliberately shallow.
- **Assets enqueue once** via a static guard even with several product instances on a screen.
- **Phantom npm scripts.** `pnpm test` / `test:*` / `test:svn` call shell harnesses under
  `__tests__/` and `scripts/` that are not in this repo; run Vitest directly instead.
- **Generated, gitignored dirs:** `src/php/libraries/*` (the admin UMD bundle) and `/dist` are
  build products — don't hand-edit them; edit `src/` and rebuild.

## Dependencies

- PHP: runtime requires only `php >=8.0` (no runtime Composer deps). Dev: PHPStan
  (+ phpstan-wordpress), PHP_CodeSniffer + WPCS, CaptainHook, composer-normalize.
- JS: no bundled runtime deps — relies on WordPress-provided React and `@wordpress/*` globals.
  Build/test tooling: esbuild (via `__build__`), Vitest, Cypress, ESLint, Prettier, Sass.
