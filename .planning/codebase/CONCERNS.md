# Codebase Concerns

**Analysis Date:** 2026-02-04

## Performance Bottlenecks

**Unbounded WP_Query in Content Filter:**
- Problem: `class-content-filter.php` (line 123-131) runs `WP_Query` with `posts_per_page => -1` on every page load to fetch all glossary entries. For large glossaries (100+ entries), this becomes a database query that loads all entries into memory even if only a few are used.
- Files: `includes/class-content-filter.php:120-180`
- Cause: No pagination or filtering strategy; query runs synchronously during `the_content` filter (priority 20)
- Improvement path: Implement transient caching for glossary entries (e.g., 1 hour TTL, invalidate on glossary post save). Cache should be flushed when any glossary entry is updated via `save_post_pp_glossary` hook.

**Regex Pattern Recompilation:**
- Problem: `replace_first_occurrence()` (line 189-266) rebuilds the excluded tag regex pattern for every entry processed. With 10 entries, the same pattern is compiled 10 times.
- Files: `includes/class-content-filter.php:202-207`
- Cause: Excluded tag pattern is built inside the entry loop instead of once before the loop
- Improvement path: Move excluded tag pattern building outside the entry loop (before line 100 in `filter_content()`)

**Multiple Schema Queries:**
- Problem: Both `Blocks::render_glossary_list_block()` and `Schema::add_to_yoast_schema_graph()` independently run `WP_Query` for glossary entries. On glossary page with Yoast SEO active, queries run twice.
- Files: `includes/class-blocks.php:172-219` and `includes/class-schema.php:84-116`
- Cause: No shared query cache between schema and block rendering
- Improvement path: Create a shared static cache method to return glossary entries (cache key based on glossary post count)

**Asset Loading on Every Page:**
- Problem: `Assets::enqueue_assets()` (line 33-56) is hooked to `wp_footer` at priority 1, executing on every page request. Even when no glossary terms exist on the page, the class exists and the check runs.
- Files: `includes/class-assets.php:23-57`
- Cause: Hook priority 1 on `wp_footer` means it runs very late in page load
- Improvement path: Move to `wp_enqueue_scripts` (earlier in page cycle) and guard the hook addition itself with a check for glossary entries count

## Tech Debt

**Deprecated Span Element Used for Button:**
- Issue: `create_term_button()` (line 276-285) uses `<span>` with `role="button"` attribute instead of native `<button>` element. CLAUDE.md claims "uses `<button>` element" but code shows otherwise.
- Files: `includes/class-content-filter.php:279`
- Impact: Screen readers handle native button elements better; span-based buttons require more JavaScript and ARIA management
- Fix approach: Replace `<span data-glossary-popover>` with `<button type="button" data-glossary-popover>`, update JavaScript to work with button element. Note: This is a breaking change requiring JS and CSS updates.

**Inconsistent Nonce Handling in Settings:**
- Issue: Settings page (line 106) shows a comment ignoring nonce verification with phpcs directive, but WordPress form uses `settings_fields()` which handles nonce automatically. The comment is misleading.
- Files: `includes/class-settings.php:106`
- Impact: Code review confusion; developers may miss legitimate security concerns
- Fix approach: Remove the misleading phpcs:ignore comment or add clarifying comment explaining that `settings_fields()` handles verification

**Schema Attributes Without Nullability Checks:**
- Issue: `get_microdata_attributes()` (line 160-174) and `get_entry_microdata_attributes()` (line 181-188) return strings but don't validate that `get_permalink()` returns a non-empty value
- Files: `includes/class-schema.php:160-174, 181-188`
- Impact: If `get_permalink()` fails (rare edge case), returns incomplete markup
- Fix approach: Add early return if `get_permalink()` returns empty string

**Hardcoded Anchor ID Generation:**
- Issue: `create_term_button()` generates IDs using `md5(sanitize_title($entry['title']))`. If two entries have the same sanitized title (extremely rare), IDs would collide despite unique counter.
- Files: `includes/class-content-filter.php:240-241`
- Impact: Very low probability; affects only entries with identical sanitized titles
- Fix approach: Change to `'dfn-' . $entry['id'] . '-' . $popover_counter` to use post ID for guaranteed uniqueness

## Browser Compatibility & Fallback Concerns

