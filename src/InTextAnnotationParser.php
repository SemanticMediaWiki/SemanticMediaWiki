<?php

namespace SMW;

use Hooks;
use SMW\MediaWiki\MagicWordsFinder;
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
	private $parserData;

	/**
	 * @var MagicWordsFinder
	 */
	private $magicWordsFinder;

	/**
	 * @var RedirectTargetFinder
	 */
	private $redirectTargetFinder;

	/**
	 * @var DataValueFactory
	 */
	private $dataValueFactory = null;

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory = null;

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
	 * @var boolean
	 */
	private $strictModeState = true;

	/**
	 * @since 1.9
	 *
	 * @param ParserData $parserData
	 * @param MagicWordsFinder $magicWordsFinder
	 * @param RedirectTargetFinder $redirectTargetFinder
	 */
	public function __construct( ParserData $parserData, MagicWordsFinder $magicWordsFinder, RedirectTargetFinder $redirectTargetFinder ) {
		$this->parserData = $parserData;
		$this->magicWordsFinder = $magicWordsFinder;
		$this->redirectTargetFinder = $redirectTargetFinder;
		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	/**
	 * Whether a strict interpretation (e.g [[property::value:partOfTheValue::alsoPartOfTheValue]])
	 * or a more loose interpretation (e.g. [[property1::property2::value]]) for
	 * annotations is to be applied.
	 *
	 * @since 2.3
	 *
	 * @param boolean $strictModeState
	 */
	public function setStrictModeState( $strictModeState ) {
		$this->strictModeState = (bool)$strictModeState;
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
		$this->settings = $this->applicationFactory->getSettings();
		$start = microtime( true );

		// Identifies the current parser run (especially when called recursively)
		$this->parserData->getSubject()->setContextReference( 'intp:' . uniqid() );

		$this->doStripMagicWordsFromText( $text );

		$this->setSemanticEnabledNamespaceState( $title );
		$this->addRedirectTargetAnnotation( $text );

		$linksInValues = $this->settings->get( 'smwgLinksInValues' );

		$text = preg_replace_callback(
			$this->getRegexpPattern( $linksInValues ),
			$linksInValues ? 'self::process' : 'self::preprocess',
			$text
		);

		if ( $this->isEnabledNamespace ) {
			$this->parserData->getOutput()->addModules( $this->getModules() );

			if ( method_exists( $this->parserData->getOutput(), 'recordOption' ) ) {
				$this->parserData->getOutput()->recordOption( 'userlang' );
			}
		}

		$this->parserData->pushSemanticDataToParserOutput();

		$this->parserData->addLimitReport(
			'intext-parsertime',
			number_format( ( microtime( true ) - $start ), 3 )
		);

		SMWOutputs::commitToParserOutput( $this->parserData->getOutput() );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $text
	 *
	 * @return text
	 */
	public static function decodeSquareBracket( $text ) {
		return str_replace( array( '%5B', '%5D' ), array( '[', ']' ), $text );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $text
	 *
	 * @return text
	 */
	public static function obscureAnnotation( $text ) {
		return preg_replace_callback(
			self::getRegexpPattern( false ),
			function( array $matches ) {
				return str_replace( '[', '&#x005B;', $matches[0] );
			},
			self::decodeSquareBracket( $text )
		);
	}

	/**
	 * @since 2.4
	 *
	 * @param string $text
	 *
	 * @return text
	 */
	public static function removeAnnotation( $text ) {
		return preg_replace_callback(
			self::getRegexpPattern( false ),
			function( array $matches ) {
				$caption = false;
				$value = '';

				// #1453
				if ( $matches[0] === '[[SMW::off]]' || $matches[0] === '[[SMW::on]]' ) {
					return false;
				}

				// Strict mode matching
				if ( array_key_exists( 1, $matches ) ) {
					if ( strpos( $matches[1], ':' ) !== false && isset( $matches[2] ) ) {
						list( $matches[1], $matches[2] ) = explode( '::', $matches[1] . '::' . $matches[2], 2 );
					}
				}

				if ( array_key_exists( 2, $matches ) ) {
					$parts = explode( '|', $matches[2] );
					$value = array_key_exists( 0, $parts ) ? $parts[0] : '';
					$caption = array_key_exists( 1, $parts ) ? $parts[1] : false;
				}

				return $caption !== false ? $caption : $value;
			},
			self::decodeSquareBracket( $text )
		);
	}

	/**
	 * @since 2.1
	 *
	 * @param Title|null $redirectTarget
	 */
	public function setRedirectTarget( Title $redirectTarget = null ) {
		$this->redirectTargetFinder->setRedirectTarget( $redirectTarget );
	}

	protected function addRedirectTargetAnnotation( $text ) {

		if ( $this->isEnabledNamespace ) {

			$this->redirectTargetFinder->findRedirectTargetFromText( $text );

			$redirectPropertyAnnotator = $this->applicationFactory->newPropertyAnnotatorFactory()->newRedirectPropertyAnnotator(
				$this->parserData->getSemanticData(),
				$this->redirectTargetFinder
			);

			$redirectPropertyAnnotator->addAnnotation();
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
	protected static function getRegexpPattern( $linksInValues ) {
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
		}

		return $this->process( array( $semanticLink[0], $semanticLink[1], $value ) );
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

		$valueCaption = false;
		$property = '';
		$value = '';

		if ( array_key_exists( 1, $semanticLink ) ) {

			// #1252 Strict mode being disabled for support of multi property
			// assignments (e.g. [[property1::property2::value]])

			// #1066 Strict mode is to check for colon(s) produced by something
			// like [[Foo::Bar::Foobar]], [[Foo:::0049 30 12345678]]
			// In case a colon appears (in what is expected to be a string without a colon)
			// then concatenate the string again and split for the first :: occurrence
			// only
			if ( $this->strictModeState && strpos( $semanticLink[1], ':' ) !== false && isset( $semanticLink[2] ) ) {
				list( $semanticLink[1], $semanticLink[2] ) = explode( '::', $semanticLink[1] . '::' . $semanticLink[2], 2 );
			}

			$property = $semanticLink[1];
		}

		if ( array_key_exists( 2, $semanticLink ) ) {
			$value = $semanticLink[2];
		}

		if ( $value === '' ) { // silently ignore empty values
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
			return '';
		}

		if ( array_key_exists( 3, $semanticLink ) ) {
			$valueCaption = $semanticLink[3];
		}

		// Extract annotations and create tooltip.
		$properties = preg_split( '/:[=:]/u', $property );

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

		$subject = $this->parserData->getSubject();

		// Add properties to the semantic container
		foreach ( $properties as $property ) {
			$dataValue = $this->dataValueFactory->newDataValueByText(
				$property,
				$value,
				$valueCaption,
				$subject
			);

			if (
				$this->isEnabledNamespace &&
				$this->isAnnotation &&
				$this->parserData->canModifySemanticData() ) {
				$this->parserData->addDataValue( $dataValue );
			}
		}

		// Return the text representation
		$result = $dataValue->getShortWikitext( true );

		// If necessary add an error text
		if ( ( $this->settings->get( 'smwgInlineErrors' ) &&
			$this->isEnabledNamespace && $this->isAnnotation ) &&
			( !$dataValue->isValid() ) ) {
			// Encode `:` to avoid a comment block and instead of the nowiki tag
			// use &#58; as placeholder
			$result = str_replace( ':', '&#58;', $result ) . $dataValue->getErrorText();
		}

		return $result;
	}

	protected function doStripMagicWordsFromText( &$text ) {

		$words = array();

		$this->magicWordsFinder->setOutput( $this->parserData->getOutput() );

		$magicWords = array(
			'SMW_NOFACTBOX',
			'SMW_SHOWFACTBOX'
		);

		Hooks::run( 'SMW::Parser::BeforeMagicWordsFinder', array( &$magicWords ) );

		foreach ( $magicWords as $magicWord ) {
			$words[] = $this->magicWordsFinder->findMagicWordInText( $magicWord, $text );
		}

		$this->magicWordsFinder->pushMagicWordsToParserOutput( $words );

		return $words;
	}

	private function setSemanticEnabledNamespaceState( Title $title ) {
		$this->isEnabledNamespace = $this->applicationFactory->getNamespaceExaminer()->isSemanticEnabled( $title->getNamespace() );
	}

}
