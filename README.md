# Bolt Edit

Frontend content editing for AI-built WordPress themes. The theme owns the design, your client edits the words and images right on the website.

## What Bolt Edit does

Bolt Edit is made for custom WordPress themes built by an AI coding agent such as Codex, Claude, Gemini or Cursor. It gives the agent the rules to build the theme properly, and gives your client a simple editing layer on top.

**For your clients**

- Edit text, rich text, buttons and images on the page with the **Edit** button.
- Images sit in fixed frames, so a new image never breaks the layout.
- Content from other plugins (WordPress, ACF, JetEngine, WooCommerce) opens its own edit screen in a popup, with the clicked field highlighted.
- Private comments with pins and an inbox.
- Focus mode: a simple top bar and a start on the website for the users you choose.

**For you as the developer**

- AI instructions per topic, with your own additions. A connected agent reads them with the `bolt-edit/get-instructions` ability.
- A clean-up prompt that checks the whole site against the instructions.
- Dev mode: inspect classes, spot hard-coded values and adjust spacing.
- Templates overview, change log, access per role and a simple SEO box.

Visitors never load Bolt Edit, so it does not make your website slower.

## Requirements

- WordPress 6.4 or newer
- PHP 7.4 or newer
- A custom theme built to use Bolt Edit's helpers

Bolt Edit does not make an existing pagebuilder site or block theme editable.

## Installation

1. Download the versioned `bolt-edit-*.zip` from the latest GitHub release.
2. In WordPress, open **Plugins → Add Plugin → Upload Plugin**.
3. Upload the ZIP and activate **Bolt Edit**.
4. Open **Bolt Edit → How to use** and give the starter prompt to your coding agent.

## Theme helpers

The full instructions for coding agents are in [`docs/agent-instructions.md`](docs/agent-instructions.md) and inside WordPress under **Bolt Edit → AI instructions**.

- `bolt_edit_text()`
- `bolt_edit_richtext()`
- `bolt_edit_button()`
- `bolt_edit_image()`
- `bolt_edit_managed_region_open()` and `bolt_edit_managed_region_close()`


## Version 1.1.0

AI instructions per topic, a clean-up prompt, Dev mode, a Templates tab, Access per role, an SEO box and a How to use page. Content from other plugins opens in a popup. The Clients role is replaced by Focus mode (existing Clients become Editors with Focus mode). Visitors never load Bolt Edit.

[Report a problem or share an idea](https://github.com/Bolt-Connect/bolt-edit/issues)

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).

## GitHub updates

Published stable releases appear in WordPress plugin updates. No GitHub token or wp-config.php change is needed. Use **Bolt Edit → Features → Plugin updates → Check for updates** to refresh immediately. Automatic updates follow your WordPress choice. Updates preserve content, comments, and settings.

For a release, bump the plugin version (header and `BOLT_EDIT_VERSION`) and readme stable tag, commit the changes, and publish a matching stable GitHub release such as `v1.1.1`. Attach an installable ZIP containing the `bolt-edit/` folder.

Developed and maintained by [Bolt Connect](https://boltconnect.nl).
