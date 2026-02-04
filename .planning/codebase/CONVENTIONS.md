# Coding Conventions

**Analysis Date:** 2026-02-04

## Naming Patterns

**Files:**
- Class files: `class-{name}.php` (lowercase with hyphens), e.g., `class-post-type.php`, `class-meta-boxes.php`
- Placed in `includes/` directory
- Special files: `functions.php` for helper functions, `pp-glossary.php` for main plugin entry point

**Functions:**
- Global functions: `pp_glossary_{action}()` (prefix with plugin namespace), e.g., `pp_glossary_strtolower()`, `pp_glossary_autoloader()`
- Static methods: `public static function method_name(): return_type`
- Callback methods: Use `[ __CLASS__, 'method_name' ]` array syntax for hook callbacks
- Snake_case for all PHP function names

**Variables:**
- Snake_case for all variables: `$post_id`, `$glossary_entries`, `$excluded_tags`
- Static properties prefixed appropriately: `private static $popover_counter`, `private static $popovers`
- Array keys: Snake_case, e.g., `$data['short_description']`, `$entry['long_description']`

**Classes:**
- PascalCase with underscores: `Post_Type`, `Meta_Boxes`, `Content_Filter`, `Schema`
- Namespace: `namespace PP_Glossary;`
- All classes in `includes/` directory with `class-` prefix

**Types/Constants:**
- Global constants: `UPPERCASE_WITH_UNDERSCORES`, e.g., `PP_GLOSSARY_VERSION`, `PP_GLOSSARY_PLUGIN_DIR`
- Class constants: `UPPERCASE`, e.g., `OPTION_NAME`, `DEFAULT_EXCLUDED_TAGS`
- Plugin text domain: `'pp-glossary'` (used in all `__()`, `_e()`, `_x()` calls)

## Code Style

**Formatting:**
- Indentation: Tab characters (WordPress standard)
- Line length: No hard limit, readability focused
- Spacing: 1 blank line between methods, double blank lines between major sections
- Braces: Opening brace on same line as statement (K&R style)
- Arrays: Short array syntax `[]` enforced (Generic.Arrays.DisallowLongArraySyntax in phpcs.xml)

**Linting:**
- Tool: PHP CodeSniffer (PHPCS) with WordPress Coding Standards
- Config: `phpcs.xml` at project root
- Run: `composer run check-cs` (check) or `composer run fix-cs` (auto-fix)
- Standards applied:
  - `WordPress-Extra` - Enhanced WordPress standards
  - `WordPress-Docs` - Documentation requirements
  - `PHPCompatibilityWP` - PHP 7.4+ compatibility (minimum version)
- Min WordPress version: 6.3
- Exclusions: vendor/, node_modules/, coverage/
- Custom configuration:
  - Text domain: `pp-glossary`
  - Global prefixes: `PP_Glossary`, `pp_glossary`
  - Enforces short array syntax
  - Disables Yoda conditions check

**Static Analysis:**
- Tool: PHPStan at level 7
- Config: `phpstan.neon.dist`
- Run: `composer run phpstan`
- Scans: `pp-glossary.php` and `includes/` directory
- Ignores: Plugin constants (`PP_GLOSSARY_VERSION`, `PP_GLOSSARY_PLUGIN_DIR`, `PP_GLOSSARY_PLUGIN_URL`) defined in main file

## Import Organization

**Order:**
1. Comment header with file description and @package tag
2. Namespace declaration
3. Direct file abort check (`if ( ! defined( 'WPINC' ) ) { die; }`)
4. Class declaration (no explicit imports needed - WordPress autoloading via class names)

**Pattern in main plugin file (`pp-glossary.php`):**
```php
<?php
/**
 * Plugin Name: Glossary
 * ... metadata ...
 * @package PP_Glossary
 */

// Direct file abort check
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define constants
define( 'PP_GLOSSARY_VERSION', '1.2.0' );
// ... more constants ...

// Require functions file
require_once PP_GLOSSARY_PLUGIN_DIR . 'includes/functions.php';

// Register autoloader
spl_autoload_register( 'pp_glossary_autoloader' );

// Initialization
add_action( 'plugins_loaded', 'pp_glossary_init' );
```

**Autoloading:**
- Custom PSR-0-like autoloader in `pp-glossary.php` (line 41-62)
- Converts class name `PP_Glossary\Settings` to `includes/class-settings.php`
- Format: `class-{lowercase-name-with-hyphens}.php`

## Error Handling

**Patterns:**
- Silent returns with early exit pattern: `if ( ! condition ) { return; }`
- Nonce verification with sanitization: `wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'action' )`
- Capability checks: `if ( ! current_user_can( 'manage_options' ) ) { return; }`
- Autosave check: `if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }`
- Sanitization always before save: `sanitize_textarea_field()`, `sanitize_text_field()`, `wp_kses_post()`
- Escaping on output: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- Regex failure handling: `if ( false === $result ) { return $fallback; }`

**Example from `class-meta-boxes.php` (line 206-225):**
```php
public static function save_meta_boxes( $post_id ): void {
	// Check nonce
	if ( ! isset( $_POST['pp_glossary_meta_box_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pp_glossary_meta_box_nonce'] ) ), 'pp_glossary_meta_box' ) ) {
		return;
	}

	// Check autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check permissions
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	// ... proceed with safe operations ...
}
```

## Logging

