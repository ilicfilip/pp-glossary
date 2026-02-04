# Testing Patterns

**Analysis Date:** 2026-02-04

## Test Framework

**Status:** No testing framework detected

- No PHPUnit configuration (no `phpunit.xml`)
- No test directory (no `tests/` folder)
- No test files (no `*.test.php` or `*.spec.php` files)
- No Jest, Vitest, or JavaScript test runner configured

**Development Tools Available (in composer.json):**
- `phpcs` / `phpcbf` - Code sniffer and fixer
- `phpstan` - Static analysis
- `php-parallel-lint` - Syntax checker
- No unit/integration test runner

**Composition:**
- Code quality relies entirely on static analysis via PHPStan and PHPCS
- No runtime assertions or automated test coverage
- Manual testing required for functionality

## Code Quality Tools (Used Instead of Tests)

**PHPStan (Static Analysis):**
```bash
composer run phpstan
```
- Level 7 analysis
- Scans `pp-glossary.php` and `includes/` directory
- Catches type errors, undefined variables, incorrect method calls
- Configuration: `phpstan.neon.dist`

**PHP CodeSniffer (Coding Standards):**
```bash
composer run check-cs
```
- WordPress-Extra standards
- WordPress-Docs standards
- PHP compatibility (7.4+)
- Configuration: `phpcs.xml`

**PHP Parallel Lint (Syntax Checking):**
```bash
composer run lint
```
- Checks PHP syntax across entire codebase
- Excludes vendor/, node_modules/, .git/

**Combined Verification (as per CLAUDE.md):**
```bash
composer check-cs   # Check WordPress coding standards
composer lint       # Check PHP syntax errors
composer phpstan    # Static analysis for type errors
```
All three must pass before changes are considered complete.

## Test Structure

**Manual Testing Approach (from CLAUDE.md):**

### General Testing Checklist:
- Test with overlapping terms (e.g., "CLS" and "Cumulative Layout Shift")
- Check keyboard navigation (Tab, Enter, Space, Escape)
- Test with screen reader to verify ARIA attributes
- Verify terms maintain surrounding text color
- Check that only first occurrence is linked per term
- Test synonym functionality (add/remove in admin)
- Verify glossary page doesn't highlight its own terms
- Check popover positioning near viewport edges
- Test case-sensitive matching (enable on entry, verify exact case required)
- Test disable auto-linking (enable on entry, verify it appears in glossary but not linked in content)
- Verify only one popover can be open at a time
- Verify clicking outside popover closes it (light dismiss)
- Test excluded HTML tags setting (add/remove tags, verify terms are not highlighted)
- Test excluded post types setting (enable for a post type, verify terms are not highlighted)

### Schema Testing:

