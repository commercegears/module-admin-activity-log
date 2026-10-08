# Changelog

## 1.3.2 (2026-10-08)

### Added

- Product listing, FAQ and user guide (docs/).

## 1.3.1 (2026-10-07)

### Fixed

- README: the module requires `softaware/module-core` ^1.0 (the README said ^1.2; composer.json was right).

## 1.3.0 (2026-10-07)

### Added
- Keyword search on the Admin Actions grid (admin user, entity, entity ID, IP address, admin page, request ID) and the
  Login Log grid (username, IP address, reason, browser). Adds a full-text index to both log tables.
- Grid pages start with a one-line explanation (what is listed, how long entries are kept, session lifetime on Active
  Sessions) and a warning with a link to the settings when the matching log is switched off.
- "Allowed IP Addresses" shows your current IP address, so you can add it before switching the allowlist on.

### Changed
- Login Log: the result is shown as a coloured status badge (signed in, failed, locked out, blocked by allowlist).
- Admin Actions grid: Entity ID is hidden by default (still available under Columns) so the grid fits narrower
  screens; the link column is headed "Details" instead of a second "Action"; entries from the command line show
  "(command line)" instead of an empty admin user.
- Entry detail page: standard Back button; compact two-column summary; old and new values in equal columns that
  wrap long values and scroll very long ones (such as encoded files) inside the cell; empty values shown as
  "(empty)"; created records, mass actions and imports are listed as one value column ("Saved Values",
  "Mass Action Parameters", "Import Result") instead of under "New value" next to an empty "Old value" column;
  "Open Record" / "Open Configuration Section" links; a note when more than 100 related entries exist.
- Active Sessions: confirmation dialogs say when the admin is signed out; result messages say what happened;
  Session ID hidden by default; Country filter added; long browser strings wrap.
- Page titles match the menu ("Login Log", "Active Sessions"); labels in title case throughout.
- Settings: shorter field comments with defaults and examples, comments added to every field, email recipients
  validated, "Allowed IP Addresses" only shown when the allowlist is enabled; the General group is always open.

## 1.2.0 (2026-10-06)

### Changed

- Requires `softaware/module-core` instead of `softaware/module-base`. The admin menu and ACL now sit under `Softaware_Core::core` ("Softaware"); roles that had access keep it (migrated by module-core). After updating all SoftAware modules, `softaware/module-base` can be removed.

## 1.1.0 (2026-10-06)

### Added
- "Client IP Address From" setting: Web server (default, unchanged) or Cloudflare. With Cloudflare, `CF-Connecting-IP`
  and `CF-IPCountry` are used only on requests from Cloudflare's address ranges, so they cannot be faked by
  connecting to the server directly. The allowlist's own-IP check uses the IP source being saved.

### Changed
- Changes to the Activity Log's own settings are kept by the retention clean-up (tamper evidence).
- Log entries are written every 500 entries during long requests instead of only at the end.

### Security
- Unknown usernames of failed sign-ins that could be a password typed into the wrong field are stored partly masked
  (also in alert emails and admin notices).
- More secrets are masked: API/private/licence keys, passphrases and card fields; config fields of type
  obscure/password with a custom backend; config paths Magento declares sensitive; nested values in mass-action
  parameters (were stored as JSON in clear).

## 1.0.0

- Admin action log with field-level diff for catalog, CMS, customers, sales documents and comments, price rules,
  configuration, admin users, roles and permissions; mass actions and imports.
- Login log (success, failure with reason, lockout, blocked IP, sign-out) with IP, browser and country.
- Active admin sessions grid with terminate.
- Security notifications (failed sign-ins per IP, lockouts, new IP, new country) by email and admin inbox.
- Admin IP allowlist with CIDR support and CLI fallback.
- Grids with filters, detail view, CSV/XML export, retention cron, exclusions, CLI commands.
