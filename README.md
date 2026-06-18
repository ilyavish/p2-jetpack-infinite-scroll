# P2 Jetpack Infinite Scroll Compatibility

Standalone WordPress plugin that adds Jetpack Infinite Scroll support to the classic P2 theme without editing P2 or requiring a child theme.

## Behavior

- Runs only when the active theme or parent theme is P2.
- Runs only when Jetpack is available.
- Uses Jetpack's click-based Infinite Scroll mode.
- Appends posts into P2's real `ul#postlist` stream.
- Renders posts through P2's own `p2_load_entry()` function.
- Leaves the original P2 theme files untouched.
- Fails closed if P2, Jetpack, or the expected P2 renderer is missing.

## Installation

1. Upload this directory to `wp-content/plugins/p2-jetpack-infinite-scroll`.
2. Activate `P2 Jetpack Infinite Scroll Compatibility`.
3. Make sure Jetpack is active and Infinite Scroll is available.

No admin page is added.

## Compatibility Notes

This plugin assumes the classic P2 theme structure where the normal loop renders posts as direct children of `ul#postlist` and each entry is emitted by `p2_load_entry()`. Those assumptions are intentionally checked before Infinite Scroll support is registered.

## License

GPL-2.0-or-later.
