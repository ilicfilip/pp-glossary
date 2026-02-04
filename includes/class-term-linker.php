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

		$entries = pp_glossary_get_linkable_entries();
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
	 * Replace first occurrence of any term in entry
	 *
	 * Splits text by excluded tags (e.g. <a>, headings) to avoid creating
	 * nested anchors or linking inside elements that should be skipped.
	 *
	 * @param string               $text         The text to process.
	 * @param array<string, mixed> $entry        The glossary entry data.
	 * @param string               $glossary_url The glossary page URL.
	 * @return string Modified text.
	 */
	private static function replace_first_term_occurrence( string $text, array $entry, string $glossary_url ): string {
		$excluded_tags = Settings::get_excluded_tags();

		/** This filter is documented in includes/class-content-filter.php */
		$excluded_tags = apply_filters( 'pp_glossary_excluded_tags', $excluded_tags );

		// Build the pattern for excluded tags.
		$excluded_pattern = '';
		foreach ( $excluded_tags as $tag ) {
			$excluded_pattern .= '<' . $tag . '\b[^>]*>.*?<\/' . $tag . '>|';
		}
		$excluded_pattern = rtrim( $excluded_pattern, '|' );

		// Split text by excluded tags.
		$parts = preg_split( '/(' . $excluded_pattern . ')/is', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

		if ( false === $parts ) {
			return $text;
		}

		// Try each term until we find a match in a non-excluded part.
		foreach ( $entry['terms'] as $term ) {
			$replaced  = false;
			$new_parts = [];
			$flags     = $entry['case_sensitive'] ? 'u' : 'iu';
			$pattern   = '/\b(' . preg_quote( $term, '/' ) . ')\b(?![^<]*>)/' . $flags;

			foreach ( $parts as $part ) {
				// If already replaced or this is an excluded tag, keep as-is.
				if ( $replaced || preg_match( '/^<(?:' . implode( '|', $excluded_tags ) . ')\b/i', $part ) ) {
					$new_parts[] = $part;
					continue;
				}

				if ( preg_match( $pattern, $part, $matches, PREG_OFFSET_CAPTURE ) ) {
					$matched_term = $matches[1][0];
					$offset       = $matches[1][1];

					$link_html = sprintf(
						'<a href="%s#%s" class="pp-glossary-link">%s</a>',
						esc_url( $glossary_url ),
						esc_attr( $entry['slug'] ),
						esc_html( $matched_term )
					);

					$new_parts[] = substr_replace( $part, $link_html, $offset, strlen( $matched_term ) );
					$replaced    = true;
				} else {
					$new_parts[] = $part;
				}
			}

			if ( $replaced ) {
				$parts = $new_parts;
				break;
			}
		}

		return implode( '', $parts );
	}
}
