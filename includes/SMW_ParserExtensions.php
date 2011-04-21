<?php
/**
 * Static class to collect all functions related to parsing wiki text in SMW.
 * It includes all parser function declarations and hooks.
 * 
 * @file SMW_ParserExtensions.php
 * @ingroup SMW
 * 
 * @author Markus Krötzsch
 * @author Denny Vrandecic
 */
class SMWParserExtensions {

	/// Temporarily store parser as it cannot be passed to call-back functions otherwise.
	protected static $mTempParser;
	/// Internal state for switching off/on SMW link annotations during parsing
	protected static $mTempStoreAnnotations;

	/**
	 * This method will be called before an article is displayed or previewed.
	 * For display and preview we strip out the semantic properties and append them
	 * at the end of the article.
	 *
	 * @param Parser $parser
	 * @param string $text
	 */
	static public function onInternalParseBeforeLinks( &$parser, &$text ) {
		global $smwgStoreAnnotations, $smwgLinksInValues;

		SMWParseData::stripMagicWords( $text, $parser );

		// Store the results if enabled (we have to parse them in any case,
		// in order to clean the wiki source for further processing).
		$smwgStoreAnnotations = smwfIsSemanticsProcessed( $parser->getTitle()->getNamespace() );
		SMWParserExtensions::$mTempStoreAnnotations = true; // used for [[SMW::on]] and [[SMW:off]]

		// Process redirects, if any (it seems that there is indeed no more direct way of getting this info from MW)
		if ( $smwgStoreAnnotations ) {
			$rt = Title::newFromRedirect( $text );
			if ( $rt !== null ) {
				$p = new SMWDIProperty( '_REDI' );
				$di = SMWDIWikiPage::newFromTitle( $rt, '__red' );
				SMWParseData::getSMWData( $parser )->addPropertyObjectValue( $p, $di );
			}
		}

		// only used in subsequent callbacks, forgotten afterwards
		SMWParserExtensions::$mTempParser = $parser;

		// In the regexp matches below, leading ':' escapes the markup, as known for Categories.
		// Parse links to extract semantic properties.
		if ( $smwgLinksInValues ) { // More complex regexp -- lib PCRE may cause segfaults if text is long :-(
			$semanticLinkPattern = '/\[\[                 # Beginning of the link
			                        (?:([^:][^]]*):[=:])+ # Property name (or a list of those)
			                        (                     # After that:
			                          (?:[^|\[\]]         #   either normal text (without |, [ or ])
			                          |\[\[[^]]*\]\]      #   or a [[link]]
			                          |\[[^]]*\]          #   or an [external link]
			                        )*)                   # all this zero or more times
			                        (?:\|([^]]*))?        # Display text (like "text" in [[link|text]]), optional
			                        \]\]                  # End of link
			                        /xu';
			$text = preg_replace_callback( $semanticLinkPattern, array( 'SMWParserExtensions', 'parsePropertiesCallback' ), $text );
		} else { // Simpler regexps -- no segfaults found for those, but no links in values.
			$semanticLinkPattern = '/\[\[                 # Beginning of the link
			                        (?:([^:][^]]*):[=:])+ # Property name (or a list of those)
			                        ([^\[\]]*)            # content: anything but [, |, ]
			                        \]\]                  # End of link
			                        /xu';
			$text = preg_replace_callback( $semanticLinkPattern, array( 'SMWParserExtensions', 'simpleParsePropertiesCallback' ), $text );
		}

		// Add link to RDF to HTML header.
		// TODO: do escaping via Html or Xml class.
		SMWOutputs::requireHeadItem(
			'smw_rdf', '<link rel="alternate" type="application/rdf+xml" title="' .
			htmlspecialchars( $parser->getTitle()->getPrefixedText() ) . '" href="' .
			htmlspecialchars(
				SpecialPage::getTitleFor( 'ExportRDF', $parser->getTitle()->getPrefixedText() )->getLocalUrl( 'xmlmime=rdf' )
			) . "\" />"
		);

		SMWOutputs::commitToParser( $parser );
		return true; // always return true, in order not to stop MW's hook processing!
	}

	/**
	 * This callback function strips out the semantic attributes from a wiki
	 * link. Expected parameter: array(linktext, properties, value|caption)
	 * This function is a preprocessing for smwfParsePropertiesCallback, and
	 * takes care of separating value and caption (instead of leaving this to
	 * a more complex regexp).
	 */
	static public function simpleParsePropertiesCallback( $semanticLink ) {
		$value = '';
		$caption = false;

		if ( array_key_exists( 2, $semanticLink ) ) {
			$parts = explode( '|', $semanticLink[2] );
			if ( array_key_exists( 0, $parts ) ) {
				$value = $parts[0];
			}
			if ( array_key_exists( 1, $parts ) ) {
				$caption = $parts[1];
			}
		}

		if ( $caption !== false ) {
			return SMWParserExtensions::parsePropertiesCallback( array( $semanticLink[0], $semanticLink[1], $value, $caption ) );
		} else {
			return SMWParserExtensions::parsePropertiesCallback( array( $semanticLink[0], $semanticLink[1], $value ) );
		}
	}

	/**
	 * This callback function strips out the semantic attributes from a wiki
	 * link. Expected parameter: array(linktext, properties, value, caption)
	 */
	static public function parsePropertiesCallback( $semanticLink ) {
		global $smwgInlineErrors, $smwgStoreAnnotations;

		wfProfileIn( 'smwfParsePropertiesCallback (SMW)' );

		if ( array_key_exists( 1, $semanticLink ) ) {
			$property = $semanticLink[1];
		} else {
			$property = '';
		}

		if ( array_key_exists( 2, $semanticLink ) ) {
			$value = $semanticLink[2];
		} else {
			$value = '';
		}

		if ( $value == '' ) { // silently ignore empty values
			wfProfileOut( 'smwfParsePropertiesCallback (SMW)' );
			return '';
		}

		if ( $property == 'SMW' ) {
			switch ( $value ) {
				case 'on':
					SMWParserExtensions::$mTempStoreAnnotations = true;
					break;
				case 'off':
					SMWParserExtensions::$mTempStoreAnnotations = false;
					break;
			}
			wfProfileOut( 'smwfParsePropertiesCallback (SMW)' );
			return '';
		}

		if ( array_key_exists( 3, $semanticLink ) ) {
			$valueCaption = $semanticLink[3];
		} else {
			$valueCaption = false;
		}

		// Extract annotations and create tooltip.
		$properties = preg_split( '/:[=:]/u', $property );

		foreach ( $properties as $singleprop ) {
			$dv = SMWParseData::addProperty( $singleprop, $value, $valueCaption, SMWParserExtensions::$mTempParser, $smwgStoreAnnotations && SMWParserExtensions::$mTempStoreAnnotations );
		}

		$result = $dv->getShortWikitext( true );

		if ( ( $smwgInlineErrors && $smwgStoreAnnotations && SMWParserExtensions::$mTempStoreAnnotations ) && ( !$dv->isValid() ) ) {
			$result .= $dv->getErrorText();
		}

		wfProfileOut( 'smwfParsePropertiesCallback (SMW)' );

		return $result;
	}

}
