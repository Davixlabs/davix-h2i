# Architecture

```
davix-h2i/
├─ davix-h2i.php            bootstrap only
├─ uninstall.php  readme.txt
├─ src/                     PHP, namespace DavixWP\
│  ├─ Core/                 Plugin, Context (registries), ModuleLoader, Options, Services, Installer, CoreSettings
│  ├─ Connection/           KeyStore, WpTransport, ClientFactory, ErrorMapper, ConnectionTester
│  ├─ Capabilities/         LimitsService, CapabilityGate (the plan decides what shows)
│  ├─ Operations/           Contract (reads the SDK contract), Definition, Executor
│  ├─ Jobs/                 Queue (table), Worker (cron)
│  ├─ Media/                AttachmentWriter, BackupStore, StateMarker, TextWriter, ResultFetcher, Selector, Guard
│  ├─ History/              Repository (the plugin-only history table)
│  ├─ Admin/                Shell, Assets (only place that enqueues), Drawer, Ui, Components/OpPanel
│  ├─ Rest/                 Api helper, CoreRoutes
│  ├─ Cli/                  BaseCommand, Commands
│  ├─ Support/              Logger
│  └─ Modules/<Name>/       one folder per feature (manifest + <Name>Module.php + its pages, routes, hooks)
├─ assets/
│  ├─ css/core, css/kit, css/modules/<module>
│  ├─ js/core, js/kit, js/modules/<module>, js/editor
│  └─ image/logo           the same logo files the app uses
├─ vendor/davix-h2i-sdk/    the PHP SDK, exactly as the app publishes it
├─ bin/                     update-sdk.js, check-architecture.php
├─ tests/                   integration (fake client) and e2e (live service)
└─ docs/
```

## Modules

`Dashboard, Account, Images, Analyze, Ai, Create, Render, Pdf, Documents, Generators, Activity, History, Library, Settings, Help`. A module extends `Core\Module` and in `register( Context $c )` adds what it owns: operations, tabs, sub-tabs, drawer tabs, settings sections, REST routes, CLI commands, assets and hooks. The core never names a module; it only reads the Context. A module may use the core services but never another module (a sub-tab can be added to another module's tab by id, through the registry).

## Where each kind of work lives

A module registers the same action in up to three places: the **Media Library sidebar** (`add_drawer_tab`, kind `ops`: the actions of the module that fit every chosen file; the Image tab is the exception, it shows the chips), the **Automatic** rules (`add_auto_action`: the actions that may run on a new upload) and, for actions that start from nothing, a sub-tab of the **Create** tab (`add_subtab( 'create', ... )`, added by id, so no module knows another).

`Core\AutoRules` is the one engine for automatic work: a single option (`davix_h2i_auto`, a list of rules), a single `wp_generate_attachment_metadata` hook, and `Rest\AutoRoutes` + `Admin\Components\AutoPanel` + `assets/js/core/auto.js` for the screen. A rule is an action id, file types, a smallest size, whether files this plugin made are included, replace-or-copy, and the action's own settings. Several image steps in one rule become one pipeline call. Rules of earlier versions (a switch per module) are moved into rules once (`AutoRules::maybe_migrate`).

## One request, end to end

1. A screen, the drawer, an upload hook, a bulk run or WP-CLI asks `Operations\Executor` to run an operation for an attachment.
2. `CapabilityGate` checks the plan (data from `GET /v1/limits` and `/v1/ai/capabilities`, cached). A locked operation never reaches the network.
3. The call goes through the SDK client (`ClientFactory`, with `WpTransport`), with an idempotency key so a retry is never charged twice.
4. The result becomes WordPress content: replace (with backup), copy, new media, or text written by policy (`Media/*`). Optimization never keeps a larger file.
5. One row is written to History (`History\Repository`). Only the Executor writes it, which is why History shows only what the plugin did.

Uploads only add a row to the queue (`Jobs\Queue`). `Jobs\Worker` runs from WP-Cron, honours rate limits, pauses on a used-up allowance, and can hand large jobs to the service (`async`) and poll them.

## Rules that are checked

`php bin/check-architecture.php` fails when: CSS or JS is outside `assets/css` or `assets/js`; a logo is outside `assets/image/logo`; a module uses another module; the core names a module; inline `<style>` or `<script>` is in PHP; a module lists an operation that is not in the SDK contract; or the set of operations differs from the agreed 43.

## Data

| Where | What |
|---|---|
| Options `davix_h2i_<section>` | connection, runtime, auto (the rules), images and pdf (what happens to a replaced file), ai, render (never autoloaded) |
| Option `davix_h2i_key` | the encrypted key (libsodium) |
| Table `davix_h2i_jobs` | the queue |
| Table `davix_h2i_history` | the plugin-only history |
| Post meta `_davix_h2i_state`, `_backup`, `_generated`, `_source`, `_tags`, `_seo`, `_flags` | what was done to an attachment |
| Uploads `davix-h2i/` | backups and the debug log (protected) |
| Transients | plan data, usage, library counts |
