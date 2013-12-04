<?php
/**
 * Global functions specified and used by Semantic MediaWiki. In general, it is
 * tried to fit functions in suitable classes as static methods if they clearly
 * belong to some particular sub-function of SMW. Most functions here are used
 * in diverse contexts so that they do not have fonud a place in any such class
 * yet.
 * @file
 * @ingroup SMW
 */

/**
 * @see NamespaceExaminer
 *
 * @return boolean
 * @deprecated since 1.9 and will be removed in 1.11
 */
function smwfIsSemanticsProcessed( $namespace ) {
	return \SMW\NamespaceExaminer::getInstance()->isSemanticEnabled( $namespace );
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
	global $wgCapitalLinks, $wgContLang;

	$text = trim( $text );

	if ( $wgCapitalLinks ) {
		$text = $wgContLang->ucfirst( $text );
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
 * @codeCoverageIgnore
 *
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

	$decseparator = wfMessage( 'smw_decseparator' )->text();

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
		$value = str_replace(
			array( 'x', 'y' ),
			array( $decseparator, wfMessage( 'smw_kiloseparator' )->inContentLanguage()->text() ),
			$value
		);

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
function smwfEncodeMessages( array $messages, $type = 'warning', $seperator = ' <!--br-->', $escape = true ) {
	if (  $messages !== array() ) {

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

		// Type will be converted internally
		$highlighter = SMW\Highlighter::factory( $type );
		$highlighter->setContent( array (
			'caption'   => null,
			'content'   => $errorList
		) );

		return $highlighter->getHtml();
	} else {
		return '';
	}
}

/**
 * Returns an instance for the storage back-end
 *
 * @return SMWStore
 */
function &smwfGetStore() {
	$store = \SMW\StoreFactory::getStore();
	return $store;
}

/**
 * @codeCoverageIgnore
 *
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

/**
 * Function to switch on Semantic MediaWiki. This function must be called in
 * LocalSettings.php after including SMW_Settings.php. It is used to ensure
 * that required parameters for SMW are really provided explicitly. For
 * readability, this is the only global function that does not adhere to the
 * naming conventions.
 *
 * This function also sets up all autoloading, such that all SMW classes are
 * available as early on. Moreover, jobs and special pages are registered.
 *
 * @deprecated since 1.9, just set $smwgNamespace after the inclusion of SemanticMediaWiki.php
 *
 * @param mixed $namespace
 * @param boolean $complete
 *
 * @return true
 *
 * @codeCoverageIgnore
 */
function enableSemantics( $namespace = null, $complete = false ) {
	global $smwgNamespace;

	if ( !$complete && ( $smwgNamespace !== '' ) ) {
		// The dot tells that the domain is not complete. It will be completed
		// in the Export since we do not want to create a title object here when
		// it is not needed in many cases.
		$smwgNamespace = '.' . $namespace;
	} else {
		$smwgNamespace = $namespace;
	}

	return true;
}

/**********************************************/
/***** namespace settings                 *****/
/**********************************************/

/**
 * Init the additional namespaces used by Semantic MediaWiki.
 *
 * @codeCoverageIgnore
 */
function smwfInitNamespaces() {
	global $smwgNamespaceIndex, $wgExtraNamespaces, $wgNamespaceAliases, $wgNamespacesWithSubpages, $wgLanguageCode, $smwgContLang;

	if ( !isset( $smwgNamespaceIndex ) ) {
		$smwgNamespaceIndex = 100;
	}
	// 100 and 101 used to be occupied by SMW's now obsolete namespaces "Relation" and "Relation_Talk"
	define( 'SMW_NS_PROPERTY',       $smwgNamespaceIndex + 2 );
	define( 'SMW_NS_PROPERTY_TALK',  $smwgNamespaceIndex + 3 );
	define( 'SMW_NS_TYPE',           $smwgNamespaceIndex + 4 );
	define( 'SMW_NS_TYPE_TALK',      $smwgNamespaceIndex + 5 );
	// 106 and 107 are occupied by the Semantic Forms, we define them here to offer some (easy but useful) support to SF
	define( 'SF_NS_FORM',            $smwgNamespaceIndex + 6 );
	define( 'SF_NS_FORM_TALK',       $smwgNamespaceIndex + 7 );
	define( 'SMW_NS_CONCEPT',        $smwgNamespaceIndex + 8 );
	define( 'SMW_NS_CONCEPT_TALK',   $smwgNamespaceIndex + 9 );

	smwfInitContentLanguage( $wgLanguageCode );

	// Register namespace identifiers
	if ( !is_array( $wgExtraNamespaces ) ) {
		$wgExtraNamespaces = array();
	}

	/**
	 * @var SMWLanguage $smwgContLang
	 */
	$wgExtraNamespaces = $wgExtraNamespaces + $smwgContLang->getNamespaces();
	$wgNamespaceAliases = $wgNamespaceAliases + $smwgContLang->getNamespaceAliases();

	// Support subpages only for talk pages by default
	$wgNamespacesWithSubpages = $wgNamespacesWithSubpages + array(
				SMW_NS_PROPERTY_TALK => true,
				SMW_NS_TYPE_TALK => true
	);

	// not modified for Semantic MediaWiki
	/* $wgNamespacesToBeSearchedDefault = array(
		NS_MAIN           => true,
		);
	*/

	###
	# Overwriting the following array, you can define for which namespaces
	# the semantic links and annotations are to be evaluated. On other
	# pages, annotations can be given but are silently ignored. This is
	# useful since, e.g., talk pages usually do not have attributes and
	# the like. In fact, is is not obvious what a meaningful attribute of
	# a talk page could be. Pages without annotations will also be ignored
	# during full RDF export, unless they are referred to from another
	# article.
	##
	$GLOBALS['smwgNamespacesWithSemanticLinks'] = array(
		NS_MAIN => true,
		NS_TALK => false,
		NS_USER => true,
		NS_USER_TALK => false,
		NS_PROJECT => true,
		NS_PROJECT_TALK => false,
		NS_IMAGE => true,
		NS_IMAGE_TALK => false,
		NS_MEDIAWIKI => false,
		NS_MEDIAWIKI_TALK => false,
		NS_TEMPLATE => false,
		NS_TEMPLATE_TALK => false,
		NS_HELP => true,
		NS_HELP_TALK => false,
		NS_CATEGORY => true,
		NS_CATEGORY_TALK => false,
		SMW_NS_PROPERTY  => true,
		SMW_NS_PROPERTY_TALK  => false,
		SMW_NS_TYPE => true,
		SMW_NS_TYPE_TALK => false,
		SMW_NS_CONCEPT => true,
		SMW_NS_CONCEPT_TALK => false,
	);
##
}

/**********************************************/
/***** language settings                  *****/
/**********************************************/

/**
 * Initialise a global language object for content language. This must happen
 * early on, even before user language is known, to determine labels for
 * additional namespaces. In contrast, messages can be initialised much later
 * when they are actually needed.
 *
 * @codeCoverageIgnore
 */
function smwfInitContentLanguage( $langcode ) {
	global $smwgIP, $smwgContLang;

	if ( !empty( $smwgContLang ) ) {
		return;
	}
	wfProfileIn( 'smwfInitContentLanguage (SMW)' );

	$smwContLangFile = 'SMW_Language' . str_replace( '-', '_', ucfirst( $langcode ) );
	$smwContLangClass = 'SMWLanguage' . str_replace( '-', '_', ucfirst( $langcode ) );

	if ( file_exists( $smwgIP . 'languages/' . $smwContLangFile . '.php' ) ) {
		include_once( $smwgIP . 'languages/' . $smwContLangFile . '.php' );
	}

	// Fallback if language not supported.
	if ( !class_exists( $smwContLangClass ) ) {
		include_once( $smwgIP . 'languages/SMW_LanguageEn.php' );
		$smwContLangClass = 'SMWLanguageEn';
	}

	$smwgContLang = new $smwContLangClass();

	wfProfileOut( 'smwfInitContentLanguage (SMW)' );
}
