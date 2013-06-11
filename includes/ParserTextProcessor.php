<?php

namespace SMW;

use MagicWord;
use Title;
use Html;
use SpecialPage;

use SMWOutputs;
use SMWDIWikiPage;
use SMWDIProperty;

/**
 * Class collects all functions for wiki text parsing / processing that are
 * relevant for SMW
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 * @ingroup Parser
 *
 * @author Markus Krötzsch
 * @author Denny Vrandecic
 * @author mwjames
 */

/**
 * This class is contains all functions necessary for parsing wiki text before
 * it is displayed or previewed while identifying SMW related
 * annotations.
 *
 * @ingroup SMW
 * @ingroup Parser
 */
class ParserTextProcessor {

	/**
	 * Represents a Settings object
	 * @var Settings
	 */
	protected $settings;

	/**
	 * Represents a IParserData object
	 * @var IParserData
	 */
	protected $parserData;

	/**
	 * Represents $smwgNamespacesWithSemanticLinks status
	 * @var boolean
	 */
	protected $isEnabled;

	/**
	 * Internal state for switching SMW link annotations off/on during parsing
	 * ([[SMW::on]] and [[SMW:off]])
	 * @var boolean
	 */
	protected $isAnnotation = true;

	/**
	 * @par Example:
	 * @code
	 * $settings = Settings::newFromArray( array(
	 *  'smwgNamespacesWithSemanticLinks' => $GLOBALS['smwgNamespacesWithSemanticLinks'],
	 *  'smwgLinksInValues' => $GLOBALS['smwgLinksInValues'],
	 *  'smwgInlineErrors'  => $GLOBALS['smwgInlineErrors']
	 * ) );
	 *
	 * $parserData = new ParserData( $title, $parserOutput );
	 *
	 * new ParserTextProcessor( $parserData, $settings );
	 * @endcode
	 *
	 * @see SMWHooks::onInternalParseBeforeLinks
	 *
	 * @since 1.9
	 *
	 * @param IParserData $parserData
	 * @param Settings $settings
	 */
	public function __construct( IParserData $parserData, Settings $settings ) {
		$this->parserData = $parserData;
		$this->settings = $settings;
	}

	/**
	 * Parsing text before an article is displayed or previewed, strip out
	 * semantic properties and add them to the ParserOutput object
	 *
	 * @since 1.9
	 *
	 * @param string &$text
	 */
	public function parse( &$text ) {
		$title = $this->parserData->getTitle();

		// Strip magic words from text body
		$this->stripMagicWords( $text );

		// Attest if semantic data should be processed
		$this->isEnabled = NamespaceExaminer::newFromArray( $this->settings->get( 'smwgNamespacesWithSemanticLinks' ) )->isSemanticEnabled( $title->getNamespace() );

		// Process redirects
		$this->setRedirect( $title );

		// Parse links to extract semantic properties
		$linksInValues = $this->settings->get( 'smwgLinksInValues' );
		$text = preg_replace_callback(
			$this->getRegexpPattern( $linksInValues ),
			$linksInValues ? 'self::process' : 'self::preprocess',
			$text
		);

		// Update ParserOutput
		$this->parserData->getOutput()->addModules( $this->getModules()  );
		$this->parserData->updateOutput();
		SMWOutputs::commitToParserOutput( $this->parserData->getOutput() );
	}

	/**
	 * Returns required resource modules
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	protected function getModules() {
		return array(
			'ext.smw.style',
			'ext.smw.tooltips'
		);
	}

	/**
	 * Process and add '_REDI' property in case the current Title is a redirect
	 *
	 * @since 1.9
	 *
	 * @param Title $title
	 */
	protected function setRedirect( Title $title ) {
		if ( $this->isEnabled && $title->isRedirect() ) {
			$this->parserData->getData()->addPropertyObjectValue(
				new SMWDIProperty( '_REDI' ),
				SMWDIWikiPage::newFromTitle( $title, '__red' )
			);
		}
	}

