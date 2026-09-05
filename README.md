# Email Module for Nails

![license](https://img.shields.io/badge/license-MIT-green.svg)
[![tests](https://github.com/nails/module-email/actions/workflows/build_and_test.yml/badge.svg)](https://github.com/nails/module-email/actions)


This is the email module for nails, it brings email capability to the app.


## The email template

Every email is assembled from three views, concatenated and then run through
Mustache in a single pass:

| Part | Default view |
| --- | --- |
| Header | `email/structure/email_header` (`_plaintext`) |
| Body | whatever the email type declares |
| Footer | `email/structure/email_footer` (`_plaintext`) |

Body views are bare HTML fragments — a few paragraphs, a table, a button. They
inherit the shell and its stylesheet, and should not open a `<table>` for
layout or carry `style` attributes of their own.

The header and footer are **two halves of one document**. The header opens
everything down to the content well and closes none of it — `<html>`,
`<body>`, the `.body-wrap` table and its row, the `.container` cell, the
Outlook ghost table, the `.content` div, the `.main` table and its row, and the
`.content-wrap` cell — and the footer closes all nine in order. They are not
independently overridable: replacing one means replacing both, and a copy taken
today stops tracking upstream changes. Override a slot instead.


### The shell

```
grey page  (.body-wrap)
  gutter | centred 600px column (.container > .content) | gutter
      masthead                    slot: logo, or the app name as text
      card (table.main)
          subject banner          .alert.alert-header
          non-production band     .alert.alert-header.alert-header--warning
          content well            .content-wrap
              greeting            slot
              the body view
              sign off            slot
      footer (.footer)
          links                   slot: view online, unsubscribe
          address                 slot
          email reference
```


## Customising it

Three layers, cheapest first.

### 1. Do nothing

The masthead calls `Logo::discover()`, which resolves a logo from the app's
composer `extra.nails.data.logo_url`, then the `APP_LOGO_URL` config value,
then a filesystem probe of `assets/img/logo.{png,jpg,gif}`. If it finds one you
get a branded email header for free; if it does not, the app's name is set as
text.

### 2. Set the content settings in admin

Under *Admin → Settings → Email → Content*:

| Setting | Effect |
| --- | --- |
| Sign Off | A block rendered below the body of every email |
| Footer Address | A postal address in the footer |

Both are omitted entirely — no empty row, no stray whitespace — when blank.

Because Mustache runs *after* the three views have been concatenated, these
values share the email's template context. `The {{appName}} Team` and
`Hope that helps, {{sentTo.first_name}}` both work, as does the
`{{ config('FOO') }}` syntax. They are emitted unescaped, which is the same
trust boundary as the email template override admin — treat them as
administrator-authored HTML.

### 3. Override a slot

Drop a file into `application/modules/email/views/structure/slots/`. It wins
over the module's copy through `View`'s normal resolution order; no
registration, and no need to touch the shell or its tag-balance contract.

| Slot | Default |
| --- | --- |
| `styles` | nothing — see below |
| `masthead` | the discovered logo, else the app name as text |
| `greeting` | `Hi {{sentTo.first_name}},`, falling back to `Hi,` |
| `signoff` | the `sign_off` setting, else nothing |
| `footer_links` | view-online and unsubscribe links |
| `footer_address` | the `footer_address` setting, else nothing |

Each has a `<slot>_plaintext` counterpart for the text part of the email, which
you should override alongside it. Slots receive `$emailObject`.

`slots/styles.php` is rendered inside `<head>` *after* the module's own
`<style>` block, so anything it emits cascades over the framework. That is the
hook for an app which compiles its own email CSS:

```php
<style type="text/css">
    <?php require NAILS_APP_PATH . 'assets/css/email.min.css'; ?>
</style>
```

There is no CSS inliner in the pipeline, so this has to be an inline `<style>`
block rather than a `<link>`.


## The stylesheet

`assets/sass/email.scss` is a manifest; the framework itself is a set of
partials under `assets/sass/email/`:

```
base/       variables  mixins  reset  typography  utilities
layout/     shell  header  footer
components/ alert  badge  button  code  divider  hero  panel  table
```

Every partial imports its own dependencies, so any one of them can be compiled
on its own. Set your tokens **before** the import, or the `!default` wins:

```scss
$email-color-brand: #00a0b0;
$email-radius-card: 0;
@import '../../vendor/nails/module-email/assets/sass/email/components/button';
```

Media-query and dark-mode rules live in the partial they belong to rather than
a trailing `responsive.scss`, so cherry-picking `components/button` gets the
button's narrow-viewport and dark rules with it.

Tokens are Sass variables rather than custom properties, which is a deliberate
divergence from `nails/common`: Outlook renders with the Word engine, which has
no `var()` support and no fallback which can recover a missing colour, so
tokens have to compile down to literals. Everything is prefixed `$email-`
because `@import` leaks globals.

`assets/css/email.min.css` is a committed build artefact. Run `yarn build`
after changing anything under `assets/sass/` and commit the result.


### Class reference

Everything below is available to a body view.

**Alerts** — `alert` plus one of `alert-success`, `alert-info`,
`alert-warning`, `alert-danger`. `alert--success` and friends work too.

```html
<p class="alert alert-warning">Something needs your attention.</p>
```

**Banners** — `alert alert-header` is the subject banner and
`alert-header--warning` / `--danger` / `--success` / `--info` are solid-fill
bands. These belong to the shell; a body view rarely wants them.

**Buttons** — `btn` (or `button`) plus one of `btn-primary`, `btn-secondary`,
`btn-default`, `btn-success`, `btn-warning`, `btn-danger`, `btn-info`,
`btn-link`. Add `btn-block` for full width. `btn--primary`-style modifiers and
the `button-*` aliases work as well.

```html
<a href="{{url}}" class="btn btn-block btn-primary">Pay online now</a>
```

A button fakes its padding with a border, because Outlook ignores padding on an
anchor. The consequence, if you are writing a variant of your own: the padding
ring is painted with the **border** colour, so `border-color` has to equal the
fill. An outline variant draws its rule with a non-inset `box-shadow`.

For a single, important call to action, `btn-table` is the Outlook-hardened
version — a one-cell table, so the whole area is clickable everywhere:

```html
<table class="btn-table btn-table--center" role="presentation" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td class="btn-table__cell" align="center" bgcolor="#d53354">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word"
                         href="{{url}}" style="height:48px;v-text-anchor:middle;width:240px;" arcsize="12%"
                         strokecolor="#d53354" fillcolor="#d53354">
                <w:anchorlock/>
                <center style="color:#ffffff;font-family:Arial,sans-serif;font-size:16px;font-weight:bold;">Pay online now</center>
            </v:roundrect>
            <![endif]-->
            <!--[if !mso]><!-->
            <a href="{{url}}">Pay online now</a>
            <!--<![endif]-->
        </td>
    </tr>
</table>
```

**Tables** — `table`, with `table--list` for a two-column label/value list,
`table--numeric` to centre every column after the first, and `table--plain`
for no borders or tints. Do not put `role="presentation"` on a data table.

**Panel** — `panel` with `panel__header`, `panel__body` and `panel__footer`.
Works as a `<div>` or a single-column `<table>`. `panel--plain` sits on the
card rather than being tinted by it; `panel--brand` adds a brand rule.

**Other components** — `badge` (`badge--neutral`, `--success`, `--warning`,
`--danger`, `--info`), `divider` (`--tight`, `--loose`, `--strong`, `--blank`),
`hero` with `hero__caption`, `code` / `<pre>` for monospaced output, and
`heads-up` for a value the recipient has to read off the screen and use.

**Utilities** — `alignleft` / `aligncenter` / `alignright` and their
`text-left` / `-center` / `-right` equivalents; `text-muted`, `text-brand`,
`text-small`, `text-tiny`, `text-large`, `text-bold`, `text-normal`,
`text-uppercase`, `text-nowrap`, `text-break`; `valign-top` / `-middle` /
`-bottom`; `mobile-only` and `desktop-only`; `last` to drop a trailing margin;
and spacing steps on a 4px scale — `m-0` … `m-7`, `mt-`, `mr-`, `mb-`, `ml-`,
`mx-`, `my-` and the `p-` equivalents (`0`, `4px`, `8px`, `12px`, `16px`,
`24px`, `32px`, `48px`).

**Preheader** — the snippet a client shows next to the subject in the message
list. Set `preheader` in the email's data to fill it.


## Client caveats

- **No CSS inliner.** Styling comes from the embedded `<style>` block only.
  Clients which strip it — notably the Gmail app signed in to a non-Gmail
  account — render close to unstyled. The HTML presentational attributes
  (`width`, `align`, `valign`, `bgcolor`) are kept as a partial fallback: the
  card keeps its 600px width, its white fill and its coloured banner, but not
  its padding, radii or type scale. Mobile clients which strip `<style>`
  generally scale the message to fit rather than scrolling it.
- **Dark mode** is `prefers-color-scheme`, paired with `<meta name="color-scheme">`
  and `<meta name="supported-color-schemes">`. Honoured by Apple Mail, Mail on
  iOS and Outlook.com. **Gmail ignores it and force-inverts on its own terms**
  — do not spend an afternoon working out why the dark rules appear not to
  apply there. Nothing in the framework depends on dark mode being honoured;
  the light palette is the one that has to work.
- **Outlook** gets a `<!--[if mso]>` block which swaps the font stack for Arial
  and removes every radius, plus a ghost table which holds the card at 600px.
  The footer closes the ghost table the header opens.
- A logo discovered by `Logo::discover()` is used in both colour schemes. If
  yours only works on a light background, override `slots/masthead.php`.


## Previewing and testing

In a development environment the *view online* link in the footer renders the
email debugger, which shows the Mustache context, the HTML part in an
`<iframe>` and the plain text part side by side.

To send real mail:

```bash
php nails email:test <address>          # the framework test email
php nails email:test <address> -t <type>
```

*Admin → Utilities → Send test email* does the same through the UI.
