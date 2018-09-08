<?php

namespace SMW\Query\Parser;

/**
 * The term parser uses a simplified string to build an #ask conform query
 * string, for example:
 * - `in:foo bar || (phrase:bar && not:foo)` becomes `[[in:
 * foo bar]] || <q>[[phrase:bar]] && [[not:foo]]</q>`
 * - `in:(foo && bar)`becomes [[in:foo]] && [[in:bar]]
 *
 * A custom prefix map allows to create assignments between a custom prefix and
 * a property set and hereby simplifies the search input process.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TermParser {

	/**
	 * @var []
	 */
	private $standard_prefix = [ 'in:', 'phrase:', 'not:', 'has:', 'category:' ];

	/**
	 * @var []
	 */
	private static $cache = [];

	/**
	 * The `prefix_map` is expected to contain assignments of prefixes that link
	 * to a collection of properties. The prefix is used as short-cut to cover a
	 * range of disjunctive query declarations to simplify the creation of a
	 * query construct such as:
	 *
	 * - Prefix map: `'keyword' => [ 'Has keyword', 'Keyword' ]`
	 * - Input: `keyword:foo bar`
	 * - Output: `([[Has keyword::foo bar]] || [[Keyword::foo bar]])`
	 *
	 * @var []
	 */
	private $prefix_map = [];

	/**
	 * @since 3.0
	 *
	 * @param array $prefix_map
	 */
	public function __construct( array $prefix_map = [] ) {
		$this->prefix_map = $prefix_map;
	}

	/**
	 * @param string $term
	 *
	 * @return string
	 */
	public function parse( $term ) {

		$hash = md5( $term );

		if ( isset( self::$cache[$hash] ) ) {
			return self::$cache[$hash];
		}

		$pattern = '';
		$custom_prefix = [];

		foreach ( array_keys( $this->prefix_map ) as $p ) {

			// Just in case, `in:`, `phrase:`, `has:`, and `not:` are not
			// permitted to be overridden by a prefix assignment, `category:`
			// can.
			if ( in_array( $p, [ 'in', 'phrase', 'not', 'has' ] ) ) {
				continue;
			}

			$pattern .= '|(' . $p . ':)';
			$custom_prefix[] = "$p:";
		}

		// in:(A&&b)-> in:A && in:b
		$this->normalize_compact_form( 'in', $pattern, $term );

		// has:(A&&b) -> has:A && has:b
		$this->normalize_compact_form( 'has', $pattern, $term );

		// Simplify the processing by normalizing expressions
		$term = str_replace( [ '<q>', '</q>' ],  [ '(', ')' ], $term );

		$terms = preg_split(
			"/(in:)|(phrase:)|(not:)|(has:)|(category:)$pattern|(&&)|(AND)|(OR)|(\|\|)|(\()|(\)|(\[\[))/",
			$term,
			-1,
			PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
		);

		$affix = array_merge(
			[ '&&', 'AND', '||', 'OR', '(', ')', '[[' ],
			$this->standard_prefix,
			$custom_prefix
		);

		$term = '';
		$custom = '';
		$prefix = '';
		$k = 0;

		while ( key( $terms ) !== null ) {

			$t_term = current( $terms );
			$new = trim( $t_term );

			$continue = true;
			$space = $t_term{0} == ' ' ? ' ' : '';

			// Look ahead
			$next = next( $terms );
			$last = substr( $term, -2 );

			if ( $new === '' ) {
				continue;
			}

			if ( $new === '[[' && $next === '[[' ) {
				continue;
			}

			if ( in_array( $new, $custom_prefix ) ) {
				$custom = "[[$new";
				$prefix = $new;
			} elseif( in_array( $new, $this->standard_prefix ) ) {
				$term .= "[[$new";
			} elseif ( $custom !== '' ) {
				$custom .= $new;
				$last = substr( $new, -2 );
			} else {
				$term .= "{$space}{$new}";
			}

			// has:Property Foo -> [[Property Foo::+]]
			if ( $new === 'has:' ) {
				$next = trim( $next );
				$last = ']]';
				// Already using the next element to set the property,
				// skip in case other terms are to be found
				$continue = !next( $terms );
				$term = str_replace( 'has:', "$next::+$last", $term );
			}

			if ( $continue && $last === ']]' || $new === '(' || $new === '||' ) {
				continue;
			}

			// Check next element, close expression in case of a matching
			// affix
			if ( $k > 0 && in_array( $next, $affix ) ) {
				$term .= $this->close( $custom, $prefix );
			}

			// Last element
			if ( $next === false && !in_array( $last, [ '&&', 'AND', '||', 'OR', ']]' ] ) ) {
				if ( $custom === '' && mb_substr_count( $term, '[[' ) > mb_substr_count( $term, ']]' ) ) {
					$term .= $this->close( $custom, $prefix );
				} elseif ( $custom !== '' ) {
					$term .= $this->close( $custom, $prefix );
				}
			}

			$k++;
		}

		return self::$cache[$hash] = $this->normalize( $term );
	}

	private function close( &$custom, $prefix ) {

		// Standard closing
		if ( $custom === '' ) {
			return "]]";
		}

		$term = "$custom]]";
		$custom = '';
		$terms =  [];
		$p_map = str_replace( ':', '', $prefix );

		if ( !isset( $this->prefix_map[$p_map] ) ) {
			return $term;
		}

		// A custom prefix adds additional disjunctive conditions to broaden the
		// search radius for all its assigned properties.
		foreach ( $this->prefix_map[$p_map] as $val ) {
			$terms[] = str_replace( $prefix, $val . '::', $term );
		}

		// `keyword:foo bar` -> ([[Has keyword::foo bar]] || [[Keyword::foo bar]])
		return '(' . implode( '||', $terms ) . ')';
	}

	private function normalize( $term ) {
		return str_replace(
			[ ')[[', ']](', '(', ')', '||', '&&', 'AND', 'OR', ']][[', '[[[[', ']]]]', '  ' ],
			[ ') [[', ']] (', '<q>', '</q>', ' || ', ' && ', ' AND ', ' OR ', ']] [[', '[[', ']]', ' ' ],
			$term
		);
	}

	private function normalize_compact_form( $exp, $pattern, &$term ) {

		if ( strpos( $term, "$exp:(" ) === false ) {
			return;
		}

		preg_match_all("/$exp:\((.*?)\)/", $term, $matches );

		foreach ( $matches[0] as $match ) {
			$orig = $match;
			$match = str_replace( "$exp:(", '', $match );

			if ( substr( $match, -1 ) === ')' ) {
				$match = substr( $match, 0, -1 );
			}

			$terms = preg_split(
				"/(in:)|(phrase:)|(not:)|(has:)|(category:)$pattern|(&&)|(AND)|(OR)|(\|\|)|(\()|(\)|(\[\[))/",
				$match,
				-1,
				PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
			);

			$replace = '';

			foreach ( $terms as $t ) {
				$t = trim( $t );

				if ( in_array( $t, [ '&&', 'AND', '||', 'OR' ] ) ) {
					$replace .= " $t ";
				} elseif ( $t === ')' ) {
					$replace .= "$t";
				} else {
					$replace .= "$exp:$t";
				}
			}

			$term = str_replace( $orig, $replace, $term );
		}
	}

}
