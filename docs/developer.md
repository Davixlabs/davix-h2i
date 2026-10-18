# Developer guide

## Constants (wp-config.php)

| Constant | Meaning |
|---|---|
| `DAVIX_H2I_API_KEY` | The key, so it is not stored in the database |
| `DAVIX_H2I_SERVER_URL` | The service address. Hard-coded in `davix-h2i.php` (local Docker app for now; the live address at release) |

## Filters and actions

| Hook | Purpose |
|---|---|
| `davix_h2i_modules` (filter) | Return the module ids to load |
| `davix_h2i_operation_params` (filter) | Change the parameters of a call: `( $params, $operation_id, $request )` |
| `davix_h2i_base_url` (filter) | Change the service address |
| `davix_h2i_http_args` (filter) | Change the `wp_remote_request` arguments |
| `davix_h2i_operation_finished` (action) | After every operation: `( $outcome, $request )` |
| `davix_h2i_options_updated` (action) | After a settings section is saved |

## REST (namespace `davix-h2i/v2`)

Every route checks a capability (default `upload_files`; settings, connection and clearing need `manage_options`) and the REST nonce. Main routes: `run`, `estimate`, `bulk`, `bulk/scope`, `media/info`, `jobs*`, `history*`, `settings/{section}`, `connection/*`, `limits`, `library/*`, `drawer/form`, `images/run-chips`, `render/social-card`, `logs`.

## WP-CLI

```
wp davix verify | limits | queue [run|retry|resume|clear] | history
wp davix optimize <ids|all> [--queue]
wp davix alt-text <ids|all> [--queue]
```

## Add an operation

1. Make sure the endpoint is in the SDK contract.
2. In the owning module's `module.php` add its id to `operations`.
3. In the module's `register()` call `$c->add_operation( array( 'id' => ..., 'label' => ..., 'group' => ..., 'input' => 'file|optional|none', 'file_field' => ..., 'accepts' => array( 'image/' ), 'store' => 'replace|copy|new|text|none', 'result' => 'files|text|json', 'fields' => array( 'name', ... ) ) )`. Field names are looked up in the contract, so labels, types, enums, limits and defaults come for free.
4. Show it: add it to an `OpPanel::render( array( ... ) )` list. The form, plan gate, cost line, bulk run, queue, result handling and History all work without more code.
5. Add it to the agreed list in `bin/check-architecture.php` if it is a deliberate addition.

## Add a module

Create `src/Modules/<Name>/module.php` (manifest) and `<Name>Module.php` extending `DavixWP\Core\Module`; put its CSS in `assets/css/modules/<name>/` and JS in `assets/js/modules/<name>/` and register them with `$c->add_assets()`. Run `php bin/check-architecture.php`.
