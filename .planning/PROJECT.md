# Glossary by Progress Planner

## What This Is

A WordPress plugin that automatically links glossary terms to accessible, semantic popovers that appear on click. Uses native WordPress custom fields for field management and includes a Gutenberg block for displaying the full glossary. The plugin is production-ready with comprehensive Schema.org integration and accessibility features.

## Core Value

Glossary terms mentioned anywhere in content should become discoverable learning opportunities — clicking reveals their definition without leaving the page.

## Requirements

### Validated

<!-- Shipped and confirmed valuable. -->

- ✓ Register custom post type for glossary entries (title + custom fields only) — existing
- ✓ Store entry data in consolidated post meta (short description, long description, synonyms) — existing
- ✓ Case-sensitive matching option per entry — existing
- ✓ Disable auto-linking option per entry — existing
- ✓ Auto-transform first mention of terms in content into click-triggered popovers — existing
- ✓ Popover displays short description with "Read more" link to glossary page — existing
- ✓ Gutenberg block displays full glossary with alphabetical navigation — existing
- ✓ Settings page to configure glossary page, excluded tags, excluded post types — existing
- ✓ Schema.org DefinedTermSet/DefinedTerm integration (Yoast + Microdata) — existing
- ✓ CSS Anchor Positioning for popover placement — existing
- ✓ Accessible click-based interaction with keyboard support — existing
- ✓ Only one popover open at a time (mutual exclusivity) — existing

### Active

<!-- Current scope. Building toward these. -->

- [ ] Glossary terms inside glossary definitions should be auto-linked
- [ ] Nested links point to glossary page anchors (not nested popovers)
- [ ] Current term should not link to itself in its own definition
- [ ] Works in both Glossary List block and popovers

### Out of Scope

- Nested popovers (clicking term in popover opens another popover) — UX complexity, recursion risk
- Setting to enable/disable nested linking — keep it simple, always on
- Limiting nested linking to specific contexts — same behavior everywhere

## Context

This is a brownfield project with a mature, production-ready WordPress plugin. The codebase follows WordPress conventions with class-based component architecture. Key processing happens in:

- `Content_Filter::filter_content()` — transforms content by replacing terms with popovers
- `Blocks::render_glossary_list_block()` — renders the full glossary block

The term matching algorithm uses regex with word boundaries and handles overlapping terms by processing longest first. Content inside excluded HTML tags is skipped.

Feature request came from GitHub Issue #35: Users want terms referenced inside glossary definitions to be clickable, improving discoverability and making the glossary feel more connected.

## Constraints

- **WordPress compatibility**: PHP 7.4+, WordPress 6.0+
- **No build process**: Vanilla JS and CSS, no transpilation
- **Accessibility**: Must maintain WCAG compliance (click-based, keyboard accessible)
- **Performance**: No additional database queries beyond existing glossary fetch
- **Browser support**: CSS Anchor Positioning gracefully degrades

## Key Decisions

<!-- Decisions that constrain future work. Add throughout project lifecycle. -->

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| One level of nesting only | Avoids recursion complexity, cleaner UX | — Pending |
| Links to anchors, not nested popovers | Simpler implementation, better performance | — Pending |
| Always enabled (no setting) | Reduces complexity, universally useful | — Pending |

---
*Last updated: 2026-02-04 after initialization*