	/**
	 * $smwgLinksInValues (default = false) determines which regexp pattern
	 * is returned, either a more complex (lib PCRE may cause segfaults if text
	 * is long) or a simpler (no segfaults found for those, but no links
	 * in values) pattern.
	 *
	 * If enabled (SMW accepts inputs like [[property::Some [[link]] in value]]),
	 * this may lead to PHP crashes (!) when very long texts are
	 * used as values. This is due to limitations in the library PCRE that
	 * PHP uses for pattern matching.
	 *
	 * @since 1.9
	 *
	 * @param boolean $linksInValues
	 *
	 * @return string
	 */
	protected function getRegexpPattern( $linksInValues ) {
		if ( $linksInValues ) {
			return '/\[\[             # Beginning of the link
				(?:([^:][^]]*):[=:])+ # Property name (or a list of those)
				(                     # After that:
				  (?:[^|\[\]]         #   either normal text (without |, [ or ])
				  |\[\[[^]]*\]\]      #   or a [[link]]
				  |\[[^]]*\]          #   or an [external link]
				)*)                   # all this zero or more times
				(?:\|([^]]*))?        # Display text (like "text" in [[link|text]]), optional
				\]\]                  # End of link
				/xu';
		} else {
			return '/\[\[             # Beginning of the link
				(?:([^:][^]]*):[=:])+ # Property name (or a list of those)
				([^\[\]]*)            # content: anything but [, |, ]
				\]\]                  # End of link
				/xu';
		}
	}

	/**
	 * A method that precedes the process() callback, it takes care of separating
	 * value and caption (instead of leaving this to a more complex regexp).
	 *
	 * @since 1.9
	 *
	 * @param array $semanticLink expects (linktext, properties, value|caption)
	 *
	 * @return string
	 */
	protected function preprocess( array $semanticLink ) {
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
			return $this->process( array( $semanticLink[0], $semanticLink[1], $value, $caption ) );
		} else {
			return $this->process( array( $semanticLink[0], $semanticLink[1], $value ) );
		}
	}

	/**
	 * This callback function strips out the semantic attributes from a wiki
	 * link.
	 *
	 * @since 1.9
	 *
	 * @param array $semanticLink expects (linktext, properties, value|caption)
	 *
	 * @return string
	 */
	protected function process( array $semanticLink ) {
		wfProfileIn( __METHOD__ );

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

		if ( $value === '' ) { // silently ignore empty values
			wfProfileOut( __METHOD__ );
			return '';
		}

		if ( $property == 'SMW' ) {
			switch ( $value ) {
				case 'on':
					$this->isAnnotation = true;
					break;
				case 'off':
					$this->isAnnotation = false;
					break;
			}
			wfProfileOut( __METHOD__ );
			return '';
		}

		if ( array_key_exists( 3, $semanticLink ) ) {
			$valueCaption = $semanticLink[3];
		} else {
			$valueCaption = false;
		}

		// Extract annotations and create tooltip.
		$properties = preg_split( '/:[=:]/u', $property );

		wfProfileOut( __METHOD__ );
		return $this->addPropertyValue( $properties, $value, $valueCaption );
	}

	/**
	 * Adds property values to the ParserOutput instance
	 *
	 * @since 1.9
	 *
	 * @param array $properties
	 *
	 * @return string
	 */
	protected function addPropertyValue( array $properties, $value, $valueCaption ) {
		wfProfileIn( __METHOD__ );

		$subject = $this->parserData->getData()->getSubject();

		// Add properties to the semantic container
		foreach ( $properties as $property ) {
			$dataValue = DataValueFactory::newPropertyValue(
				$property,
				$value,
				$valueCaption,
				$subject
			);

			if ( $this->isEnabled && $this->isAnnotation ) {
				$this->parserData->addPropertyValue( $dataValue );
			}
		}

		// Return the text representation
		$result = $dataValue->getShortWikitext( true );

		// If necessary add an error text
		if ( ( $this->settings->get( 'smwgInlineErrors' ) &&
			$this->isEnabled && $this->isAnnotation ) &&
			( !$dataValue->isValid() ) ) {
			$result .= $dataValue->getErrorText();
		}

		wfProfileOut( __METHOD__ );
		return $result;
	}

	/**
	 * Remove relevant SMW magic words from the given text and return
	 * an array of the names of all discovered magic words. Moreover,
	 * store this array in the current parser output, using the variable
	 * mSMWMagicWords and for MW 1.21+ 'smwmagicwords'
	 *
	 * @since 1.9
	 *
	 * @param &$text
	 *
	 * @return array
	 */
	protected function stripMagicWords( &$text ) {
		$words = array();
		$mw = MagicWord::get( 'SMW_NOFACTBOX' );

		if ( $mw->matchAndRemove( $text ) ) {
			$words[] = 'SMW_NOFACTBOX';
		}

		$mw = MagicWord::get( 'SMW_SHOWFACTBOX' );

		if ( $mw->matchAndRemove( $text ) ) {
			$words[] = 'SMW_SHOWFACTBOX';
		}

		// Store values into the mSMWMagicWords property
		if ( method_exists( $this->parserData->getOutput(), 'setExtensionData' ) ) {
			$this->parserData->getOutput()->setExtensionData( 'smwmagicwords', $words );
		} else {
			// @codeCoverageIgnoreStart
			$this->parserData->getOutput()->mSMWMagicWords = $words;
			// @codeCoverageIgnoreEnd
		}

		return $words;
	}
}
