# Architecture

**Analysis Date:** 2026-02-04

## Pattern Overview

**Overall:** Class-based component architecture with static singleton pattern for initialization.

**Key Characteristics:**
- Plugin-oriented architecture using WordPress hooks and filters
- Separation of concerns via distinct classes for each functional domain
- Static class methods for initialization and public entry points
- Lazy initialization with conditional loading based on admin vs frontend context
- Namespace-based auto-loading using PSR-4-style conventions

## Layers

**Initialization Layer:**
- Purpose: Bootstrap plugin components and coordinate initialization
- Location: `pp-glossary.php` (main plugin file) and each class's `init()` method
- Contains: Autoloader registration, hook setup, component activation
- Depends on: None (entry point)
- Used by: WordPress `plugins_loaded` and `admin_init`/`init` hooks

**Data Management Layer:**
- Purpose: Define, store, and retrieve glossary entry data
- Location: `includes/class-meta-boxes.php`, `includes/class-migrations.php`
- Contains: Post type field definitions, custom meta box rendering, data serialization/deserialization
- Depends on: WordPress post meta API
- Used by: Content_Filter, Blocks, Schema

**Content Processing Layer:**
- Purpose: Transform content by replacing glossary terms with interactive elements
- Location: `includes/class-content-filter.php`
- Contains: Term matching algorithm, popover HTML generation, state management during filtering
- Depends on: Meta_Boxes (for entry data), Settings (for configuration)
- Used by: Frontend `the_content` filter (priority 20)

**Presentation Layer:**
- Purpose: Render glossary UI and manage user interactions
- Location: `includes/class-blocks.php`, `blocks/glossary-list/`, `assets/css/glossary.css`, `assets/js/glossary.js`
- Contains: Block rendering, alphabetical grouping, accessibility markup
- Depends on: WordPress REST API for block metadata
- Used by: Gutenberg editor and frontend

**Configuration Layer:**
- Purpose: Store and manage plugin settings
- Location: `includes/class-settings.php`
- Contains: Settings page UI, option sanitization, getter methods for configuration values
- Depends on: WordPress settings API
- Used by: Content_Filter, Blocks, Schema

**Post Type Layer:**
- Purpose: Register and configure custom post type
- Location: `includes/class-post-type.php`
- Contains: Post type registration arguments, Yoast SEO exclusion logic
- Depends on: WordPress post type API
- Used by: WordPress core

**Metadata Layer:**
- Purpose: Enrich content with semantic HTML and structured data
- Location: `includes/class-schema.php`
- Contains: Schema.org DefinedTermSet/DefinedTerm generation, Yoast SEO integration
- Depends on: Settings (for glossary page ID)
- Used by: Blocks (microdata attributes), Yoast SEO schema graph

**Assets Layer:**
- Purpose: Load CSS and JavaScript conditionally based on content presence
- Location: `includes/class-assets.php`
- Contains: Script/style enqueueing with deferred loading strategy
- Depends on: Content_Filter (to check if terms were found)
- Used by: Frontend

## Data Flow

**On Page Render (Frontend):**

1. WordPress loads page content
2. `the_content` filter fires (priority 20)
3. `Content_Filter::filter_content()` executes:
   - Checks if filtering should be skipped (is_feed, is REST request, is glossary page, post type excluded)
   - Queries all published `pp_glossary` posts with their meta data
   - Sorts entries by longest term first to handle overlapping terms
   - For each entry, calls `replace_first_occurrence()`:
     - Splits content by excluded HTML tags
     - Searches for term in safe content chunks
     - Replaces first occurrence with `<dfn>` + `<button>` markup
     - Appends popover HTML to internal storage
   - Appends all popovers to end of content
   - Sets `Content_Filter::$terms_found_on_page = true`
4. `Assets::enqueue_assets()` executes in `wp_footer`:
   - Checks if `$terms_found_on_page` is true
   - If true, enqueues CSS (`assets/css/glossary.css`) and JS (`assets/js/glossary.js`)
5. Frontend JavaScript attaches click handlers to trigger popovers

**On Glossary Entry Save (Admin):**

1. User saves glossary entry via edit screen
2. `save_post_pp_glossary` hook fires
3. `Meta_Boxes::save_meta_boxes()` executes:
   - Validates nonce
   - Sanitizes all field data (short description, long description, synonyms, checkboxes)
   - Saves consolidated array to `_pp_glossary_data` post meta
4. Nonce and capability checks prevent unauthorized saves

**On Glossary Block Render (Frontend):**

1. WordPress processes block
2. `Blocks::render_glossary_list_block()` executes:
   - Queries all published `pp_glossary` posts
   - Retrieves meta data for each entry
   - Groups entries alphabetically by title first letter
   - Generates navigation links for each letter
   - For each letter group, renders entry details with synonyms and descriptions
   - Includes edit links for users with capabilities
   - Calls `Schema::get_microdata_attributes()` to add schema markup (empty if Yoast SEO active)
   - Returns HTML string

**On Yoast SEO Schema Generation:**

1. If Yoast SEO is active and glossary page is being rendered
2. `wpseo_schema_graph` filter fires (priority 10)
3. `Schema::add_to_yoast_schema_graph()` executes:
   - Verifies current page is glossary page
   - Queries all published glossary entries
   - Creates DefinedTermSet schema object with page metadata
   - For each entry, creates DefinedTerm child schema with title, description, URL, and synonyms
   - Returns modified graph array
4. Yoast outputs JSON-LD via their schema system

**On Plugin Upgrade:**

