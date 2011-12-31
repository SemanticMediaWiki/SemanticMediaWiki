<?php
/**
 * Global functions and constants for Semantic MediaWiki. In general, it is
 * tried to fit functions in suitable classes as static methods if they clearly
 * belong to some particular sub-function of SMW. Most functions here are used
 * in diverse contexts so that they do not have fonud a place in any such class
 * yet.
 * @file
 * @ingroup SMW
 */

// Constants for displaying the factbox.
define( 'SMW_FACTBOX_HIDDEN', 1 );
define( 'SMW_FACTBOX_SPECIAL', 2 );
define( 'SMW_FACTBOX_NONEMPTY',  3 );
define( 'SMW_FACTBOX_SHOWN',  5 );

// Constants for regulating equality reasoning.
define( 'SMW_EQ_NONE', 0 );
define( 'SMW_EQ_SOME', 1 );
define( 'SMW_EQ_FULL', 2 );

// Flags to classify available query descriptions, used to enable/disable certain features.
define( 'SMW_PROPERTY_QUERY', 1 );     // [[some property::...]]
define( 'SMW_CATEGORY_QUERY', 2 );     // [[Category:...]]
define( 'SMW_CONCEPT_QUERY', 4 );      // [[Concept:...]]
define( 'SMW_NAMESPACE_QUERY', 8 );    // [[User:+]] etc.
define( 'SMW_CONJUNCTION_QUERY', 16 ); // any conjunctions
define( 'SMW_DISJUNCTION_QUERY', 32 ); // any disjunctions (OR, ||)
define( 'SMW_ANY_QUERY', 0xFFFFFFFF );  // subsumes all other options

// Constants for defining which concepts to show only if cached.
define( 'CONCEPT_CACHE_ALL', 4 ); // show concept elements anywhere only if cached
define( 'CONCEPT_CACHE_HARD', 1 ); // show without cache if concept is not harder than permitted inline queries
define( 'CONCEPT_CACHE_NONE', 0 ); // show all concepts even without any cache

// Constants for identifying javascripts as used in SMWOutputs.
/// @deprecated Use module 'ext.smw.tooltips', see SMW_Ouptuts.php. Vanishes in SMW 1.7 at the latest.
define( 'SMW_HEADER_TOOLTIP', 2 );
/// @deprecated Module removed. Vanishes in SMW 1.7 at the latest.
define( 'SMW_HEADER_SORTTABLE', 3 );
/// @deprecated Use module 'ext.smw.style', see SMW_Ouptuts.php. Vanishes in SMW 1.7 at the latest.
define( 'SMW_HEADER_STYLE', 4 );

// Constants for denoting output modes in many functions: HTML or Wiki?
// "File" is for printing results into stand-alone files (e.g. building RSS)
// and should be treated like HTML when building single strings. Only query
// printers tend to have special handling for that.
define( 'SMW_OUTPUT_HTML', 1 );
define( 'SMW_OUTPUT_WIKI', 2 );
define( 'SMW_OUTPUT_FILE', 3 );

// Comparators for datavalues:
define( 'SMW_CMP_EQ', 1 ); // Matches only datavalues that are equal to the given value.
define( 'SMW_CMP_LEQ', 2 ); // Matches only datavalues that are less or equal than the given value.
define( 'SMW_CMP_GEQ', 3 ); // Matches only datavalues that are greater or equal to the given value.
define( 'SMW_CMP_NEQ', 4 ); // Matches only datavalues that are unequal to the given value.
define( 'SMW_CMP_LIKE', 5 ); // Matches only datavalues that are LIKE the given value.
define( 'SMW_CMP_NLKE', 6 ); // Matches only datavalues that are not LIKE the given value.
define( 'SMW_CMP_LESS', 7 ); // Matches only datavalues that are less than the given value.
define( 'SMW_CMP_GRTR', 8 ); // Matches only datavalues that are greater than the given value.

// Constants for date formats (using binary encoding of nine bits: 3 positions x 3 interpretations).
define( 'SMW_MDY', 785 );  // Month-Day-Year
define( 'SMW_DMY', 673 );  // Day-Month-Year
define( 'SMW_YMD', 610 );  // Year-Month-Day
define( 'SMW_YDM', 596 );  // Year-Day-Month
define( 'SMW_MY', 97 );    // Month-Year
define( 'SMW_YM', 76 );    // Year-Month
define( 'SMW_Y', 9 );      // Year
define( 'SMW_YEAR', 1 );   // an entered digit can be a year
define( 'SMW_DAY', 2 );   // an entered digit can be a year
define( 'SMW_MONTH', 4 );  // an entered digit can be a month
define( 'SMW_DAY_MONTH_YEAR', 7 ); // an entered digit can be a day, month or year
define( 'SMW_DAY_YEAR', 3 ); // an entered digit can be either a month or a year