**Framework:** `console.error()` for JavaScript, no PHP logging framework used

**Patterns:**
- JavaScript: Error logging in try-catch blocks with context
- Example from `assets/js/glossary.js` (line 78-85):
```javascript
function showPopover(popover, trigger) {
	try {
		if (!popover.matches(':popover-open')) {
			popover.showPopover();
			trigger.setAttribute('aria-expanded', 'true');
		}
	} catch (error) {
		console.error('Error showing popover:', error);
	}
}
```
- No production logging - errors caught but not persisted

## Comments

**When to Comment:**
- Complex algorithms (term matching with regex, sorting by term length)
- Non-obvious logic (why early return is safe)
- Section separators for major code blocks
- Before conditionals that aren't self-documenting

**JSDoc/TSDoc:**
- Full JSDoc used in JavaScript with `@param`, `@return` tags
- PHPDoc used for all PHP classes and public methods
- Format: `/** ... */` (multi-line comment blocks)
- Tag format: `@param {Type} $variable Description`, `@return type Description`

**Example from `class-content-filter.php` (line 182-187):**
```php
/**
 * Replace first occurrence of glossary terms in content
 *
 * @param string               $content The content.
 * @param array<string, mixed> $entry   The glossary entry data.
 * @return string Modified content.
 */
private static function replace_first_occurrence( $content, $entry ): string {
```

**Example from `assets/js/glossary.js` (line 70-76):**
```javascript
/**
 * Show a popover.
 *
 * @param {HTMLElement} popover The popover element.
 * @param {HTMLElement} trigger The trigger element.
 */
function showPopover(popover, trigger) {
```

## Function Design

**Size:**
- Methods typically 20-50 lines
- Longest method is `replace_first_occurrence()` (~77 lines) due to regex complexity
- Most are 10-30 lines (focused, single responsibility)

**Parameters:**
- Use type hints: `( string $text, int $post_id ): string`
- Array types documented: `array<int, string>`, `array<string, mixed>`
- Nullable types: `?int $length = null`
- Avoid parameter bundling - explicit parameters preferred

**Return Values:**
- Always include return type: `: void`, `: string`, `: array`
- Void for hook callbacks and side-effect functions
- String for HTML generation
- Array for data queries
- Array values reset between function calls when used with static storage

**Example from `class-blocks.php` (line 55):**
```php
public static function render_glossary_list_block() {
	// Uses self::get_grouped_entries() -> array
	// Uses output buffering (ob_start/ob_get_clean)
	// Returns string HTML
}
```

## Module Design

**Exports:**
- All classes are static (no instantiation)
- Public static methods are the "exports"
- Private static methods for internal helpers
- No public properties - all private static

**Pattern in all classes:**
```php
class Settings {
	// Public configuration constants
	const OPTION_NAME = 'pp_glossary_settings';

	// Public static methods (entry points)
	public static function init(): void { ... }
	public static function get_glossary_page_id(): int { ... }

	// Private static methods (helpers)
	private static function has_glossary_posts(): bool { ... }
}
```

**Barrel Files:**
- Not used - direct class imports via namespace
- Autoloader handles all file loading

## WordPress Hook Integration

**Patterns:**
- Always use array syntax: `add_action( 'hook_name', [ __CLASS__, 'method_name' ] )`
- Hook priorities specified when needed: `add_filter( 'the_content', ..., 20 )`
- Unhooked only on deactivation (via register_deactivation_hook)
- All filter callbacks return modified value
- All action callbacks modify state or output, return void

**Hook List (from `CLAUDE.md`):**
- `plugins_loaded` → Initialize all components
- `init` → Register post type, blocks, settings
- `add_meta_boxes` → Register meta boxes
- `save_post_pp_glossary` → Save custom field data
- `admin_enqueue_scripts` → Admin JavaScript
- `the_content` (priority 20) → Filter content for term replacement
- `wp_enqueue_scripts` → Frontend CSS/JS
- `admin_menu` → Add settings page
- `admin_init` → Register settings
- `wpseo_schema_graph` (priority 10) → Yoast SEO integration
- `register_activation_hook` → Flush rewrite rules
- `register_deactivation_hook` → Clean up

## Prefixing Convention

**Global symbols must have `pp_glossary_` or `PP_Glossary` prefix:**
- Functions: `pp_glossary_strtolower()`
- Classes: `PP_Glossary\Settings`
- Hooks: `pp_glossary_disabled_post_types`, `pp_glossary_excluded_tags`
- Post type: `pp_glossary`
- Options: `pp_glossary_settings`
- Meta keys: `_pp_glossary_data` (leading underscore per WordPress meta convention)
- CSS classes: `pp-glossary-*`, `.glossary-*`
- JavaScript variables: None global (IIFE wrapper at line 9 in `assets/js/glossary.js`)

## Internationalization

**Text Domain:** `'pp-glossary'` (always used)

**Patterns:**
- User-facing strings: `__( 'Text', 'pp-glossary' )` or `_e()` for direct output
- Context strings: `_x( 'Text', 'context', 'pp-glossary' )`
- All strings are translatable via WordPress.org

**Example:**
```php
$labels = [
	'name' => _x( 'Glossary', 'Post Type General Name', 'pp-glossary' ),
	'singular_name' => _x( 'Entry', 'Post Type Singular Name', 'pp-glossary' ),
];
```

---

*Convention analysis: 2026-02-04*
