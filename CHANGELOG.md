# Change Log


## Unreleased

### Email template

The default email template has been rebuilt. Existing body views keep working
— every class name the shell and the downstream modules used is still defined
— but the emails themselves look different.

- All styling now lives in SCSS. The `style` attributes duplicated on every
  element of `email_header.php` and `email_footer.php` are gone; the HTML
  presentational attributes (`width`, `align`, `valign`, `bgcolor`) stay as a
  fallback for clients which strip `<style>`, along with `role="presentation"`
  on the layout tables. The tracker pixel and the preheader keep their inline
  rules, because both break if the `<style>` block is dropped.
- `assets/sass/email.scss` is now a manifest over partials in
  `assets/sass/email/{base,layout,components}`. Each imports its own
  dependencies, so a consuming app can compile one component on its own; every
  token is an `!default` `$email-*` variable it can set first.
- New components: `panel`, `divider`, `badge`, `code`, `hero`, a table-based
  Outlook-hardened button, and a full status set for alerts and buttons.
- Classes which the shell or a downstream module referenced but which had never
  been defined anywhere are now authored: `.body-wrap`, `.main`, `.alert`,
  `.alert-header`, `.alert-warning`, `.alert-danger`, `.footer`,
  `.content-block`, `.aligncenter`, `.alignleft`, `.alignright`,
  `.btn-primary`, `.btn-default`, `.heads-up`, and the gutter cells' `.gutter`.
- Dark mode via `prefers-color-scheme`, plus the `color-scheme` meta tags.
  Gmail ignores both and force-inverts regardless; see the README.
- Outlook hardening: an MSO-only `<style>` block for the fallback font and
  squared corners, a ghost table holding the card at 600px, and
  `mso-table-lspace` / `mso-table-rspace` / `mso-line-height-rule` throughout.
- Visual refresh onto `nails/common`'s palette — brand `#d53354`, page
  `#f1f3f6`, ink `#1d2229` — 16px base type on a 1.6 line height, a consistent
  spacing scale, and an 8px card radius.

### Fixed

- `.container`, `.content` and `.content-wrap` carried `!important` mobile
  overrides outside any media query, so the narrow-viewport layout applied at
  every width. They are now inside `@include email-mobile`.
- The subject banner's `bgcolor` was `#FF9F00` while its `background-color` was
  `#4a4a4a`; a client honouring one and not the other got the wrong colour.
- `.container` no longer carries a fixed width, and the `width="600"` fallback
  attribute has moved from the container cell to the card table. The old
  arrangement overflowed the viewport once the gutters collapsed, and forced
  the card open to ~670px wherever `.container` is nested inside the content
  well — which `module-invoice`'s invoice view does.
- `.btn-secondary` and `.btn-default` set a `border-color` which differed from
  their fill. Since the button fakes its padding with a border, that rendered
  as a button in the border's colour with a patch of the background around the
  label. Both are now proper outline variants.
- Footer text was `#999` on `#f6f6f6`, about 2.8:1 and short of WCAG AA. It now
  uses the palette's muted ink. The two footer links no longer run together.
- The HTML footer omitted `{{emailRef}}` while the plain text one included it.
- A recipient with no first name got no greeting at all in the plain text part;
  the `{{^sentTo.first_name}}` branch was missing.
- `!important` is gone from `h1`–`h4`, which only needed it to out-specify the
  inline styles, and the four duplicated blocks are one rule and a type scale.
- The tracker pixel is 1x1 rather than 0x0; some clients decline to request a
  zero-dimension image.

### Added

- Slots: the shell is decomposed into `email/structure/slots/*`, each with a
  module-shipped default and a plain text counterpart. An app overrides one by
  dropping a file into `application/modules/email/views/structure/slots/`,
  rather than copying the whole header or footer and losing upstream changes.
- A masthead which shows the app's logo via `Logo::discover()` with no
  configuration at all, falling back to the app name as text.
- Two settings under a new *Content* fieldset in *Admin → Settings → Email*:
  `sign_off` and `footer_address`. Both are rendered through the email's
  Mustache context and both are omitted entirely when blank.
- A `preheader` template variable, for the snippet a client shows next to the
  subject in the message list.
- README documentation: the class reference, the slot list, the three
  customisation layers, the token-override and cherry-pick recipes, the
  header/footer tag-balance contract and the client caveats.
- A **System / Light / Dark** switcher on the email debugger's HTML pane.
  `prefers-color-scheme` follows the browser, and so the desktop, which left
  flipping the OS appearance or DevTools emulation as the only ways to see the
  dark palette. The Light and Dark panes render copies of the email with that
  query rewritten to `all` or `not all`, so neither can be wrong about which
  palette it is showing; System renders the email untouched and remains the
  default. The HTML pane is also 680px wide now, clearing the 640px mobile
  breakpoint so the 600px card previews its desktop layout rather than its
  narrow one.


## Version 0.1.0

Release date: 21st June 2014

- Initial Release, first release through composer, not intended to be stable at all.