# Softaware Admin Activity Log

`softaware/module-admin-activity-log` (Magento module `Softaware_ActivityLog`)

Who changed what in the Magento admin, who signed in from where, and who is signed in right now.

## Features

- **Admin action log** with a field-level diff (old → new) for create, update and delete of:
  products, categories (including moves in the category tree), CMS pages and blocks, customers, orders,
  invoices, shipments, credit memos, order/document comments, cart price rules, catalog price rules,
  configuration (path, scope, old and new value), admin users, admin roles and role permissions.
- **Mass actions** (`mass*` grid actions, product "Update attributes") are logged with the selection and the
  parameters; records saved by the mass action are logged individually and linked through the request ID.
- **Imports** (System > Data Transfer > Import): entity, behaviour, processed rows, created/updated/deleted counts.
- **Configuration changes from the command line** (`bin/magento config:set`) are logged as well (admin page "cli: config:set").
- **Secrets are masked**: passwords, tokens, API/private/licence keys, card fields, hashes, encrypted/obscured
  config fields, config paths Magento declares sensitive and config paths that look like keys or passwords are
  stored as `******` (you still see that they changed). Nested mass-action parameters are masked the same way.
  A failed sign-in with an unknown username that could be a password (7+ characters with letters and digits, not
  an email address) is stored partly masked, e.g. `Se*** (masked, may be a password)`.
- **Login log**: successful and failed sign-ins (with reason: unknown username, wrong password, inactive account,
  CAPTCHA, ...), account lockouts, sign-ins refused by the IP allowlist, sign-outs; IP address, browser,
  and country from Cloudflare's `CF-IPCountry` header when present.
- **Active sessions**: every signed-in admin session (user, IP, browser, country, sign-in time, last activity)
  with **Terminate** (single and mass action). The terminated admin is signed out on their next click.
- **Security notifications** by email and in the admin notification inbox: N failed sign-ins from one IP within
  M minutes, admin account lockouts, sign-in from a new IP address, sign-in from a new country.
- **Admin IP allowlist** (single IPs and CIDR ranges, IPv4/IPv6). Refuses to save a list that does not include
  your own IP, and has a CLI way back in.
- **Grids** with filters (admin user, entity type, action, date range, IP, entity, admin page), **detail view**
  of each change with links to the record, **CSV/XML export**.
- **Retention**: entries older than N days are deleted by a daily cron job (in batches). Changes to the Activity
  Log's own settings are always kept, so shortening the retention or switching logging off stays on record.
- **Client IP behind Cloudflare**: "Client IP Address From" can be set to Cloudflare. The `CF-Connecting-IP` header
  (and `CF-IPCountry`) is then trusted only on requests that come from Cloudflare's published address ranges.
- **Exclusions**: entity types and configuration paths (with `*` wildcards) that are never logged.
- **Low overhead**: tracking runs in the admin area only; the diff is built from data Magento already loaded
  (one extra load only for repositories that build a fresh model, such as customers); entries are kept in memory,
  dropped if the database transaction rolls back, and written with one multi-row insert at the end of the request.

## Where in the admin

- Softaware > Admin Activity Log > Admin Actions / Login Log / Active Sessions / Settings
- Settings: Stores > Configuration > Softaware > Admin Activity Log (`softaware_activitylog/*`, default scope)
- ACL: Softaware > Admin Activity Log > View Admin Actions / View Login Log / Manage Active Sessions / Settings

## Configuration

| Path | Default | |
|---|---|---|
| `softaware_activitylog/general/enabled` | 1 | Master switch (logging, alerts and allowlist) |
| `softaware_activitylog/general/log_actions` | 1 | Admin action log |
| `softaware_activitylog/general/log_logins` | 1 | Login log (needed for alerts) |
| `softaware_activitylog/general/ip_source` | server | `server` (REMOTE_ADDR) or `cloudflare` (CF-Connecting-IP from Cloudflare ranges only) |
| `softaware_activitylog/general/retention_days` | 90 | 0 = keep forever |
| `softaware_activitylog/exclude/entity_types` | – | Comma-separated entity type codes |
| `softaware_activitylog/exclude/config_paths` | – | One pattern per line, `*` wildcard |
| `softaware_activitylog/notifications/*` | on | Recipients, sender, threshold (5) and window (15 min), new IP/country, templates |
| `softaware_activitylog/allowlist/enabled`, `.../ips` | off | Admin IP allowlist |

Email templates: "Admin Activity Log: Failed Sign-ins / Lockout" and "Admin Activity Log: Sign-in From New IP or Country".

## CLI

```
bin/magento softaware:activity-log:clean [--days=N]           # delete entries older than the retention period (or N days)
bin/magento softaware:activity-log:allowlist                  # show the admin IP allowlist
bin/magento softaware:activity-log:allowlist --add=203.0.113.4
bin/magento softaware:activity-log:allowlist --disable        # way back in after a lock-out
```

## Extending

Other modules can track their own entities by adding an item to the `types` argument of
`Softaware\ActivityLog\Model\EntityTypeResolver` in their `di.xml` (keys: `class`, `label`, `label_fields`,
`extra_fields`, `ignore_fields`).

## Compatibility

Magento Open Source / Adobe Commerce 2.4.7–2.4.9, PHP 8.2–8.5. Requires `softaware/module-base` ^1.2 and
Magento_Security (admin session records). No storefront code.

## Limitations

- Only changes made in the admin panel (and `config:set` on the CLI) are logged. Changes through the REST/SOAP
  API, cron jobs, imports from the command line or direct SQL are not.
- Changes written with direct SQL by Magento or third-party modules (for example stock updates, some
  "Update attributes" paths that run through the message queue) only appear as the mass action that started them.
- Very large field values are truncated to 5,000 characters in the diff.
- By default the client IP is taken from `REMOTE_ADDR`. Behind Cloudflare either configure the web server to pass the
  real IP (nginx `real_ip` with Cloudflare's ranges) or set "Client IP Address From" to Cloudflare. Behind other
  proxies or CDNs configure the web server; otherwise the allowlist and IP alerts see the proxy IP. The built-in list
  of Cloudflare ranges is updated with the module.
- Entries are written in groups of up to 500 during long requests (bulk saves in the admin area, CLI runs in the
  admin area), so memory stays flat.
- "New country" alerts need Cloudflare with IP geolocation (`CF-IPCountry`).
- Terminating a session takes effect on the admin's next request (Magento_Security behaviour).
