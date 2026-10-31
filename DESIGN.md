# Hotel Booking Management System: Reference Design Specification

## Direction and scope

The two supplied images define the target appearance for this project: a white hotel-management dashboard and a white, blue-accented product landing page. Match their composition, proportions, color relationships, typography, spacing, borders, shadows, icons, charts, and imagery treatment. This specification replaces the earlier forest-green, cream, serif, and botanical design direction.

This is a target design specification. Updating this document does not implement the redesign in the application. The existing Laravel, Blade, CSS, and JavaScript files remain the implementation starting point. Product setup and business rules remain documented in [README.md](README.md) and [docs/IMPLEMENTATION.md](docs/IMPLEMENTATION.md).

### Reference authority

| Reference | Source | What it governs |
| --- | --- | --- |
| Dashboard | [Dashboard reference](docs/design/references/dashboard-reference.png), 1600 × 1200 pixels | Workspace shell, sidebar, header, dashboard grid, cards, charts, table, room tiles, profile treatments |
| Landing page | [Landing reference](docs/design/references/landing-reference.png), 1600 × 1200 pixels | Marketing navigation, centered hero, dashboard preview, feature sections, services, audience panel, pricing |

The landing reference is a presentation composite: the opening page occupies the larger left area, while later sections appear at a smaller scale on the right. In the website, these sections form one vertical page. The composite is not a two-column website layout. Its top-right edge and bottom edge crop some content; unseen sections and interactions are not specified by the image.

Solid-fill colors below were sampled from the supplied PNG files. Dimensions are measured or estimated from visible edges at the reference scale. Font identity, original CSS, exact shadow parameters, responsive layouts, and interactive states cannot be recovered exactly from screenshots; those are explicitly defined as implementation targets below. The images remain the visual authority when a written estimate differs.

## Identity and visual character

The defining characteristics are near-black sans-serif typography, bright blue actions, white surfaces, cool neutral backgrounds, restrained borders, rounded rectangles, and diffuse shadows. The dashboard is dense but orderly. The landing page uses generous white space and large centered type around a product screenshot.

The reference wordmark reads “YowStay,” with the first portion black, “Stay” blue, and a small black hospitality silhouette to its left. This is reference branding. The current project name is Stay OS; apply the same visual treatment to the project identity unless a separate rename is requested. The signed-in dashboard begins with the current user's avatar, name, role, and disclosure chevron rather than a large logo block.

Visual intent: ENERGY 2/5, RHYTHM 3/5, MOTION 1/5. Blue provides the main focal accent; purple belongs to analytics; green and red describe meaningful data. Layout variation comes from the reference's different card sizes and alternating feature rows. Motion remains limited to interaction feedback because animation cannot be inferred from the static references.

## Color system

### Shared palette

| Token | Target value | Role and evidence |
| --- | --- | --- |
| `--canvas` | `#f6f6f6` | Sampled outer presentation background |
| `--surface` | `#ffffff` | Sampled sidebar, header, cards, and landing background |
| `--workspace` | `#fafafa` | Sampled dashboard content canvas |
| `--surface-subtle` | `#f5f5f5` | Sampled control and icon-well neutral |
| `--text` | `#000000` | Sampled primary type; use near-black antialiased rendering |
| `--text-secondary` | `#606166` | Approximation for descriptions and table details |
| `--text-muted` | `#8b8b8b` | Approximation for group labels and chart axes; verify contrast by use |
| `--border` | `#e0e0e0` | Approximation for thin card and shell separators |
| `--primary` | `#007bff` | Dominant sampled blue in landing buttons |
| `--primary-soft` | `#edf6ff` | Sampled selected navigation and pale room tiles |
| `--primary-hover` | `#006be0` | Derived interaction target, not a photographed state |
| `--chart-purple` | `#5100d6` | Dominant sampled check-in chart bars |
| `--chart-purple-soft` | `#f4edff` | Approximation for pale comparison bars |
| `--success` | `#008000` | Target positive metric color; screenshot greens vary with rendering |
| `--danger` | `#df3434` | Sampled notification-count red |
| `--audience-panel` | `#f0f7ff` | Sampled pale blue landing panel |
| `--audience-shape` | `#e9f3ff` | Sampled low-contrast background geometry |

