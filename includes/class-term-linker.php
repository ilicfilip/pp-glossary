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
	 * Link glossary terms in text
	 *
	 * @param string $text       The text to process.
	 * @param int    $exclude_id Entry ID to exclude from linking (prevents self-links).
	 * @return string Modified text with linked terms.
	 */
	public static function link_terms_in_text( string $text, int $exclude_id = 0 ): string {
		if ( empty( $text ) ) {
			return $text;
		}

		$glossary_url = Settings::get_glossary_page_url();
		if ( empty( $glossary_url ) ) {
			return $text;
		}

		$entries = self::get_linkable_entries();
		if ( empty( $entries ) ) {
			return $text;
		}

		foreach ( $entries as $entry ) {
			if ( $entry['id'] === $exclude_id ) {
				continue;
			}

			$text = self::replace_first_term_occurrence( $text, $entry, $glossary_url );
		}

		return $text;
	}

	/**
	 * Get linkable glossary entries (excludes entries with auto-linking disabled)
	 *
	 * @return array<int, array<string, mixed>> Array of glossary entries.
	 */
	private static function get_linkable_entries(): array {
		return pp_glossary_get_linkable_entries();
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
