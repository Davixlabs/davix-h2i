# Testing

| Layer | File | Needs | What it proves |
|---|---|---|---|
| Rules | `php bin/check-architecture.php` | PHP only | Folder rules, module isolation, the 43 agreed operations, operations exist in the contract |
| Integration | `wp eval-file .../tests/integration/run.php` | WordPress + database | Every operation and form field against the contract; chip pipelines; key store; option sanitizers; plan gate; the result pipeline (replace, backup, restore, copy, never-larger, new media, AI text policies); the queue (pause on quota, rate-limit wait, cancel, retry); uploads never call the network; History |
| End to end | `wp eval-file .../tests/e2e/sweep.php` | WordPress + a running Davix H2I with a customer key | Runs every operation that does not need an AI provider against the real service |

The integration test uses a fake client, restores your key, options and history afterwards, and can be run on any site. The end-to-end sweep creates media and History rows.

Local setup used during development: a throwaway WordPress and MariaDB in Docker, the plugin folder mounted, the plugin's hard-coded server address (`DAVIX_H2I_SERVER_URL`) pointing at the app on the host, and a customer key created with the app's own key service.