### Room and chart colors

| Meaning | Target treatment |
| --- | --- |
| Pale room tile | `#edf6ff` |
| Need cleaning | `#75b8ff`, sampled solid fill |
| Dark selected / do-not-disturb tile | `#004a99`, dominant sampled solid fill |
| Booked tile | `#fff9ed` base, sampled; thin diagonal `#ffe7bc` stripes, approximate |
| Platform series | Dark green points, pale sage points, very pale green points, connected by fine vertical stems |
| Radar series | Three overlapping translucent light-to-mid blue areas with fine blue outlines |
| Positive delta | Green arrow plus percentage |
| Negative delta | Red downward arrow plus percentage |

Blue and purple have separate jobs. Primary navigation and actions use blue; the check-in histogram and selected billing cadence use purple. Green is a data accent rather than the page's brand color. Avoid broad blue-to-purple gradients. The visible blue button halo and soft blue audience-panel shapes have specific roles and should remain localized.

## Typography

Use one modern sans-serif family across both surfaces. The reference has rounded, open letterforms and restrained weights. Exact font identity is unconfirmed. Use the project's existing DM Sans as the first candidate, with `system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif` fallbacks; compare its glyphs and text widths against the reference before finalizing. Inter is a fallback candidate only if that comparison is closer. Remove the previous Georgia display treatment and the separate Manrope heading treatment for this direction.

| Text role | Target size / weight | Treatment |
| --- | --- | --- |
| Landing hero | 40–44px / 600 at the large left panel's displayed scale | Two centered lines, tight leading around 1.12, tracking around -0.035em |
| Landing major heading | 30–34px / 600 at the same scale | Centered for section introductions; left aligned in feature rows |
| Landing supporting text | 13–15px / 400 | Gray, line-height 1.5–1.65 |
| Dashboard page title | 24px / 600 | Black, left aligned |
| Dashboard card heading | 20px / 600 | Black, with a gray description immediately below |
| Dashboard KPI | 26px / 600 | Compact number with smaller delta beside or below it |
| Dashboard navigation | 16px / 400–500 | Relaxed rows; selected item is blue |
| Dashboard body | 14–16px / 400 | Regular rather than bold by default |
| Dashboard details / axes | 11–13px / 400 | Gray, subordinate to values |
| Navigation section label | 14px / 400 | Uppercase gray labels exactly where the dashboard reference groups navigation |
| Buttons | 12–14px / 500 | Sentence or title case matching the reference |

These are screenshot-scale targets. The smaller landing continuation is reduced in the composite; do not reproduce its tiny rendered text as actual website font sizes. Preserve proportions at a normal readable scale. Align numeric table columns consistently and use tabular numerals for frequently changing metrics.

## Geometry, borders, and elevation

| Element | Target |
| --- | --- |
| Dashboard outer frame | 1440 × 1024px at x=80, y=88 in the 1600 × 1200 reference |
| Outer frame radius | Approximately 16–18px; clip shell backgrounds to its corners |
| Sidebar | 328px wide within the reference frame; white with a 1px right divider |
| Header | 88px tall; white with a 1px bottom divider |
| Main dashboard padding | 24px |
| Major grid gaps | 24px horizontally and vertically |
| Dashboard cards | White, 1px light-gray border, approximately 12px radius |
| Sidebar selected row | 280 × 48px, approximately 14px radius |
| Room tiles | Approximately 75 × 60px, 8px radius, 8px horizontal / 16px vertical gaps |
| Standard controls | 8–10px radius; light border or neutral fill |
| Small metrics / staff row surfaces | 10–12px radius with selective soft elevation |
| Landing buttons | About 32px tall at the displayed left-panel scale; 8px radius |
| Landing section spacing | Roughly 80–120px at the displayed left-panel scale |

The dashboard's large exterior shadow falls below the frame and disperses widely. A starting approximation is `0 32px 64px rgba(0,0,0,.10)`. Most dashboard cards rely on borders. The search field, selected table row, small floating metrics, and chart tooltip use lighter diffuse shadows, approximately `0 6px 24px rgba(0,0,0,.04)`.

Landing primary buttons have a localized blue shadow, approximately `0 8px 24px rgba(0,123,255,.22)`. Feature screenshot assemblies have very soft gray elevation. These shadow values are reconstruction targets, not extracted original CSS.

