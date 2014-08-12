<?php

namespace SMW;

use Html;
use MagicWord;
use SMW\Application;
use SMW\MediaWiki\MagicWordFinder;
use SMW\MediaWiki\RedirectTargetFinder;
use SMWOutputs;
use Title;

/**
 * Class collects all functions for wiki text parsing / processing that are
 * relevant for SMW
 *
 * This class is contains all functions necessary for parsing wiki text before
 * it is displayed or previewed while identifying SMW related annotations.
 *
 * @note Settings involve smwgNamespacesWithSemanticLinks, smwgLinksInValues,
 * smwgInlineErrors
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author Markus Krötzsch
 * @author Denny Vrandecic
 * @author mwjames
 */
class InTextAnnotationParser {

	/**
	 * @var ParserData
	 */
	protected $parserData;

	/**
	 * @var MagicWordFinder
	 */
	protected $magicWordFinder;

	/**
	 * @var RedirectTargetFinder
	 */
	protected $redirectTargetFinder;

	/**
	 * @var Settings
	 */
	protected $settings = null;

	/**
	 * @var boolean
	 */
	protected $isEnabledNamespace;

	/**
	 * Internal state for switching SMW link annotations off/on during parsing
	 * ([[SMW::on]] and [[SMW:off]])
	 * @var boolean
	 */
	protected $isAnnotation = true;

	/**
	 * @since 1.9
	 *
	 * @param ParserData $parserData
	 * @param MagicWordFinder $magicWordFinder
	 * @param RedirectTargetFinder $redirectTargetFinder
	 */
	public function __construct( ParserData $parserData, MagicWordFinder $magicWordFinder, RedirectTargetFinder $redirectTargetFinder ) {
		$this->parserData = $parserData;
		$this->magicWordFinder = $magicWordFinder;
		$this->redirectTargetFinder = $redirectTargetFinder;
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
		$this->settings = Application::getInstance()->getSettings();

		$this->magicWordFinder->setOutput( $this->parserData->getOutput() );
		$this->stripMagicWords( $text );

		$this->isEnabledNamespace = NamespaceExaminer::newFromArray( $this->settings->get( 'smwgNamespacesWithSemanticLinks' ) )->isSemanticEnabled( $title->getNamespace() );
		$this->findRedirectTarget( $text );

		$linksInValues = $this->settings->get( 'smwgLinksInValues' );
		$text = preg_replace_callback(
			$this->getRegexpPattern( $linksInValues ),
			$linksInValues ? 'self::process' : 'self::preprocess',
			$text
		);

		$this->parserData->getOutput()->addModules( $this->getModules()  );
		$this->parserData->updateOutput();

		SMWOutputs::commitToParserOutput( $this->parserData->getOutput() );
	}

	protected function findRedirectTarget( $text ) {

		if ( $this->isEnabledNamespace ) {

			$propertyAnnotator = Application::getInstance()->newPropertyAnnotatorFactory()->newRedirectPropertyAnnotator(
				$this->parserData->getSemanticData(),
				$this->redirectTargetFinder->findTarget( $text )
			);

			$propertyAnnotator->addAnnotation();
		}
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
			$dataValue = DataValueFactory::getInstance()->newPropertyValue(
				$property,
				$value,
				$valueCaption,
				$subject
			);

			if ( $this->isEnabledNamespace && $this->isAnnotation ) {
				$this->parserData->addDataValue( $dataValue );
			}
		}

		// Return the text representation
		$result = $dataValue->getShortWikitext( true );

		// If necessary add an error text
		if ( ( $this->settings->get( 'smwgInlineErrors' ) &&
			$this->isEnabledNamespace && $this->isAnnotation ) &&
			( !$dataValue->isValid() ) ) {
			$result .= $dataValue->getErrorText();
		}

		wfProfileOut( __METHOD__ );
		return $result;
	}

	protected function stripMagicWords( &$text ) {

		$words = array();

		foreach ( array( 'SMW_NOFACTBOX', 'SMW_SHOWFACTBOX' ) as $magicWord ) {
			$words = $words + $this->magicWordFinder->matchAndRemove( $magicWord, $text );
		}

		$this->magicWordFinder->setMagicWords( $words );

		return $words;
	}

}
