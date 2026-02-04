<?php
/**
 * Term Linker Utility
 *
 * @package PP_Glossary
 */

namespace PP_Glossary;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Term_Linker
 *
 * Static utility for transforming text by linking glossary terms.
 */
class Term_Linker {

	/**
	 * Cache for glossary entries (request-level)
	 *
	 * @var array<int, array<string, mixed>>|null
	 */
	private static $entries_cache = null;

	/**
	 * Link glossary terms in text
	 *
	 * @param string $text       The text to process.
	 * @param int    $exclude_id Entry ID to exclude from linking (prevents self-links).
	 * @return string Modified text with linked terms.
	 */
	public static function link_terms_in_text( string $text, int $exclude_id = 0 ): string {
		// Return early if text is empty.
		if ( empty( $text ) ) {
			return $text;
		}

		// Return early if no glossary page URL is set.
		$glossary_url = Settings::get_glossary_page_url();
		if ( empty( $glossary_url ) ) {
			return $text;
		}

		// Get entries (cached).
		$entries = self::get_entries();

		if ( empty( $entries ) ) {
			return $text;
		}

		// Process each entry.
		foreach ( $entries as $entry ) {
			// Skip the excluded entry to prevent self-links.
			if ( $entry['id'] === $exclude_id ) {
				continue;
			}

			$text = self::replace_first_term_occurrence( $text, $entry, $glossary_url );
		}

		return $text;
	}

	/**
	 * Get glossary entries
	 *
	 * @return array<int, array<string, mixed>> Array of glossary entries.
	 */
	private static function get_entries(): array {
		// Return from cache if available.
		if ( null !== self::$entries_cache ) {
			return self::$entries_cache;
		}

		$entries = [];

		$query = new \WP_Query(
			[
				'post_type'      => 'pp_glossary',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = (int) get_the_ID();

				$data = Meta_Boxes::get_entry_data( $post_id );

				// Skip entries that have auto-linking disabled.
				if ( $data['disable_autolink'] ) {
					continue;
				}

				// Build array of terms (title + synonyms).
				$terms = [ get_the_title() ];

				if ( ! empty( $data['synonyms'] ) && is_array( $data['synonyms'] ) ) {
					foreach ( $data['synonyms'] as $synonym ) {
						if ( ! empty( $synonym ) ) {
							$terms[] = $synonym;
						}
					}
				}

				$entries[] = [
					'id'             => $post_id,
					'slug'           => sanitize_title( get_the_title() ),
					'title'          => get_the_title(),
					'terms'          => $terms,
					'case_sensitive' => $data['case_sensitive'],
				];
			}
			wp_reset_postdata();
		}

		// Sort by longest term first to handle overlapping terms correctly.
		usort(
			$entries,
			function ( $a, $b ) {
				$max_len_a = max( array_map( 'strlen', $a['terms'] ) );
				$max_len_b = max( array_map( 'strlen', $b['terms'] ) );
				return $max_len_b - $max_len_a;
			}
		);

		// Store in cache.
		self::$entries_cache = $entries;

		return $entries;
	}

	/**
	 * Replace first occurrence of any term in entry
	 *
	 * @param string               $text         The text to process.
	 * @param array<string, mixed> $entry        The glossary entry data.
	 * @param string               $glossary_url The glossary page URL.
	 * @return string Modified text.
	 */
	private static function replace_first_term_occurrence( string $text, array $entry, string $glossary_url ): string {
		// Try each term until we find a match.
		foreach ( $entry['terms'] as $term ) {
			// Build regex pattern.
			// Use case-insensitive matching unless the entry is marked as case sensitive.
			$flags   = $entry['case_sensitive'] ? 'u' : 'iu';
			$pattern = '/\b(' . preg_quote( $term, '/' ) . ')\b(?![^<]*>)/' . $flags;

			// Find first match with offset.
			if ( preg_match( $pattern, $text, $matches, PREG_OFFSET_CAPTURE ) ) {
				$matched_term = $matches[1][0];
				$offset       = $matches[1][1];

				// Create link HTML.
				$link_html = sprintf(
					'<a href="%s#%s" class="pp-glossary-link">%s</a>',
					esc_url( $glossary_url ),
					esc_attr( $entry['slug'] ),
					esc_html( $matched_term )
				);

				// Replace only this occurrence.
				$text = substr_replace( $text, $link_html, $offset, strlen( $matched_term ) );

				// Return immediately - only replace first occurrence.
				return $text;
			}
		}

		return $text;
	}
}
