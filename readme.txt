=== Key Figure Block ===
Contributors:      beapi, candrietti
Tags:              block, key, figure, gutenberg
Tested up to:      6.5
Stable tag:        1.1.1
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Gutenberg key figure block

== Description ==

A modular key figure gutenberg block.

This block allow to set a prefix, suffix and a number. It's possible to change the color and background of the block, text alignment...

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/blockparty-key-figure` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress

== Screenshots ==

1. Block configuration in columns block

== Migration from beapi/key-figure ==

Content created with the legacy `beapi/key-figure-block` plugin still stores the old block name (`wp:beapi/key-figure`) and the old BEM root class (`wp-block-beapi-key-figure`). Those blocks are invalid in the editor until the content is rewritten, which the `wp blockparty key-figure migrate` WP-CLI command does.

The command scans the post content of every post type (revisions and reusable blocks included) and the block widgets, then:

1. Renames `wp:beapi/key-figure` to `wp:blockparty/key-figure`, keeping the block attributes.
2. Renames `wp-block-beapi-key-figure` to `wp-block-blockparty-key-figure`, including the `__key`, `__prefix`, `__number`, `__suffix` and `__description` elements.
3. Aligns the markup with the current block output, unless `--no-modernize` is used: `p` key wrapper (instead of `div`), `data-decimal-separator` and `data-minimum-fraction-digits` on the number, and the raw number as text since formatting now happens on the front end.

It must be run manually on the server:

    # Preview changes without writing to the database.
    wp blockparty key-figure migrate --dry-run

    # Apply the migration.
    wp blockparty key-figure migrate

    # Only rename the block and its CSS classes.
    wp blockparty key-figure migrate --no-modernize

    # Limit the migration to some post types, with a smaller batch size.
    wp blockparty key-figure migrate --post-type=post,page --posts-per-page=20

The command always targets a single site. On multisite, run it for each site with the native `--url` parameter:

    wp blockparty key-figure migrate --url=example.com
    wp blockparty key-figure migrate --url=example.com/site-2

Run `wp help blockparty key-figure migrate` for the full list of options.

== Changelog ==

= 1.0.0 - 2024-04-02 =
* Initial plugin release

= 1.0.1 - 2024-04-03 =
* Fix plugin internal version

= 1.0.2 - 2024-06-06 =
* Add support for PHP 8.2

= 1.0.3 - 2024-06-10 =
* Fix composer name (from blockparty/key-figure to beapi/blockparty-key-figure)

= 1.0.4 - 2025-06-11 =
* Move number formatting to JavaScript and save unformatted numbers.

= 1.1.0 - 2026-02-16 =
* Markup change: __key wrapper was div, now p (a11y/semantic)

= 1.1.1 - 2026-09-04 =
* Add block example.
* Move suffix attribute after number control for UX improvment
