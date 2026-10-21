# Admin Activity Log for Magento 2: FAQ

**What does the module record?**
Changes made in the Magento admin (create, update and delete with the old and new value of each field), every admin
sign-in attempt and sign-out, and the admin sessions that are active right now.

**Which records are covered?**
Products, categories (including moves in the category tree), CMS pages and blocks, customers, orders, invoices,
shipments, credit memos, order and document comments, cart price rules, catalog price rules, configuration, admin
users, admin roles and role permissions. Grid mass actions, the product "Update attributes" action and imports under
System > Data Transfer > Import are logged too.

**Are changes made outside the admin logged?**
Configuration changes made with `bin/magento config:set` are. Changes through the REST or SOAP API, cron jobs,
command-line imports and direct SQL are not. Records that Magento or other extensions write with direct SQL (for
example some stock updates) only appear as the mass action that started them.

**Are passwords and API keys stored in the log?**
No. Passwords, tokens, API, private and licence keys, card fields, hashes, encrypted configuration fields and
configuration paths Magento declares sensitive are stored as `******`, so you still see that they changed. A failed
sign-in with an unknown username that looks like a password is stored partly masked.

**Does it slow down the admin?**
Tracking only runs in the admin area. The diff is built from data Magento has already loaded, the entries are kept in
memory and written with one multi-row insert at the end of the request, and nothing is written if the database
transaction is rolled back. The storefront is not affected.

**How long are entries kept?**
90 days by default. A daily cron job deletes older entries in batches; set 0 to keep everything. Changes to the
Activity Log's own settings are always kept, so shortening the retention or switching logging off stays on record.

**Can I leave some records or settings out?**
Yes. Under Exclusions choose entity types that are never logged, and list configuration paths (with `*` wildcards,
for example `design/*`) that are never logged.

**Which alerts are there?**
A number of failed sign-ins from one IP address within a number of minutes (default 5 within 15), lockouts of an
admin account, and sign-ins from an IP address or country the admin has not used before. Alerts go to the admin
notification inbox and, if you enter recipients, by email. The new IP and new country alert can also go to the admin
user's own email address.

**How does the country detection work?**
From Cloudflare's `CF-IPCountry` header. New country alerts and the country column therefore need the store to be
behind Cloudflare with IP geolocation switched on.

**Can I end another admin's session?**
Yes, under Active Sessions with Terminate (single or mass action). The admin is signed out on their next click. Your
own session cannot be terminated; use Sign Out instead.

**How does the IP allowlist work, and can I lock myself out?**
When the allowlist is on, only the listed IP addresses and CIDR ranges (IPv4 and IPv6) can sign in and use the admin.
The module refuses to save a list that does not include your current IP address. If you are locked out anyway, for
example after your IP address changed, run `bin/magento commercegears:activity-log:allowlist --disable` on the server.

**My store is behind Cloudflare and the log shows Cloudflare IP addresses. What should I do?**
Either configure the web server to pass the real visitor IP (for example nginx `real_ip` with Cloudflare's ranges), or
set "Client IP Address From" to Cloudflare. The `CF-Connecting-IP` header is then trusted only on requests that come
from Cloudflare's published address ranges.

**Can I export the log?**
Yes. The Admin Actions and Login Log grids export to CSV and XML, with the filters you have set.

**Why do older entries show "Save" and "Mass Update" after updating to 1.4.0?**
Since 1.4.0 the action names follow the Adobe Commerce action log ("Save" instead of "Create" and "Update", "Mass Update" and
"Mass Delete" instead of "Mass action", "Login" and "Logout" in the Login Log). `bin/magento setup:upgrade` converts
the stored mass action and order Cancel / Hold / Unhold entries, so filters and exports use the same names for old and
new entries.

**Which Magento and PHP versions are supported?**
Magento Open Source and Adobe Commerce 2.4.6 to 2.4.9, PHP 8.2 to 8.5 (Magento 2.4.6: PHP 8.2). The module is admin only.

**Is Admin Activity Log free?**
Yes. Admin Activity Log is free to download from commercegears.com and is installed with Composer like our other modules.
It needs `commercegears/module-core`, which Composer installs automatically.
