# Changelog

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
