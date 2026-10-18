# Updating the SDK

The plugin uses the Davix H2I PHP SDK **exactly as the app publishes it**. Nothing is copied by hand, scoped or edited, and it does not matter that the SDK covers more endpoints than the plugin uses.

```bash
node bin/update-sdk.js            # copies ../Davix-h2i-app/sdk/php into vendor/davix-h2i-sdk
node bin/update-sdk.js /path/to/sdk/php   # or from another folder
```

That replaces `vendor/davix-h2i-sdk` (source, bundled contract, resources) and writes its version into `vendor/davix-h2i-sdk/VERSION`. Then:

```bash
php bin/check-architecture.php    # fails if the new contract dropped an operation the plugin uses
wp eval-file wp-content/plugins/davix-h2i/tests/integration/run.php
```

What picks up the new SDK automatically: the plan-aware screens (they read `/v1/limits`), the option forms of every operation (built from the contract's field list), and the operation checks. New endpoints do not appear in the plugin until a module adds an operation for them (see developer.md).

If another plugin on the same site already loaded the Davix H2I SDK, that copy is used instead.