**With Yoast SEO:**
1. Install and activate Yoast SEO
2. View glossary page source
3. Look for JSON-LD script tag with `@type: "DefinedTermSet"`
4. Verify all entries appear in `hasDefinedTerm` array
5. Test with [Google Rich Results Test](https://search.google.com/test/rich-results)
6. Verify no duplicate schema markup appears

**Without Yoast SEO:**
1. Deactivate Yoast SEO
2. View glossary page source
3. Look for `itemscope itemtype="https://schema.org/DefinedTermSet"` on main div
4. Verify each entry has `itemscope itemtype="https://schema.org/DefinedTerm"`
5. Check all `itemprop` attributes are present (name, description, url)
6. Verify multiple `<meta itemprop="alternateName">` tags exist for each synonym
7. Test with [Google Rich Results Test](https://search.google.com/test/rich-results)
8. Verify Microdata is properly nested and alternateName appears as an array

**Validation:**
- Use [Schema.org Validator](https://validator.schema.org/)
- Use [Google Rich Results Test](https://search.google.com/test/rich-results)
- Check for any warnings or errors
- Verify all required properties are present

## Code Patterns Subject to Testing

**Core Logic Areas:**

### 1. Content Filtering (`includes/class-content-filter.php`)

**Complex Logic:**
- Term matching with regex: `/\b({term})\b(?![^<]*>)/u` (or `/iu` for case-insensitive)
- Excluded tags parsing and content splitting via `preg_split()`
- First occurrence replacement only (per entry)
- Case-sensitive/insensitive matching per entry
- Overlapping term handling (sorted by longest term first)

**Critical Methods:**
- `filter_content()` - Entry point, resets state
- `replace_first_occurrence()` - Complex regex and string manipulation
- `get_glossary_entries()` - Database query, sorting, filtering

**Edge Cases Requiring Testing:**
- Multiple occurrences of same term (only first should be linked)
- Overlapping terms ("CLS" vs "Cumulative Layout Shift")
- Terms inside excluded tags (should be skipped)
- HTML content inside content (should not break parsing)
- Empty glossary
- Case-sensitive matching enabled/disabled per entry
- Glossary page itself (should skip filtering)
- Feed and REST API requests (should skip filtering)

### 2. Meta Box Data (`includes/class-meta-boxes.php`)

**Critical Methods:**
- `save_meta_boxes()` - Nonce verification, sanitization, data structure
- `get_entry_data()` - Defaults, array merging, data retrieval

**Edge Cases:**
- Missing nonce
- Invalid nonce
- Autosave context
- User without edit capability
- Empty synonyms array
- HTML in long description (should preserve safe HTML)
- Special characters in synonyms (should be sanitized)
- Case-sensitive and disable-autolink checkboxes not checked (should be false)

### 3. Settings (`includes/class-settings.php`)

**Critical Methods:**
- `sanitize_settings()` - Complex array processing, tag normalization
- `get_settings()` - Defaults, merging
- `update_setting()` - Single key updates

**Edge Cases:**
- No settings saved (should use defaults)
- Glossary page deleted after being selected (should handle gracefully)
- Excluded tags with invalid characters (should be normalized to alphanumeric)
- Excluded post types no longer public (should still be stored)
- Empty comma-separated tags input (should result in empty array)

### 4. Schema Integration (`includes/class-schema.php`)

**Critical Methods:**
- `add_to_yoast_schema_graph()` - JSON-LD structure generation
- `get_microdata_attributes()` - Conditional output based on Yoast detection
- `create_defined_term_schema()` - Individual entry schema

**Edge Cases:**
- Yoast SEO active vs inactive (two different code paths)
- Glossary page not set (should not output schema)
- Missing entry descriptions (should use defaults)
- HTML in descriptions (should be stripped for schema)
- Synonyms array (should output as array in JSON-LD, as multiple meta tags in Microdata)
- No glossary page ID (should return empty string)

### 5. Block Rendering (`includes/class-blocks.php`)

**Critical Methods:**
- `render_glossary_list_block()` - Complex output buffering with embedded schema
- `get_grouped_entries()- Grouping by first letter, handling special characters

**Edge Cases:**
- No glossary entries
- Entry with no description (should fall back to short description)
- Entry starting with number (should group as "#")
- Entry in non-Latin script (Cyrillic, Greek - should group correctly)
- User without edit capability (edit link should not appear)
- Long descriptions with formatting preserved

### 6. JavaScript Functionality (`assets/js/glossary.js`)

**Critical Methods:**
- `setupClickPopovers()` - Event listeners, toggle logic
- `showPopover()` / `hidePopover()` - Try-catch error handling
- `setupSmoothScrolling()` - Alphabet navigation
- `maybeScrollOnPageLoad()` - Hash-based scrolling on page load

**Edge Cases:**
- Popover API not supported (try-catch should prevent errors)
- CSS Anchor Positioning not supported (popovers display but may not position correctly)
- Missing popover elements (querySelector returns null)
- Clicking popover link before popover fully displayed
- Escape key while inside popover (should close and refocus trigger)
- Back/forward browser navigation with hash

## Current Testing Approach

**Strategy:** Verification through static analysis + manual testing

The codebase currently uses:
1. **PHPCS** (`composer check-cs`) - Enforces WordPress Coding Standards
   - Catches naming violations, escaping issues, documentation gaps
   - Runs against: all `.php` files except vendor/

2. **PHPStan** (`composer phpstan`) - Type safety and logic errors
   - Level 7 analysis catches undefined variables, type mismatches
   - Configured to ignore plugin-defined constants

3. **Parallel Lint** (`composer lint`) - Syntax validation
   - Ensures no parse errors in PHP

**No Runtime Tests:**
- No PHPUnit for PHP unit tests
- No integration tests for WordPress hooks
- No Jest/Vitest for JavaScript
- No E2E tests for frontend interaction

## Recommendations for Future Testing Implementation

**If PHPUnit Testing Were Added:**

Structure would likely be:
```
tests/
├── phpunit.xml
├── bootstrap.php
├── unit/
│   ├── ContentFilterTest.php
│   ├── MetaBoxesTest.php
│   ├── SettingsTest.php
│   └── SchemaTest.php
├── integration/
│   ├── HooksTest.php
│   ├── BlocksTest.php
│   └── WordPressTest.php
└── fixtures/
    ├── sample-posts.php
    └── sample-settings.php
```

**If JavaScript Testing Were Added:**

Framework would likely be Vitest (lightweight, modern):
```
assets/__tests__/
├── glossary.test.js
├── setup.js
└── mocks/
    └── popover-api.mock.js
```

## Known Coverage Gaps

**No Automated Tests For:**
1. Content filtering with real WordPress content (HTML, special characters)
2. Meta box save/load cycles with various data types
3. Settings sanitization edge cases
4. Block rendering with different post statuses
5. Schema generation accuracy
6. JavaScript popover API interactions
7. Keyboard navigation accessibility
8. Form submission with invalid/missing data
9. Database queries with edge cases (empty results, corrupted data)
10. Yoast SEO integration (conditional code path)

**Mitigated By:**
- Static analysis (PHPCS, PHPStan) catches many errors before runtime
- Type hints on all methods
- Comprehensive escaping and sanitization
- CLAUDE.md detailed manual testing checklist (used before releases)
- Careful code review before release

## Testing Philosophy

This plugin prioritizes:
1. **Code Quality Tools** - Static analysis catches errors early
2. **Defensive Programming** - Type hints, sanitization, nonce/capability checks
3. **Clear Code** - Single responsibility methods, good naming, documentation
4. **Manual Testing Process** - Thorough checklist in CLAUDE.md before each release

Trade-off: Faster development iteration without test maintenance overhead, balanced by strict static analysis standards and documented manual testing procedures.

---

*Testing analysis: 2026-02-04*
