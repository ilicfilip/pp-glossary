# Technology Stack

**Analysis Date:** 2026-02-04

## Languages

**Primary:**
- PHP 7.4+ - Core plugin logic, hooks, filters, post type registration, Gutenberg blocks

**Frontend:**
- JavaScript (Vanilla) - Click-based popover behavior, keyboard accessibility, smooth scrolling
- CSS 3 - Styling, CSS Anchor Positioning API for popover placement

## Runtime

**Environment:**
- WordPress 6.0+
- PHP 7.4 or higher (as specified in `pp-glossary.php` and `composer.json`)

**Package Manager:**
- Composer - PHP dependency management
- Lockfile: `composer.lock` (present)

## Frameworks

**Core:**
- WordPress Core - All major plugin functionality uses native WordPress APIs
  - Custom Post Type (`pp_glossary`)
  - Meta boxes and custom fields
  - Gutenberg blocks
  - Admin settings pages
  - Hooks and filters system

**Block Development:**
- WordPress Gutenberg - Block registration using native `register_block_type()`
  - Editor interface: Vanilla JavaScript (see `blocks/glossary-list/editor.js`)
  - Server-side rendering via `render_callback`
  - No JSX, no build process

**Testing/Quality Assurance:**
- PHP CodeSniffer (PHPCS) 3.7+ - WordPress coding standard checks
- WordPress Coding Standards (WPCS) 3.0+ - WordPress-specific linting rules
- PHPStan 2.0+ - Static type analysis
- szepeviktor/phpstan-wordpress 2.0+ - WordPress-specific PHPStan rules
- PHP Parallel Lint 1.3+ - PHP syntax validation
- PHP CS Fixer 3.75+ - Automatic code style fixing

**Build/Dev:**
- Composer Installers 1.0+ - Package installation management
- PHPStan Extension Installer 1.4+ - Automatic extension loading
- phpcompatibility/phpcompatibility-wp - PHP compatibility checking

## Key Dependencies

**Critical:**
- WordPress Core Functions - All data retrieval and manipulation uses WordPress APIs exclusively
  - `WP_Query` - Glossary entry queries
  - `get_post_meta()`, `update_post_meta()` - Custom field storage
  - `wp_nonce_field()`, `wp_verify_nonce()` - Security
  - `wp_kses_post()`, `esc_attr()`, `esc_html()`, `esc_url()` - Output escaping
  - `register_post_type()`, `add_action()`, `add_filter()` - Registration

**Infrastructure:**
- None - Plugin uses only WordPress native functions and PHP standard library

## Browser Requirements

**Frontend Features:**
- Popover API (Chrome 114+, Safari 17+, Firefox experimental) - Required for popover functionality
- CSS Anchor Positioning API (Chrome/Edge 125+) - Required for optimal popover positioning
  - Graceful degradation: Popovers display but may not position optimally in unsupported browsers

**JavaScript:**
- Vanilla JavaScript (ES6+) - No frameworks, no transpilation needed
- No external JS libraries or dependencies

## Configuration

**Environment:**
- WordPress installation with plugin directory access
- PHP memory limit: Recommended 256MB minimum (for static analysis: 2048M for phpstan)
- No environment variables required for plugin operation

**Build/Development:**
- `phpcs.xml` - PHP CodeSniffer ruleset configuration
  - Uses WordPress-Extra standard
  - Checks 8 files in parallel
  - Excludes vendor, node_modules, coverage, .js, .css files

**PHP Compatibility Checked:**
- PHP 7.4 baseline (WordPress compatibility)
- Rules configured in `phpcs.xml` to catch compatibility issues

## Data Storage

**Database:**
- WordPress post meta (`_pp_glossary_data` key) - Single consolidated meta array
- WordPress options table (`pp_glossary_settings` key) - Plugin settings
- Standard WordPress posts table - CPT storage
- No external databases or APIs required

## Platform Requirements

**Development:**
- PHP 7.4+ CLI
- Composer (for dependency management)
- Text editor or IDE
- No build process or compilation needed

**Production:**
- WordPress 6.0+ installation
- PHP 7.4+ on hosting provider
- No additional server requirements beyond standard WordPress hosting
- Compatible with standard WordPress multisite installations

---

*Stack analysis: 2026-02-04*