**CSS Anchor Positioning Not Universally Supported:**
- Risk: Plugin uses CSS Anchor Positioning for popover placement (required feature per CLAUDE.md). Not supported in Safari or Firefox.
- Files: `assets/css/glossary.css:57-60`
- Current mitigation: None documented; popovers still display but positioning may fail
- Recommendations:
  1. Add fallback positioning CSS for non-supporting browsers (e.g., `top: auto; left: auto;` with viewport-aware fallback)
  2. Consider JavaScript fallback positioning for non-supporting browsers
  3. Document browser support limitations prominently in README

**Popover API Polyfill Missing:**
- Risk: Plugin requires Popover API (Chrome 114+, Safari 17+, Firefox experimental). IE 11 and older Safari versions cannot run this plugin at all.
- Files: `assets/js/glossary.js` uses `popover.showPopover()` and `popover.hidePopover()`
- Current mitigation: None; JavaScript will error silently in unsupported browsers
- Recommendations:
  1. Add [popover-polyfill](https://github.com/oddbird/popover-polyfill) for broader compatibility
  2. Or add feature detection and graceful degradation (show on hover instead of click)
  3. Document minimum browser versions clearly

## Security Considerations

**Potential XSS in Popover HTML:**
- Risk: `create_popover()` (line 295-334) uses `esc_html()` for term and title, but sanitization could be insufficient if user-controlled data flows in
- Files: `includes/class-content-filter.php:295-334`
- Current mitigation: Short description is `sanitize_textarea_field()`, long description is `wp_kses_post()`, title is from post title which is escaped. Appears safe.
- Recommendations: Verify all paths through `create_popover()` data come from post meta (sanitized on save)

**Settings Sanitization Edge Case:**
- Risk: `sanitize_settings()` (line 230-261) uses regex `/[^a-z0-9]/` to sanitize HTML tag names, but doesn't validate that remaining string is a valid HTML tag. User could input "script" as a tag and it would pass through.
- Files: `includes/class-settings.php:240-250`
- Impact: Low - excluded tags are only used in regex pattern for splitting, not executed. But "script" as an excluded tag doesn't make semantic sense.
- Recommendations: Add whitelist of valid HTML tags or warn users that only standard HTML tags are supported

## Test Coverage Gaps

**No Unit Tests for Content Filter Logic:**
- What's not tested: Term matching algorithm, regex pattern building, multiple term handling, overlapping terms, case sensitivity matching
- Files: `includes/class-content-filter.php`
- Risk: Changes to regex patterns or term sorting could silently break term linking without detection
- Priority: High - this is the core functionality

**No Tests for Migration:**
- What's not tested: Data migration from old meta format to new consolidated format, edge cases with partial old data
- Files: `includes/class-migrations.php`
- Risk: Users upgrading from pre-1.1.0 could lose data silently if migration path has bugs
- Priority: High - data loss risk

**No Tests for Yoast SEO Integration:**
- What's not tested: Schema graph integration, conditional output based on WPSEO_VERSION, microdata output when Yoast inactive
- Files: `includes/class-schema.php`
- Risk: Dual-output could cause duplicate schema markup if either integration breaks
- Priority: Medium - affects SEO validity

**No Tests for Popover API Fallback:**
- What's not tested: Error handling if popover methods fail, behavior in unsupported browsers
- Files: `assets/js/glossary.js:77-103`
- Risk: Try-catch blocks exist but no verification they prevent page breaks
- Priority: Medium

## Fragile Areas

**Content Filter State Management:**
- Files: `includes/class-content-filter.php:25, 32, 39`
- Why fragile: Static properties (`$popover_counter`, `$popovers`, `$terms_found_on_page`) reset at start of `filter_content()` but are accessed elsewhere. If multiple content filters run in sequence or `filter_content()` is called recursively, state could be corrupted.
- Safe modification: Add context parameter to track which post is being filtered; validate state before reset
- Test coverage: Edge case test for nested/recursive content filtering

**Sorting by Term Length:**
- Files: `includes/class-content-filter.php:169-177`
- Why fragile: `usort()` with anonymous function that calls `strlen()` on array elements. If synonyms contain objects or non-strings, it breaks silently.
- Safe modification: Add type checking before `strlen()`; validate `$a['terms']` and `$b['terms']` are arrays of strings
- Test coverage: Add test with malformed entry data

**Block Schema Generation:**
- Files: `includes/class-blocks.php:98`
- Why fragile: `Schema::get_entry_microdata_attributes()` called without passing entry data, but method doesn't use parameters. Returns same string every time.
- Safe modification: Remove unused parameters from schema methods or pass entry data if needed
- Test coverage: Verify schema methods work correctly with and without Yoast SEO

## Scaling Limits

**Database Query Limit:**
- Current capacity: Plugin loads all glossary entries (with `posts_per_page => -1`) into memory every page load
- Limit: At ~500 entries, expect noticeable slowdown; at 2000+ entries, query becomes problematic
- Scaling path: Implement transient caching (mentioned in Performance section); consider pre-filtering entries by category or tag; add admin setting to limit entries processed

**Frontend Popover Count:**
- Current capacity: Each popover consumes HTML, CSS, and JavaScript event listeners. Plugin appends all popovers at end of content.
- Limit: With 100 terms on one page, 100 popovers are rendered (even if only a few are used)
- Scaling path: Use event delegation instead of individual popover elements; lazy-load popover content on first click

## Dependencies at Risk

**jQuery Dependency for Admin:**
- Risk: `class-meta-boxes.php:275` enqueues jQuery for synonym management. jQuery is being phased out by WordPress core; future WordPress versions may not bundle it.
- Impact: Synonym add/remove buttons would break if jQuery is unavailable
- Migration plan: Rewrite `render_synonyms_script()` (line 284-307) in vanilla JavaScript to remove jQuery dependency

**Yoast SEO Integration Fragility:**
- Risk: Plugin checks `defined('WPSEO_VERSION')` in multiple places. If Yoast changes constant name or initialization order, checks fail silently
- Files: `includes/class-schema.php:25`, `includes/class-post-type.php:27-28`, `includes/class-blocks.php:76, 104, 134`
- Impact: Schema markup output could be incorrect or duplicated
- Migration plan: Create a single utility method `is_yoast_seo_active()` in Settings or Schema class; centralize version detection

## Missing Critical Features

**No Post Type Caching:**
- Problem: Every page load queries all glossary entries even if none exist. No check for glossary post count before querying.
- Blocks: Glossary can only work if entries exist; users can't use partial setups
- Fix approach: Add early return check for glossary post count (via transient) before running expensive queries

**No Bulk Edit Support:**
- Problem: Synonyms can only be edited entry-by-entry via meta box. Bulk editing is not supported.
- Blocks: Users with hundreds of entries can't batch-update settings like `disable_autolink` or `case_sensitive`
- Fix approach: Add support for WordPress bulk edit actions via `bulk_actions-post-pp_glossary` filter

**No REST API Support for Glossary Entries:**
- Problem: Plugin doesn't expose glossary entries via REST API (`show_in_rest: false` would be more explicit, though plugin does set `show_in_rest: true`)
- Blocks: Headless WordPress or external apps can't access glossary data
- Fix approach: Verify REST API works; add REST controller if needed

## Known Issues / Edge Cases

**Adjacent HTML Tag Matching:**
- Problem: Regex pattern `/\b({term})\b(?![^<]*>)/u` uses negative lookahead to avoid matches inside HTML tags, but only checks for closing `>`. If content has unclosed tags or CDATA sections, pattern could match incorrectly.
- Trigger: Content with malformed HTML (e.g., `<strong>term` without closing `</strong>`)
- Workaround: Ensure content is valid HTML before publishing
- Files: `includes/class-content-filter.php:232`

**Synonym Collision Not Prevented:**
- Problem: Multiple entries can have identical synonyms. First match wins, so synonyms assigned to later entries won't match if the same synonym is in an earlier entry.
- Trigger: User adds "API" as synonym for both "Application Programming Interface" and "API Key" entries
- Workaround: None; user must ensure synonyms are unique across all entries
- Fix approach: Add validation on entry save to warn if synonym matches another entry's title or existing synonyms

**Case Sensitivity Partial Implementation:**
- Problem: `case_sensitive` flag only works for individual terms within an entry, not for synonyms. If entry has `case_sensitive: true` but a synonym doesn't match case, it still matches (because synonym is treated as a separate term without case-sensitivity info)
- Trigger: Entry "CLS" with `case_sensitive: true`, synonym "cls", search for "cls" - it will match the synonym even though main term wouldn't
- Workaround: Don't use synonyms when case sensitivity is needed, or ensure synonyms match case exactly
- Fix approach: Apply `case_sensitive` setting to all terms and synonyms, not just the main term

**Glossary Page Not Set Warning:**
- Problem: If glossary page is not configured in settings, "Read more" links in popovers don't appear, but no warning is shown to users
- Trigger: User forgets step 2 of setup (configure glossary page)
- Workaround: User must manually check settings
- Fix approach: Add admin notice on glossary entry pages if `get_glossary_page_url()` returns empty string
