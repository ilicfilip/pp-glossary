# External Integrations

**Analysis Date:** 2026-02-04

## APIs & External Services

**None detected** - Plugin operates entirely within WordPress and uses no external APIs or third-party services.

## Data Storage

**Databases:**
- WordPress native database only
  - Storage mechanism: Post meta and options tables
  - Client: WordPress `WP_Query` API and meta functions
  - All glossary data stored as post custom post type `pp_glossary` with meta array in `_pp_glossary_data`
  - Settings stored in `pp_glossary_settings` option
  - No external database connections

**File Storage:**
- Local filesystem only - No external file storage integration
- Asset files stored in plugin directory: `assets/css/` and `assets/js/`

**Caching:**
- None - Plugin uses WordPress native query caching through object cache
- No Redis, Memcached, or other caching service integration

## Authentication & Identity

**Auth Provider:**
- None - Uses WordPress native capability system
- Checks: `current_user_can()` for edit access in blocks and admin

**User Roles:**
- Relies on WordPress user roles and capabilities
- Requires `edit_posts` capability to manage glossary entries
- Restricted to users with appropriate post type capabilities

## Monitoring & Observability

**Error Tracking:**
- None - No external error tracking service

**Logs:**
- WordPress debug logging only (`wp-config.php` if enabled)
- No external log aggregation or monitoring

## CI/CD & Deployment

**Hosting:**
- Any WordPress-compatible hosting (no specific platform requirements)
- Works on shared hosting, VPS, managed WordPress hosting
- No cloud platform specific integrations

**CI Pipeline:**
- Not detected in codebase - Uses GitHub Actions (see `.github/` directory)
- Quality checks run via Composer scripts defined in `composer.json`:
  - `composer check-cs` - WordPress coding standards
  - `composer lint` - PHP syntax validation
  - `composer phpstan` - Static type analysis
  - `composer fix-cs` - Automatic code fixing

## Environment Configuration

**Required env vars:**
- None - Plugin requires no environment variables

**Secrets location:**
- No secrets management - Plugin contains no API keys, credentials, or sensitive configuration
- Settings managed through WordPress admin interface (Glossary > Settings)

## Optional Integrations

**Yoast SEO Detection:**
- Soft integration: Plugin detects Yoast SEO via `defined( 'WPSEO_VERSION' )`
- Location: `includes/class-schema.php` (line 25, 162, 183, 198)
- Behavior:
  - If Yoast SEO active: Hooks into `wpseo_schema_graph` filter (priority 10) to add DefinedTermSet schema
  - If Yoast SEO inactive: Outputs Microdata markup directly in HTML
  - Both modes provide Schema.org structured data for glossary entries
- No dependency - Plugin works independently without Yoast SEO

**Filters & Hooks for Extension:**
- `pp_glossary_disabled_post_types` - Allow customization of post types where terms are not auto-linked (default: none)
  - Usage: `includes/class-content-filter.php` line 79
  - Example: `add_filter( 'pp_glossary_disabled_post_types', function( $types ) { return array_merge( $types, ['page'] ); } );`

- `pp_glossary_excluded_tags` - Customize HTML tags where terms should not be highlighted (default: `a`, `h1`-`h6`)
  - Usage: `includes/class-content-filter.php` line 200
  - Example: `add_filter( 'pp_glossary_excluded_tags', function( $tags ) { return array_merge( $tags, ['code', 'pre'] ); } );`

- `wpseo_indexable_excluded_post_types` - Excludes glossary CPT from Yoast indexables (automatic)
  - Usage: `includes/class-post-type.php` line 27

- `wpseo_sitemap_exclude_post_type` - Excludes glossary CPT from Yoast sitemaps (automatic)
  - Usage: `includes/class-post-type.php` line 28

## Webhooks & Callbacks

**Incoming:**
- None

**Outgoing:**
- None

## Migration & Data Compatibility

**Data Migration:**
- Automatic migration system in `includes/class-migrations.php`
- Converts legacy separate meta keys to consolidated `_pp_glossary_data` array format
- Runs once via `init` hook with version checking
- No external data sources or imports

## Security & Permissions

**Authentication:**
- WordPress nonce verification on form submissions (`wp_verify_nonce()`)
  - Usage: `includes/class-meta-boxes.php` line 212
- Capability checks for post type access

**Output Escaping:**
- All output properly escaped for security:
  - `esc_attr()` - HTML attributes
  - `esc_html()` - Text content
  - `esc_url()` - URLs
  - `wp_kses_post()` - WYSIWYG content allowing safe HTML
  - `sanitize_textarea_field()` - Field input sanitization
  - `sanitize_text_field()` - Text field input sanitization

---

*Integration audit: 2026-02-04*
