<?php

namespace SMW\Query\Parser;

/**
 * The term parser uses a simplified string to build an #ask conform query
 * string, for example `in:foo bar || (phrase:bar && not:foo)` becomes `[[in:
 * foo bar]] || <q>[[phrase:bar]] && [[not:foo]]</q>`.
 *
 * A prefix map can contain assignments to define a query construct, hereby
 * allowing to use a custom prefix to simplify the input process.
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
	private $standard_prefix = [ 'in:', 'phrase:', 'not:', 'category:' ];

	/**
	 * The `prefix_map` is expected to contain assignments of prefixes that link
	 * to a collection of properties. The prefix is used as short-cut to cover a
	 * range of disjunctive query declarations to simplify the creation of a
	 * query construct such as:
	 *
	 * - Map: `'keyword:' => [ 'Has keyword', 'Keyword' ]`
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

		// Just in case, `in:`, `phrase:`, and `not:` are not permitted to be
		// overridden by a prefix assignment, `category:` can.
		unset( $this->prefix_map['in:'] );
		unset( $this->prefix_map['phrase:'] );
		unset( $this->prefix_map['not:'] );
	}

	/**
	 * @param string $term
	 *
	 * @return string
	 */
	public function parse( $term ) {

		$pattern = '';
		$custom_prefix = [];

		foreach ( array_keys( $this->prefix_map ) as $p ) {
			$pattern .= '|(' . $p . ':)';
			$custom_prefix[] = "$p:";
		}

		// Simplify the processing by normalizing expressions
		$term = str_replace([ '<q>', '</q>' ],  [ '(', ')' ], $term );

		$terms = preg_split(
			"/(in:)|(phrase:)|(not:)|(category:)$pattern|(&&)|(AND)|(OR)|(\|\|)|(\()|(\)|(\[\[))/",
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
			$new = trim( current( $terms ) );
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
				$term .= $new;
			}

			if ( $last === ']]' || $new === '(' || $new === '||' ) {
				continue;
			}

			// Check next element, close expression in case of a matching
			// affix
			if ( $k > 0 && in_array( $next, $affix ) ) {
				$term .= $this->close( $custom, $prefix );
			}

			// Last element
			if ( $next === false && !in_array( $last, [ '&&', 'AND', '||', 'OR', ']]' ] ) ) {
				$term .= $this->close( $custom, $prefix );
			}

			$k++;
		}

		return $this->normalize( $term );
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
			[ ')[[', ']](', '(', ')', '||', '&&', 'AND', 'OR', ']][[', '[[[[', ']]]]' ],
			[ ') [[', ']] (', '<q>', '</q>', ' || ', ' && ', ' AND ', ' OR ', ']] [[', '[[', ']]' ],
			$term
		);
	}

}