## Dashboard composition

### Shell and header

At the 1600 × 1200 reference size, the white application frame runs from x=80 to x=1520 and y=88 to y=1112. The main content begins at x=408. The page title sits at x≈432, y≈124. A centered rounded search field occupies the middle of the header, with a search icon on the left and microphone icon on the right. The right side contains circular moon and notification controls, a short divider, and a light-gray date chip with a calendar icon.

Keep the header compact. The reference has no large welcome banner or row of four full-width metrics above the cards. The desktop frame is a presentation target; it can fill the available viewport on smaller screens without retaining the surrounding mockup margin.

### Sidebar

The user block occupies the first 88px: circular photographic avatar, two-line name and role, and a chevron aligned toward the right edge. Internal horizontal padding is approximately 24px.

Match the visible grouping and generous vertical spacing:

- Overview: pale blue selected background, blue filled home icon, blue text.
- DAILY OPERATION: Reservations, Guests, Rooms, Housekeeping. Reservations has a small red count capsule aligned right.
- ACCOUNTING: Expense Tracking, Revenue Management.
- SYSTEM OPTION: a gray group label with open space below in the supplied dashboard.
- OTHERS: Settings and Log out anchored near the bottom.

Icons are small, fine-stroke black or gray outlines, approximately 18–20px. Use a consistent SVG stroke weight around 1.25–1.5px. The active home icon is filled. Do not turn all navigation items into colorful icon squares. Maintain the existing role permissions and property selection capability; adapt their controls to this white shell. Reference-only navigation labels do not establish implemented modules.

### Exact desktop grid

Coordinates below refer to the original dashboard image. Inside the content region, the two main columns are each 520px wide, separated by 24px.

| Region | Position and size | Content |
| --- | --- | --- |
| Available-room KPI | x=432, y=200, 153 × 130px | Small gray icon well, gray label, large count, green delta |
| Check-out KPI | x=432, y=346, 153 × 130px | Same rhythm, light border, outlined icon well |
| Check-in chart | x=601, y=200, 351 × 276px | Label and KPI at top left, Month/Year segmented control top right, purple bar chart |
| Booking by Platform | x=976, y=200, 520 × 276px | Title, subtitle, vertical dot/stem visualization, three-series legend |
| Staff | x=432, y=500, 520 × 356px | Heading, All staff dropdown, four small summaries, three employee rows |
| Room Status | x=976, y=500, 520 × 356px | Heading, subtitle, 6 × 3 tile grid, legend |
| Weekly Visitors | x=432, y=880, 371 × 208px | Blue radar chart left; headline, total, small callout and legend right |
| Staff profile | x=819, y=878, 133 × 210px | Small theme switch, employee name and role, portrait cropped at bottom |
| Booking List | x=976, y=880, 520 × 208px | Heading, subtitle, five columns, highlighted first row |

Equivalent structure:

```text
[Available room] [       Check in       ] [      Booking by Platform      ]
[Check out     ] [                      ] [                               ]
[                 Staff                ] [          Room Status          ]
[       Weekly Visitors       ] [Person] [          Booking List         ]
```

Preserve the asymmetric first row: the two stacked KPIs belong beside the check-in chart. Preserve the profile card beside the radar chart. A uniform grid of identical cards would lose the reference composition.

### Chart and widget details

**Check-in chart:** Four rounded purple foreground bars sit over taller pale-lavender comparison bars, labeled Jan through Apr. The chart has a 0–25 vertical scale in increments of five, faint dashed horizontal guides, and no enclosing axis box. The selected month label is darker and heavier. The captured hover state has a white rounded tooltip with date, two colored series bullets, and values. The Month/Year switch sits on a pale gray rounded track; the selected segment is white with a small shadow.

**Booking by Platform:** The title is left aligned with a subtitle below and a vertical three-dot control at the upper right. Approximately fourteen fine vertical stems carry three green-toned dots at different heights. The baseline fades gently into the white background. Booking.com, Expedia, and Hotels.com labels appear in a single bottom legend with small brand marks. This is the chart form to reproduce; a standard line graph or donut changes the reference.