1. `Migrations::init()` runs during `init` hook
2. `run_migrations()` checks stored database version
3. If version is pre-1.1.0, calls `migrate_to_1_1_0()`:
   - Iterates through all glossary posts
   - Reads old separate meta keys (`_pp_glossary_short_description`, etc.)
   - Consolidates into single `_pp_glossary_data` array
   - Deletes old meta keys
   - Updates stored db_version

**State Management:**

`PP_Glossary_Content_Filter` uses static properties during filtering:
- `$popover_counter` - incremented for each term replacement to ensure unique IDs
- `$popovers` - array collecting popover HTML for end-of-content appending
- `$terms_found_on_page` - flag indicating whether any terms were replaced (used by Assets layer)

These reset on each `filter_content()` call to ensure proper state isolation.

## Key Abstractions

**Glossary Entry:**
- Purpose: Represents a single dictionary entry with metadata
- Examples: Any post of type `pp_glossary`
- Pattern: Post title + consolidated post meta array
- Structure: `{ id, slug, title, terms[], short_description, long_description, case_sensitive, disable_autolink }`

**Popover HTML Element:**
- Purpose: Interactive container displaying term definition on click
- Pattern: Uses `popover="auto"` API for light-dismiss and mutual exclusivity
- Structure: `<aside id="{id}" popover="auto" role="tooltip">` with "Read more" link + short description

**Term Button:**
- Purpose: Clickable trigger for popover display
- Pattern: Wrapped in semantic `<dfn>` element using CSS Anchor Positioning
- Structure: `<dfn id="{id}"><button data-glossary-popover="{popover-id}">` with aria-expanded

**Settings Object:**
- Purpose: Central configuration source for runtime behavior
- Pattern: Static getter methods returning option values with defaults
- Methods: `get_glossary_page_id()`, `get_glossary_page_url()`, `get_excluded_tags()`, `get_excluded_post_types()`

## Entry Points

**Plugin Activation:**
- Location: `pp-glossary.php` line 94
- Triggers: `register_activation_hook()`
- Responsibilities: Initializes plugin, flushes rewrite rules

**Plugin Initialization:**
- Location: `pp-glossary.php` line 67-83 (`pp_glossary_init()` function)
- Triggers: `plugins_loaded` hook
- Responsibilities: Instantiates all component classes' `init()` methods in order:
  - Always: Settings, Post_Type, Blocks, Schema
  - Admin only: Meta_Boxes, Migrations
  - Frontend only: Content_Filter, Assets

**Frontend Content Processing:**
- Location: `includes/class-content-filter.php` line 48
- Triggers: `the_content` filter at priority 20
- Responsibilities: Scans post content for glossary terms and generates interactive markup

**Glossary Block Rendering:**
- Location: `includes/class-blocks.php` line 55
- Triggers: Block render when Gutenberg inserts `pp-glossary/glossary-list` block
- Responsibilities: Outputs full alphabetical glossary with navigation and schema

**Admin Settings Page:**
- Location: `includes/class-settings.php` line 42
- Triggers: Admin menu link at Glossary > Settings
- Responsibilities: Displays form to select glossary page and configure exclusions

**Meta Box Display:**
- Location: `includes/class-meta-boxes.php` line 32
- Triggers: `add_meta_boxes` hook on pp_glossary post type
- Responsibilities: Renders custom fields (short description, long description, synonyms, checkboxes)

## Error Handling

**Strategy:** Silent failure with fallback behavior - never interrupt user experience

**Patterns:**

- **Term Matching Failures:** If regex pattern compilation fails, `preg_split()` returns false and content is returned unchanged (`includes/class-content-filter.php` line 213)
- **Missing Glossary Page:** If no glossary page is set, "Read more" links are omitted from popovers (`includes/class-content-filter.php` line 268)
- **Empty Entry Data:** Default values provided via `wp_parse_args()` in `Meta_Boxes::get_entry_data()` ensures type safety
- **Missing Schema:** Conditional output checks for Yoast SEO presence; microdata only output when Yoast is inactive
- **Database Queries:** Empty results handled by checking post counts before iteration

No exceptions are thrown; errors degrade gracefully with conservative defaults.

## Cross-Cutting Concerns

**Logging:** No logging framework used. WordPress `error_log()` not explicitly called.

**Validation:**

- **Frontend Input:** Escaping applied consistently:
  - `esc_attr()` for HTML attributes (button IDs, aria-* values)
  - `esc_html()` for text content (term titles)
  - `wp_kses_post()` for rich content (long descriptions in popovers)
- **Admin Input:** Sanitization via callbacks:
  - `Settings::sanitize_settings()` sanitizes all option values
  - `Meta_Boxes::save_meta_boxes()` sanitizes all field inputs
- **Query Parameters:** Case-sensitive flag defaults to false; excluded tags/post types validated against arrays

**Authentication & Authorization:**

- Settings page requires `manage_options` capability
- Edit links in glossary block check `current_user_can( 'edit_post', $post_id )`
- Meta box saves check nonce `wp_verify_nonce()`
- No special permissions required to view glossary content (public-facing)

**Internationalization:**

- All user-facing strings wrapped in `__()` or `esc_html__()` with `pp-glossary` text domain
- Defined in class properties or called inline in templates
- No dynamic string concatenation for i18n
- Supported by `load_plugin_textdomain()` (implied in standard WordPress plugin structure)

**Accessibility:**

- Button elements used instead of spans for keyboard interaction
- `aria-expanded` attribute updated on toggle state
- Popover role set to `tooltip` for screen readers
- Popovers positioned using CSS Anchor Positioning (no JS positioning required)
- Click-based interaction (not hover) for universal accessibility
- Navigation uses semantic `<nav>` element with aria-label
- Read more link appears before description in popover for screen reader flow

---

*Architecture analysis: 2026-02-04*