/**
 * Return true if semantic data should be processed and displayed for a page
 * in the given namespace.
 * @return boolean
 */
function smwfIsSemanticsProcessed( $namespace ) {
	global $smwgNamespacesWithSemanticLinks;
	return !empty( $smwgNamespacesWithSemanticLinks[$namespace] );
}

/**
 * Takes a title text and turns it safely into its DBKey. This function
 * reimplements most of the title normalization as done in Title.php in order
 * to achieve conversion with less overhead. The official code could be called
 * here if more advanced normalization is needed.
 *
 * @param string $text
 */
function smwfNormalTitleDBKey( $text ) {
	global $wgCapitalLinks;

	$text = trim( $text );

	if ( $wgCapitalLinks ) {
		$text = ucfirst( $text );
	}

	return str_replace( ' ', '_', $text );
}

/**
 * Takes a text and turns it into a normalised version. This function
 * reimplements the title normalization as done in Title.php in order to
 * achieve conversion with less overhead. The official code could be called
 * here if more advanced normalization is needed.
 *
 * @param string $text
 */
function smwfNormalTitleText( $text ) {
	global $wgCapitalLinks;

	$text = trim( $text );

	if ( $wgCapitalLinks ) {
		$text = ucfirst( $text );
	}

	return str_replace( '_', ' ', $text );
}

/**
 * Escapes text in a way that allows it to be used as XML content (e.g. as a
 * string value for some property).
 *
 * @param string $text
 */
function smwfXMLContentEncode( $text ) {
	return str_replace( array( '&', '<', '>' ), array( '&amp;', '&lt;', '&gt;' ), Sanitizer::decodeCharReferences( $text ) );
}

/**
 * Decodes character references and inserts Unicode characters instead, using
 * the MediaWiki Sanitizer.
 *
 * @param string $text
 */
function smwfHTMLtoUTF8( $text ) {
	return Sanitizer::decodeCharReferences( $text );
}

/**
* This method formats a float number value according to the given language and
* precision settings, with some intelligence to produce readable output. Used
* to format a number that was not hand-formatted by a user.
*
* @param mixed $value input number
* @param integer $decplaces optional positive integer, controls how many digits after
* the decimal point are shown
*/
function smwfNumberFormat( $value, $decplaces = 3 ) {
	global $smwgMaxNonExpNumber;

	$decseparator = wfMsgForContent( 'smw_decseparator' );

	// If number is a trillion or more, then switch to scientific
	// notation. If number is less than 0.0000001 (i.e. twice decplaces),
	// then switch to scientific notation. Otherwise print number
	// using number_format. This may lead to 1.200, so then use trim to
	// remove trailing zeroes.
	$doScientific = false;

	// @todo: Don't do all this magic for integers, since the formatting does not fit there
	//       correctly. E.g. one would have integers formatted as 1234e6, not as 1.234e9, right?
	// The "$value!=0" is relevant: we want to scientify numbers that are close to 0, but never 0!
	if ( ( $decplaces > 0 ) && ( $value != 0 ) ) {
		$absValue = abs( $value );
		if ( $absValue >= $smwgMaxNonExpNumber ) {
			$doScientific = true;
		} elseif ( $absValue <= pow( 10, - $decplaces ) ) {
			$doScientific = true;
		} elseif ( $absValue < 1 ) {
			if ( $absValue <= pow( 10, - $decplaces ) ) {
				$doScientific = true;
			} else {
				// Increase decimal places for small numbers, e.g. .00123 should be 5 places.
				for ( $i = 0.1; $absValue <= $i; $i *= 0.1 ) {
					$decplaces++;
				}
			}
		}
	}

	if ( $doScientific ) {
		// Should we use decimal places here?
		$value = sprintf( "%1.6e", $value );
		// Make it more readable by removing trailing zeroes from n.n00e7.
		$value = preg_replace( '/(\\.\\d+?)0*e/u', '${1}e', $value, 1 );
		// NOTE: do not use the optional $count parameter with preg_replace. We need to
		//      remain compatible with PHP 4.something.
		if ( $decseparator !== '.' ) {
			$value = str_replace( '.', $decseparator, $value );
		}
	} else {
		// Format to some level of precision; number_format does rounding and locale formatting,
		// x and y are used temporarily since number_format supports only single characters for either
		$value = number_format( $value, $decplaces, 'x', 'y' );
		$value = str_replace( array( 'x', 'y' ), array( $decseparator, wfMsgForContent( 'smw_kiloseparator' ) ), $value );

		// Make it more readable by removing ending .000 from nnn.000
		//    Assumes substr is faster than a regular expression replacement.
		$end = $decseparator . str_repeat( '0', $decplaces );
		$lenEnd = strlen( $end );

		if ( substr( $value, - $lenEnd ) === $end ) {
			$value = substr( $value, 0, - $lenEnd );
		} else {
			// If above replacement occurred, no need to do the next one.
			// Make it more readable by removing trailing zeroes from nn.n00.
			$value = preg_replace( "/(\\$decseparator\\d+?)0*$/u", '$1', $value, 1 );
		}
	}

	return $value;
}

