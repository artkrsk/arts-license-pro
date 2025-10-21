# Task

## Scope

This is a composer library that should provide license panel, public API that will provide information if user has input a valid license and it's active. Also it should be capable to manage updates for the paid (Pro) version of a plugin which will use this license panel.

## Stories

- I want this library to be an easy drop in for my existing plugin. The first plugin I'm going to use it is this plugin I'm currently working on `/Users/art/Projects/EDD Github Storage` and I decided to extract license panel into a separate package rather then keep in here.
- I want this library to support multiple plugins. E.g. I can should be able to instanciate it `new ArtsLicensePro()` from two "Pro" versions of completely non-related my plugins and their updates/licenses could be managed separately.
- I want this library to have a public function that will answer if the current license for the plugin is valid and active. Check the current example in `/Users/art/Projects/EDD Github Storage/src/php/Plugin.php`. I want `->is_license_active()` method.
- The license panel could be rendered literally anywhere. Our library should provide `->render_license_panel()` method. The actual rendering will happen through React on frontend. Backend simply prints the mounting root point. Please refer to `render_license_field` in `/Users/art/Projects/EDD Github Storage/src/php/Plugin.php` and `/Users/art/Projects/EDD Github Storage/src/php/Managers/Settings.php` files.
- Similarly to the license panel rendering we should provide a method to render our "Get Pro" badge. This could be either a link or simple `span` element. You could refer it from here: `/Users/art/Projects/EDD Github Storage/src/ts/core/components/ProFeatureTeaser.tsx`. Similarly backend renders the mounting point - React renders the actual element. Later we should export that component that I'll be requiring in TS, not from PHP.

## Remote API

- Inspect `/Users/art/Projects/ArtsStore/packages/arts-store-license-server` package and rest routes: `/Users/art/Projects/ArtsStore/packages/arts-store-license-server/src/php/Managers/REST.php`. Also let him inspect this documentation file: `/Users/art/Projects/memory-bank/ArtsStore/arts-store-license-server.md`
- Inspect `/Users/art/Projects/ArtsStore/packages/arts-store-update-server` package and rest routes: `/Users/art/Projects/ArtsStore/packages/arts-store-update-server/src/php/Managers/REST.php`. Also let him inspect this documentation file: `/Users/art/Projects/memory-bank/ArtsStore/arts-store-update-server.md`
- Figure out how API routes are dynamically formed `wp-json/edd/v1/check/edd-github-storage-pro/plugin`.

In researches focus specifically on what data and in what format is transmitted during 'check', 'activate', 'update', 'deactivate' API calls. Make sure you know proper data formats on successfull, unsuccessfull, error responses.

## Existing/Related Implementations

- Research legacy implementation in the ArtsLicenseManager `/Users/art/Projects/Framework/packages/ArtsLicenseManager` - it's designed for themes not for plugins.
- Research ArtsPluginsUpdater `/Users/art/Projects/Framework/packages/ArtsPluginsUpdater` package - it's designed to update plugins and 3rd-party bundled plugins provided with a premium theme.
- Research `/Users/art/Projects/Asli/DEV/src/wp/plugin/asli_core.php` on how I'm using ArtsPluginsUpdater (don't worry about bundled plugins - this is out of scope for the current project).

## References

### API Examples

#### Check Endpoint

```bash
curl -X POST 'https://artemsemkin.com/wp-json/edd/v1/check/edd-github-storage-pro/plugin?key=ABCD1234-EFGH5678-IJKL9012-MNOP3456&url=localhost'
```

```json
{
  "success": true,
  "license": "valid",
  "message": "License key is valid",
  "is_local": true,
  "should_prompt_email": false,
  "email_link_state": "real",
  "masked_email": "**@a**********.com",
  "order_history_url": "https:\/\/artemsemkin.com\/checkout\/order-history\/",
  "expires": "lifetime",
  "date_purchased": "2025-10-20 16:32:08",
  "date_supported_until": "2026-04-20 16:32:08",
  "date_updates_provided_until": "lifetime",
  "license_limit": 1,
  "site_count": 0,
  "activations_left": 1
}
```

#### Activate Endpoint

```bash
curl -X POST 'https://artemsemkin.com/wp-json/edd/v1/activate/edd-github-storage-pro/plugin?key=ABCD1234-EFGH5678-IJKL9012-MNOP3456&url=localhost'
```

