# Marketplace search delivery

Direction: the supplied search-bar screenshot defines the joined destination/date/party row. DESIGN.md supplies DM Sans, white surfaces, blue actions, and restrained borders. ENERGY 2 / RHYTHM 3 / MOTION 1 is retained. Existing icons identify location, dates, guests, and search; existing fictional hotel imagery is reused.

## Implemented and checked

- Destination search matches published hotel names and cities without case sensitivity on PostgreSQL and SQLite.
- Dates cover 1–90 nights. Inventory excludes out-of-service rooms and rooms booked on any requested night. Group filtering checks the requested room count and the capacity of that many available rooms, counting children as occupants and requiring one adult per room.
- Search values carry into hotel links. Multi-room results and hotel pages explain that each room is reserved separately. Starting prices are per room per night before tax.
- Five additional fictional hotels were inserted into the existing PostgreSQL database without replacing existing records: 10 hotels, 180 rooms, 140 reservations, 140 payments. Re-running the seeder preserves property edits and staff passwords.
- Default SQLite suite: 33 tests, 135 assertions passed. Separate PostgreSQL suite: 33 tests, 135 assertions passed. PHP syntax, JavaScript syntax, Blade compilation, and whitespace checks passed.
- Browser checks: guest counters, date changes and night summary, case-insensitive Bandung search with three adults/one child/two rooms, two matching hotels, selected dates/group reaching the hotel page, empty results, and Escape returning focus to the summary.
- Responsive checks at 390 and 1280 CSS pixels: stacked mobile controls and joined desktop row. Mobile selector remains within the viewport; document scroll width equals client width. Temporary viewport overrides were reset.

## Delivery gate for this change

- No new visual assets, packages, generated marks, external fonts, decorative gradients, or invented customer claims.
- Search controls use visible labels, native inputs/details, named buttons, keyboard focus indicators, and disabled stepper limits. Inputs remain editable when JavaScript is unavailable.
- Search is the first actionable region below navigation; only opened panels receive an elevation shadow. No animation was added.
- Query validation, empty results, reset links, and actual availability filtering are implemented. Demo collection is labeled fictional.
- Screenshot: `.runtime/marketplace-search-desktop.png` (local preview, excluded from source control).

Scope: multi-room filtering is supported; a multi-room checkout transaction is not implemented. Concurrent booking behavior was not tested by this change.