/**
 * Formats an array of message strings so that it appears as a tooltip.
 * $icon should be one of: 'warning' (default), 'info'.
 *
 * @param array $messages
 * @param string $icon Acts like an enum. Callers must ensure safety, since this value is used directly in the output.
 * @param string $seperator
 * @param boolean $escape Should the messages be escaped or not (ie when they already are)
 *
 * @return string
 */
function smwfEncodeMessages( array $messages, $icon = 'warning', $seperator = ' <!--br-->', $escape = true ) {
	if ( count( $messages ) > 0 ) {
		SMWOutputs::requireResource( 'ext.smw.tooltips' );

		if ( $escape ) {
			$messages = array_map( 'htmlspecialchars', $messages );
		}

		if ( count( $messages ) == 1 )  {
			$errorList = $messages[0];
		}
		else {
			foreach ( $messages as &$message ) {
				$message = '<li>' . $message . '</li>';
			}
			
			$errorList = '<ul>' . implode( $seperator, $messages ) . '</ul>';
		}

		return '<span class="smwttpersist">' .
				'<span class="smwtticon">' . htmlspecialchars( $icon ) . '.png</span>' .
				'<span class="smwttcontent">' . $errorList . '</span>' . 
			'</span>';
	} else {
		return '';
	}
}

/**
 * @deprecated since 1.7, will be removed in 1.9.
 */
function smwfLoadExtensionMessages( $extensionName ) {}

/**
 * Get a handle for the storage backend that is used to manage the data.
 * Currently, it just returns one globally defined object, but the
 * infrastructure allows to set up load balancing and task-dependent use of
 * stores (e.g. using other stores for fast querying than for storing new
 * facts), somewhat similar to MediaWiki's DB implementation.
 *
 * @return SMWStore
 */
function &smwfGetStore() {
	global $smwgMasterStore, $smwgDefaultStore;

	if ( is_null( $smwgMasterStore ) ) {
		$smwgMasterStore = new $smwgDefaultStore();
	}

	return $smwgMasterStore;
}

/**
 * Get the SMWSparqlDatabase object to use for connecting to a SPARQL store,
 * or null if no SPARQL backend has been set up.
 *
 * Currently, it just returns one globally defined object, but the
 * infrastructure allows to set up load balancing and task-dependent use of
 * stores (e.g. using other stores for fast querying than for storing new
 * facts), somewhat similar to MediaWiki's DB implementation.
 *
 * @since 1.6
 *
 * @return SMWSparqlDatabase or null
 */
function &smwfGetSparqlDatabase() {
	global $smwgSparqlDatabase, $smwgSparqlDefaultGraph, $smwgSparqlQueryEndpoint,
		$smwgSparqlUpdateEndpoint, $smwgSparqlDataEndpoint, $smwgSparqlDatabaseMaster;
	if ( !isset( $smwgSparqlDatabaseMaster ) ) {
		$smwgSparqlDatabaseMaster = new $smwgSparqlDatabase( $smwgSparqlDefaultGraph,
			$smwgSparqlQueryEndpoint, $smwgSparqlUpdateEndpoint, $smwgSparqlDataEndpoint );
	}
	return $smwgSparqlDatabaseMaster;
}

/**
 * Compatibility helper for using Linker methods.
 * MW 1.16 has a Linker with non-static methods,
 * where in MW 1.19 they are static, and a DummyLinker
 * class is introduced, which can be instantaited for
 * compat reasons. 
 *
 * @since 1.6
 *
 * @return Linker or DummyLinker
 */
function smwfGetLinker() {
	static $linker = false;

	if ( $linker === false ) {
		$linker = class_exists( 'DummyLinker' ) ? new DummyLinker() : new Linker();
	}

	return $linker;
}
