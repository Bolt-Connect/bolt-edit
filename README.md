# Bolt Edit

Frontend content editing for AI-built WordPress themes. The theme owns the design; clients edit the words and images directly on the website.

Bolt Edit is designed for custom themes built by coding agents such as Codex, Claude, Cursor, or another connected agent. It provides a controlled content layer rather than a page builder.

## What Bolt Edit does

- Edits text, rich text, buttons, and Media Library images on the frontend.
- Keeps layout, styling, and behaviour in the theme.
- Preserves content through stable field IDs when the theme design changes.
- Marks content owned by WordPress, ACF, JetEngine, WooCommerce, or another provider as managed elsewhere.
- Records content changes with the editor, page, time, and before-and-after values.
- Supports private comments with pinpoints, a separate inbox, and resolving feedback.
- Offers an optional Clients role with simplified editing screens and frontend-only static page editing.
- Includes two agent workflows: build a theme from an idea or convert an HTML template.

## Requirements

- WordPress 6.4 or newer
- PHP 7.4 or newer
- A custom theme built to use Bolt Edit's helper functions

Bolt Edit does not automatically make an existing page-builder or block theme editable.

## Installation

1. Download the versioned `bolt-edit-*.zip` from the latest GitHub release.
2. In WordPress, open **Plugins → Add Plugin → Upload Plugin**.
3. Upload the ZIP and activate **Bolt Edit**.
4. Open **Bolt Edit → Get started**.
5. Copy the starter prompt for your workflow and give it to your coding agent.

## Building a compatible theme

The complete contract for coding agents is available in [`docs/agent-instructions.md`](docs/agent-instructions.md) and inside WordPress under **Bolt Edit → Get started → For AI Agents**.

The theme remains responsible for its design and safe fallback output. Bolt Edit owns only content registered through its helpers:

- `bolt_edit_text()`
- `bolt_edit_richtext()`
- `bolt_edit_button()`
- `bolt_edit_image()`
- `bolt_edit_managed_region_open()` and `bolt_edit_managed_region_close()`

## Version 1.0.0

First release of Bolt Edit: text, rich text, button and image fields on the live page, a clickable placeholder for empty image fields, managed regions, a Changes log, private comments, an optional Clients role, a Dutch translation that follows the WordPress language, AI agent instructions, and updates through GitHub releases.

[Report a problem or share an idea](https://github.com/Bolt-Connect/bolt-edit/issues)

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).

## GitHub updates

Published stable releases appear in WordPress plugin updates. No GitHub token or wp-config.php change is needed. Use **Bolt Edit → Settings → Check for updates** to refresh immediately. Automatic updates follow your WordPress choice. Updates preserve content, comments, and settings.

For a release, bump the plugin version (header and `BOLT_EDIT_VERSION`) and readme stable tag, commit the changes, and publish a matching stable GitHub release such as `v1.0.1`. Attach an installable ZIP containing the `bolt-edit/` folder.

Developed and maintained by [Bolt Connect](https://boltconnect.nl).