**Staff:** The four summaries are Total, Cleaned, Pending, and Progress. Each has a small label, larger value, and a small icon or miniature green striped progress graphic. Three employee rows use small square portraits with rounded corners, name and job role, room totals, and start/end times. The center row has a subtle floating white surface and shadow. The top-right filter is a small bordered dropdown.

**Room Status:** Eighteen blank tiles form six columns and three rows. The first row alternates pale blue, cream-striped, mid-blue, cream-striped, pale blue, cream-striped. The second row is mid-blue, pale blue, mid-blue, dark blue with a white room number, pale blue, mid-blue. The third row is pale blue, mid-blue, cream-striped, pale blue, cream-striped, pale blue. The captured dark tile reads 110. The bottom legend reads Do not disturb, Need cleaning, and Booked. This is a visual reference: operational room numbers and status assignments must come from real data.

**Weekly Visitors:** A seven-axis radar chart uses weekday labels and three translucent blue polygons. To its right are a title, large visitor count with a smaller unit, a pale blue encouragement chip, and a stacked three-item legend. Keep the chart fine-lined and translucent rather than saturated and opaque.

**Profile:** A narrow white card has a compact moon/sun segmented control at top, name and gray role, a large person cutout anchored to the bottom, and a small circular lower-right navigation control. Use authorized portrait assets or clear placeholders during implementation. The reference does not establish a working dark palette.

**Booking List:** Columns are Name, Room No., Room, Check in, and Check out, with tiny sort indicators. Use a clean header without a dark or strongly tinted strip. The first visible row has a white rounded highlight and soft shadow; the next row is quieter gray text. Align dates and room information consistently. The card has a vertical three-dot menu at top right.

## Landing-page composition

### Navigation

Use a white header with a constrained centered content width. In the large left presentation, the brand begins at x≈108, y≈103; the rightmost action ends at x≈938. The header has generous surrounding white space, no heavy divider, and no colored navigation bar.

The reference arrangement is brand left; Home, Features with a small chevron, Blog, Pricing, and Contact in the center; bordered Sign Up and blue Book a Demo on the right. Menu text is small black sans-serif with substantial horizontal gaps. The primary button has a soft blue halo and white text. Preserve this arrangement and visual hierarchy; actual destinations must exist before they are made interactive.

### Hero

The hero is centered and product-led. Its visible sequence is:

1. A compact white floating capsule containing a pale-purple icon well and a short benefit statement.
2. A large two-line headline. The reference reads “Streamline Hotel Operations” followed by “with a Smart Dashboard.” Only “Smart Dashboard” is bright blue.
3. A centered single-line gray description at desktop width.
4. Adjacent calls to action: solid blue Get Started and white bordered Get a Demo.
5. A small “Works with:” label followed by four compact colored partner marks.
6. A large layered dashboard preview directly below.

At the displayed left-panel scale, the benefit capsule is near y≈184, the headline begins around y≈235, the description sits near y≈340, buttons near y≈379, and partner marks near y≈435. The headline spans roughly 560px. The left panel's visible page content is about 912px wide; a 1200–1280px maximum content width is an implementation starting point for a full-width page, not a measured original container.

Keep the headline centered, with its original two-line rhythm at the comparison width. The reference's blue phrase, benefit capsule, and two-button arrangement are intentional requirements from the supplied design.

### Dashboard showcase and proof row

The product preview begins around x≈120, y≈535 in the large left panel. Two pale, offset dashboard sheets peek out behind the front panel, beginning higher around y≈495. The main panel has rounded top corners and a diffuse shadow. Its lower edge fades into the white page through a white gradient mask; the reference does not show the entire dashboard as a hard-edged rectangle.

Use the same dashboard visual system inside the landing preview. A preview from the old green interface would break the intended match. The three layers are a product presentation effect, not multiple functioning dashboards.

Below the preview, four evenly spaced proof metrics use large black figures and smaller gray captions. The visible reference figures are +30%, 98%, 5M+, and 50B. They describe the source artwork and are not verified claims about this project. Preserve the four-column typographic arrangement for a reference mockup; published values require actual product evidence or a visible demonstration label.

### Advantage and feature sections

The next visible heading reads “The YowStay Advantage,” with a centered gray supporting sentence and large vertical separation from the metric row. The reference crop only shows the top of the next cards. Do not invent their hidden content as if it were supplied.

