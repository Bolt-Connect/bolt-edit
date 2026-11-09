=== Bolt Edit ===
Contributors: boltconnect
Tags: frontend editing, ai, custom theme, content editing, client editing
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Frontend content editing for AI-built WordPress themes. The theme owns the design, your client edits the words and images.

== Description ==

Bolt Edit gives your clients a simple way to edit their website. They click **Edit** on the page, change a text or an image, and save. No dashboard, no pagebuilder, no way to break the design.

It is made for custom WordPress themes built by an AI coding agent such as Codex, Claude, Gemini or Cursor. Bolt Edit gives that agent the rules to build the theme properly, and gives your client the editing layer on top.

**For your clients**

* Edit text, rich text, buttons and images right on the page, with the **Edit** button
* Images sit in fixed frames, so a new image never breaks the layout
* Content from other plugins (WordPress, ACF, JetEngine, WooCommerce) opens its own edit screen in a popup, with the clicked field highlighted
* Private comments on any part of the page, with pins and an inbox
* **Focus mode** for the users you choose: a simple top bar and a start on the website instead of the dashboard

**For you as the developer**

* **AI instructions:** default rules for front-end editing, templates, content, design system, mobile, speed, safety, SEO and checks. Add your own rules per topic. A connected agent reads everything itself with the `bolt-edit/get-instructions` ability.
* **Clean-up prompt:** one prompt that makes your agent check the whole site against the instructions and fix what slipped, ending with a short summary in plain words
* **Dev mode:** inspect the classes on any element, spot hard-coded values and adjust spacing with your own design system
* **Templates:** see every template of the theme and where it is used
* **Change log:** who changed what, where and when, with before and after
* **Access:** choose which user roles may edit on the website
* A simple SEO title and description box when no SEO plugin is active

**Good to know**

* **Your website stays fast.** Bolt Edit only loads for people who are logged in and allowed to edit. Visitors never load it.
* **Turning Bolt Edit off is safe.** Your agent builds a normal WordPress theme, so every page keeps showing its content without Bolt Edit.
* **A theme you can hand over.** One design system, real WordPress pages and images in the Media Library, so another developer can keep building.

Bolt Edit does not make an existing pagebuilder site or block theme editable. It works with themes built to use its helpers.

== Installation ==

1. Download the `bolt-edit-*.zip` from the latest GitHub release and upload it under **Plugins → Add Plugin → Upload Plugin**.
2. Activate Bolt Edit.
3. Connect your AI tool to the website through an MCP plugin or solution of your choice.
4. Open **Bolt Edit → How to use**, copy the starter prompt for your starting point (an idea or an existing HTML design) and give it to your agent.
5. Before you launch, run the clean-up prompt.
6. Open the website while logged in and click **Edit**.

== Frequently Asked Questions ==

= Does Bolt Edit work with my existing theme or pagebuilder? =

No. Bolt Edit works with a theme that was built to use its helpers. Sites built with a pagebuilder or with block templates are not supported.

= Who can edit content? =

Administrators and Editors by default. Choose other roles under **Bolt Edit → Features → Access**. For clients, pick them under **Focus mode** for a simpler dashboard. Developers can use the `bolt_edit_can_edit` filter for custom rules.

= Does Bolt Edit make my website slower? =

No. Visitors never load Bolt Edit. The editing tools only load for people who are logged in and allowed to edit.

= What happens when I turn Bolt Edit off? =

Your website keeps working and every page keeps showing its content. You lose the editing and the AI instructions, so we recommend keeping it on.

= How does my AI agent know the rules? =

The starter prompts tell the agent to read the instructions with the `bolt-edit/get-instructions` ability. It returns the default instructions together with your own additions.

= Is my content safe when the theme changes? =

Yes. Each field has a stable ID, and a saved value always wins over the theme's default.

= How do I get updates? =

Bolt Edit updates through GitHub releases and shows up in the normal WordPress plugin updates. Use **Bolt Edit → Features → Plugin updates → Check for updates** to check right away. Updates keep your content, comments and settings.

== Changelog ==

= 1.1.0 =
* New: AI instructions tab with default rules per topic and your own additions. Agents read them with the bolt-edit/get-instructions ability.
* New: a clean-up prompt that checks the whole site against the instructions and ends with a short summary.
* New: Dev mode for administrators: inspect classes, spot hard-coded values and adjust spacing.
* New: Templates tab, Access settings per role, and an SEO box for sites without an SEO plugin.
* New: How to use page with four phases and starter prompts that fill in the website address.
* Changed: content from other plugins opens the full edit screen in a popup, with the clicked field highlighted.
* Changed: the Clients role is replaced by Focus mode. Users with the Clients role become Editors with Focus mode automatically.
* Changed: the Edit button replaces the Open Bolt Edit button.
* Changed: Plugin updates moved to Bolt Edit → Features.
* Improved: visitors never load Bolt Edit and never cause database writes.

= 1.0.0 =
* First release of Bolt Edit.
* Text, rich text, button and image fields, editable on the live page.
* Empty image fields show a clickable placeholder for editors, so a theme can ship without images.
* Managed regions, a Changes log, private comments and an optional Clients role.
* Dutch translation. Bolt Edit follows the WordPress language: Dutch for every Dutch locale, English for all other languages.
* Instructions for AI agents and updates through GitHub releases.