```json
{
  "success": true,
  "license": "valid",
  "message": "License has been activated successfully",
  "should_prompt_email": false,
  "email_link_state": "real",
  "masked_email": "**@a**********.com",
  "order_history_url": "https:\/\/artemsemkin.com\/checkout\/order-history\/",
  "expires": "lifetime",
  "date_purchased": "2025-10-20 16:32:08",
  "date_supported_until": "2026-04-20 16:32:08",
  "date_updates_provided_until": "lifetime",
  "is_local": true,
  "license_limit": 1,
  "site_count": 0,
  "activations_left": 1
}
```

### License Panel Implementation from a Project

#### Existing React Codebase

You can literally copy & integrate this codebase into the current project. But feel free to refactor it instead of coyping, make it more robust, simple, reliable.

- `/Users/art/Projects/EDD Github Storage/src/ts/core/license-manager/LicenseActions.tsx`
- `/Users/art/Projects/EDD Github Storage/src/ts/core/license-manager/LicenseForm.tsx`
- `/Users/art/Projects/EDD Github Storage/src/ts/core/license-manager/LicensePanel.tsx`
- `/Users/art/Projects/EDD Github Storage/src/ts/core/license-manager/LicenseStatus.tsx`
- `/Users/art/Projects/EDD Github Storage/src/ts/core/license-manager/useLicense.ts`
- `/Users/art/Projects/EDD Github Storage/src/ts/core/components/ProFeatureTeaser.tsx`

#### Existing Styles

- `/Users/art/Projects/EDD Github Storage/src/styles/components/_license-panel.sass`
- `/Users/art/Projects/EDD Github Storage/src/styles/components/_pro-teaser.sass`

## Workflow Preferences

- Make **MINIMAL CHANGES** to existing patterns and structures.
- In **ANY** implementation **LESS** means **BETTER** while **MORE** means **WORSE**. **YAGNI** is a core principle.
- Preserve existing naming conventions and file organization.
- Follow project's established architecture and component patterns.
- Use existing utility functions and avoid duplicating functionality.
- **DON'T** create any test or demo files yourself unless explicitly asked for it.
- **DON'T** care about backward compatibility unless explicitly asked for it.
- **DON'T** create anything for **potential future use** explicitly asked for it.
- **DON'T** run `npm run build` or `npm run dev` yourself, I have 'dev' process running in background.
- In a TypeScript project you're allowed to run tsc after you've made changes to the codebase to check for the errors.
- In a TypeScript project if there is "/constants" folder - use it for holding CONSTANTS (caps lock).
- In a TypeScript project if there is "/interfaces" folder use it for interfaces, one interface = 1 file, prefix all interfaces with "I".
- In a TypeScript project if there is "/types" folder use it for types, one type = 1 file, prefix all types with "T".
- In a TypeScript project comments for public methods should be added on class interface (if it exists). The comment shouldn't duplicate what certain method returns or accepts since it should be obvious from the interface or type.
- In a TypeScript project comments for private methods should be short and concise, for example `/** Instantly set to target state without animation */`.
- In a TypeScript project always strive to document at interface/type level, keeping implementation clean.
- In a TypeScript project comments should always be enclosed in `/** ... */` so IDE can have proper hints on properties and methods.
- **ALWAYS use curly braces for if/for/while statements** even for one-liners. Prefer `if (condition) { return }` over `if (condition) return` for better readability.
- For BEM methology prefer "\_" as division symbol for the modifiers over "\-\-".
- **⚠️ CRITICAL WORKFLOW RULES:**
- - **Task Management:**
- - - **DO NOT start any task** until user explicitly confirms (e.g., "yes", "start", "go ahead")
- - - When working on a task → Set status to `in-progress`
- - - When task is complete → Set status to `review` (NOT `done`)
- - - After marking as review → Ping user: "Task X is ready for your review"
- - - **DO NOT proceed to next task** without explicit user confirmation
- - - Subtasks are OK - can complete all subtasks without individual confirmation
- - **Git Commits:**
- - - **DO NOT make git commits** unless user explicitly asks (e.g., "commit", "make commits", "clean working tree")
- - - Wait for explicit instructions before creating any commits
- - - Exception: User may give blanket permission like "commit as you go"