The smaller right-hand continuation shows alternating two-column feature rows: a UI screenshot assembly on one side and an icon, heading, short paragraph, and small blue Learn More button on the other. The uppermost row is partially cropped and includes a lost-item form. The next complete row is “Revenue Insights & Financial Performance Tracking,” with copy on the left and a check-in/KPI screenshot composition on a pale neutral stage on the right. Maintain the alternation, light screenshot backgrounds, compact text blocks, and generous gaps.

### Services

A centered “YowStay Services” heading and short subtitle introduce three equal cards in one desktop row. Each card contains a product screenshot crop in the upper area, a small purple-accent icon, a bold compact title, and gray supporting copy. The visible card topics are Smart Workforce Management, Housekeeping Coordination, and Data-Driven Insights. The middle card has slightly more elevation, while the outer cards show thin borders. A small centered blue Learn More button sits below the row.

### Audience panel

A wide pale-blue rounded rectangle contains a left-aligned heading (“Who is YowStay For?”), a short description, and a compact blue action. On the right is a two-by-two grid of white cards for Hotel Owners, Airbnb Hosts, B&B Operators, and Property Managers. Each card contains a small icon, a bold short title, and a few lines of muted text. Oversized, very pale blue geometric arcs sit behind the content, clipped to the container. The panel is the landing page's main broad color field.

### Pricing

A centered “Flexible Pricing Plans” heading and gray subtitle precede a compact Month/Year billing switch; the active monthly segment is purple. Three cards form one desktop row. Each has a small icon, plan name, one-line description, price with smaller monthly unit, divider, blue checkmarks, features, and a full-width action near the bottom. The center plan receives emphasis through its solid blue button and soft shadow; the others have pale blue actions.

The source labels are Basic Plan, Pro Plan, and Enterprise Plan, with $29, $69, and $129 monthly prices. These are reference content, not this project's approved commercial plans. Reproduce the card structure and emphasis while sourcing actual product plans separately. No footer is visible in the supplied crop; a footer design is outside the captured reference.

## Shared component behavior

| Component | Visual target | Implementation behavior |
| --- | --- | --- |
| Primary button | Bright blue, white text, rounded corners, localized blue shadow | Clear hover/focus/pressed/disabled states; submit only once while pending |
| Secondary button | White, light-gray 1px border, dark text | Same control height as adjacent primary action |
| Icon button | Fine dark SVG in white circle or subtle gray square | Accessible name and a real action |
| Segmented switch | Pale gray rounded track; selected white segment, or purple in pricing | Expose selection and support keyboard operation |
| Search | White pill, fine search icon, muted placeholder, trailing microphone in reference | Search and voice controls need implemented behavior or an explicit unavailable state |
| Dropdown | Small white bordered rounded control with chevron | Keyboard navigation, visible selection, Escape dismissal |
| Table | Minimal separators, gray headers, selectively elevated highlighted row | Sorting only where supported; responsive internal scrolling |
| Tooltip | Small white rounded panel with soft shadow and series markers | Available through keyboard focus as well as pointer hover |
| Form / dialog | Carry the white, blue, gray, and rounded-control language into booking flows | Visible labels, clear errors, preserved input, focus management |

The reference's thin gray typography must remain readable in the implementation. Normal text targets 4.5:1 contrast and large text 3:1. Color-coded charts and tiles also need labels or accessible text. Where a sampled color is insufficient for text, retain it as a fill and use a darker text variant. Enlarge touch hit areas without unnecessarily enlarging the visible icon artwork.

## Responsive and motion targets

Only desktop designs are supplied. The following are proposed adaptations, not screenshots of a supplied mobile layout:

