# Changelog

## 1.4.1 (2026-10-11)
- Fix: changes from one empty value to another (such as empty to "[]") are no longer listed.
- Fix: new products no longer list empty images or the default "Display Product Options In" value.
- Fix: created records show as Save, and the Save filter matches created and changed records.
- Improvement: command line entries always show "(command line)"; scripts are named in the admin page column.
- Improvement: settings are checked when saved (retention, alert threshold and time window ranges).

## 1.4.0 (2026-10-10)
- Fix: security notices no longer send the admin to the dashboard after sign-in with secret URL keys on.
- Fix: the Login Log Action filter lists its options.
- Improvement: filters sit in a row under the column headings, as in the Adobe Commerce action log.
- Improvement: action names as in Adobe Commerce, and clearer Login Log action names.

## 1.3.2 (2026-10-08)
- New: product listing, FAQ and user guide.

## 1.3.1 (2026-10-07)
- Fix: the README states the right Softaware Core version.

## 1.3.0 (2026-10-07)
- New: keyword search on the Admin Actions and Login Log grids.
- New: "Allowed IP Addresses" shows your current IP address.
- Improvement: grid pages explain what is listed and warn when the log is switched off.
- Improvement: clearer detail page with equal value columns, "(empty)" values and links to the record.
- Improvement: Login Log status badges, tidier Active Sessions and shorter settings comments.

## 1.2.0 (2026-10-06)
- Improvement: requires Softaware Core instead of Softaware Base; menu and permissions move under "Softaware".

## 1.1.0 (2026-10-06)
- New: "Client IP Address From" setting (web server or Cloudflare, checked against Cloudflare's ranges).
- Fix: changes to the Activity Log's own settings are kept by the clean-up.
- Improvement: more secrets masked, and unknown usernames of failed sign-ins partly masked.

## 1.0.0 (2026-10-06)
- New: first release with an admin action log with field-level changes, login log and active sessions.
- New: security notifications by email and admin inbox, and an admin IP allowlist with a way back in.
- New: grids with filters, export, retention clean-up, exclusions and command line tools.
