# Changelog

## 1.4.1 (2026-10-11)
- Entries from the command line always show "(command line)"; scripts are named on the admin page column.
- Changes from one empty value to another (such as empty to "[]") are no longer listed.
- New products no longer list empty images or the default "Display Product Options In" value.
- Created records show as Save, and the Save filter matches created and changed records.
- Settings are checked on the server (retention, alert threshold and time window within sensible ranges).

## 1.4.0 (2026-10-10)
- Filters sit in a row under the column headings, as in the Adobe Commerce action log.
- Action names as in Adobe Commerce: Save, Mass Update, Mass Delete, Cancel, Hold and Unhold.
- Login Log actions read Login, Login Failed, Account Locked, Login Blocked (IP) and Logout.
- Security notices no longer send the admin to the dashboard after sign-in when secret URL keys are on.
- The Login Log Action filter lists its options.

## 1.3.2 (2026-10-08)
- Product listing, FAQ and user guide.

## 1.3.1 (2026-10-07)
- README states the right softaware/module-core version.

## 1.3.0 (2026-10-07)
- Keyword search on the Admin Actions and Login Log grids.
- Grid pages explain what is listed and warn when the log is switched off.
- Clearer detail page: equal old and new value columns, "(empty)" values and links to the record.
- Login Log status badges, tidier Active Sessions and shorter settings comments with defaults.
- "Allowed IP Addresses" shows your current IP address.

## 1.2.0 (2026-10-06)
- Requires softaware/module-core instead of module-base; menu and permissions move under "Softaware".

## 1.1.0 (2026-10-06)
- "Client IP Address From" setting (web server or Cloudflare, checked against Cloudflare's ranges).
- Changes to the Activity Log's own settings are kept by the clean-up.
- More secrets masked, and unknown usernames of failed sign-ins partly masked.

## 1.0.0 (2026-10-06)
- First release: admin action log with field-level changes, login log and active sessions.
- Security notifications by e-mail and admin inbox, and an admin IP allowlist with a CLI way back in.
- Grids with filters, export, retention clean-up, exclusions and CLI commands.