- At wide desktop widths, preserve the dashboard's two equal main columns and the first-row KPI/chart subdivision. Compare at 1600 × 1200 before adapting other sizes.
- Around 1200px, reduce sidebar width and surrounding presentation margins before compressing charts and tables. Allow the outer frame to fill the viewport.
- Around 1024px, use a single main dashboard column when two columns would make widgets unreadable. Keep each widget's internal hierarchy.
- Below 768px, use a dismissible sidebar drawer, a compact header, stacked dashboard sections, and scrollable table containers. Room tiles can use fewer columns while keeping readable status information.
- Landing navigation collapses into a real menu. Hero type scales down without clipping; actions wrap or stack; the product screenshot keeps its aspect ratio.
- Feature rows, services, audience cards, and pricing stack in the original reading order. Proof metrics can use two columns before one column becomes necessary.
- Use short 120–180ms transitions for hover, focus, menu, and switch feedback. Respect reduced-motion preferences. No scrolling choreography, looping card movement, or animated chart entrances are established by the references.

## Application mapping and product truth

The dashboard reference governs `/app`; the landing reference governs the product-marketing presentation. The current `/` route is a property marketplace, and `/hotels/{slug}` is a hotel booking page. Assigning the new marketing page a route or changing those existing surfaces is implementation work, not a change made by this document. Existing booking pages can share the new white/blue component language while retaining their hotel-search and reservation tasks.

Staff reads and actions must remain property-scoped and role-authorized. The new shell must retain a usable property switcher even though the reference user block does not show one. Reservations, room readiness, tax-inclusive quotes, check-in/out, and manual receipts retain their current server rules.

Some pictured widgets require data the application does not currently collect: employee shifts and productivity, weekly website visitors, marketplace-platform booking series, expenses, and commercial subscription plans. Keep those compositions available for clearly labeled demonstration views. A functional release should bind them to real data, provide useful empty/unavailable states, or map the same visual structure to existing operational metrics with accurate labels. The screenshot values and identities are not production records.

The current application records manual payments and exports one-way iCalendar availability. Partner marks and platform chart labels in the references do not establish active OTA integrations, payment gateways, commissions, or subscription billing. Reference artwork should remain under `docs/design/references`; a future live preview should use a rendered dashboard or a suitable exported asset rather than presenting the composite as the working interface.

## Implementation locations

| Area | Existing source |
| --- | --- |
| Shared tokens, layout, components, responsive rules | [public/assets/app.css](public/assets/app.css) |
| Dialog, drawer, tab, print, and form interactions | [public/assets/app.js](public/assets/app.js) |
| Staff shell | [resources/views/layouts/admin.blade.php](resources/views/layouts/admin.blade.php) |
| Public shell | [resources/views/layouts/public.blade.php](resources/views/layouts/public.blade.php) |
| Dashboard widgets | [resources/views/admin/overview.blade.php](resources/views/admin/overview.blade.php) |
| Public marketplace and hotel screens | [resources/views/public](resources/views/public) |
| Shared SVG icons | [resources/views/components/icon.blade.php](resources/views/components/icon.blade.php) |
| Routes | [routes/web.php](routes/web.php) |

The current CSS still contains the previous green palette and serif display styling. The values in this document are redesign targets, not descriptions of those current declarations. Retain the existing Blade-based stack; the reference does not require a framework migration.

## Visual acceptance criteria

A future implementation should be reviewed against the saved references at matching viewport size and scale:

1. White sidebar and header, cool-gray workspace, blue active navigation, and near-black type match the reference hierarchy.
2. Dashboard shell edges, sidebar width, header height, 24px grid gaps, card positions, and asymmetric widget proportions align with the measured desktop layout.
3. The purple comparison bars, green stem/dot chart, blue radar chart, striped booked tiles, and selectively elevated rows retain their distinct visual forms.
4. The landing hero keeps its two-line heading, blue phrase, floating benefit capsule, paired actions, partner-mark row, and layered dashboard preview with a fading lower edge.
5. The landing continuation follows the visible order: advantage area, feature rows, services, pale-blue audience panel, and three-plan pricing section. Cropped areas remain acknowledged rather than treated as supplied content.
6. Radius, border weight, typography width, icon stroke, neutral tones, and localized shadows are checked at close scale, not only as a page thumbnail.
7. Product text, prices, percentages, people, and chart values are real or visibly labeled demonstration content. All exposed navigation and controls have appropriate behavior.
8. Phone layouts, long names, empty results, validation errors, pending actions, keyboard focus, contrast, and reduced-motion behavior are verified separately from screenshot resemblance.

This document was checked against the supplied PNGs and repository paths. No application UI was changed, rendered, or certified as a pixel-exact implementation by this documentation update.
