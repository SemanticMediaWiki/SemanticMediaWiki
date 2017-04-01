<?php

namespace SMW;

use Hooks;
use SMW\Parser\Obfuscator;
use SMW\Parser\LinksProcessor;
use SMW\MediaWiki\MagicWordsFinder;
use SMW\MediaWiki\RedirectTargetFinder;
use SMW\Utils\Timer;
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
	 * Internal state for switching SMW link annotations off/on during parsing
	 * ([[SMW::on]] and [[SMW:off]])
	 */
	const OFF = '[[SMW::off]]';
	const ON = '[[SMW::on]]';

	/**
	 * @var ParserData
	 */
	private $parserData;

	/**
	 * @var LinksProcessor
	 */
	private $linksProcessor;

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
	 * @var boolean|integer
	 */
	private $enabledLinksInValues = false;

	/**
	 * @since 1.9
	 *
	 * @param ParserData $parserData
	 * @param LinksProcessor $linksProcessor
	 * @param MagicWordsFinder $magicWordsFinder
	 * @param RedirectTargetFinder $redirectTargetFinder
	 */
	public function __construct( ParserData $parserData, LinksProcessor $linksProcessor, MagicWordsFinder $magicWordsFinder, RedirectTargetFinder $redirectTargetFinder ) {
		$this->parserData = $parserData;
		$this->linksProcessor = $linksProcessor;
		$this->magicWordsFinder = $magicWordsFinder;
		$this->redirectTargetFinder = $redirectTargetFinder;
		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	/**
	 * @since 2.5
	 *
	 * @param boolean $enabledLinksInValues
	 */
	public function enabledLinksInValues( $enabledLinksInValues ) {
		$this->enabledLinksInValues = $enabledLinksInValues;
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
		Timer::start( __CLASS__ );

		// Identifies the current parser run (especially when called recursively)
		$this->parserData->getSubject()->setContextReference( 'intp:' . uniqid() );

		$this->doStripMagicWordsFromText( $text );

		$this->isEnabledNamespace = $this->isSemanticEnabledForNamespace( $title );

		$this->addRedirectTargetAnnotationFromText(
			$text
		);

		// Obscure [/] to find a set of [[ :: ... ]] while those in-between are left for
		// decoding for a later processing so that the regex can split the text
		// appropriately
		if ( ( $this->enabledLinksInValues & SMW_LINV_OBFU ) != 0 ) {
			$text = Obfuscator::obfuscateLinks( $text, $this );
		}

		$linksInValuesPcre = ( $this->enabledLinksInValues & SMW_LINV_PCRE ) != 0;

		$text = preg_replace_callback(
			$this->getRegexpPattern( $linksInValuesPcre ),
			$linksInValuesPcre ? 'self::process' : 'self::preprocess',
			$text
		);

		// Ensure remaining encoded entities are decoded again
		$text = Obfuscator::removeLinkObfuscation( $text );

		if ( $this->isEnabledNamespace ) {
			$this->parserData->getOutput()->addModules( $this->getModules() );

			if ( method_exists( $this->parserData->getOutput(), 'recordOption' ) ) {
				$this->parserData->getOutput()->recordOption( 'userlang' );
			}
		}

		$this->parserData->pushSemanticDataToParserOutput();

		$this->parserData->addLimitReport(
			'intext-parsertime',
			Timer::getElapsedTime( __CLASS__, 3 )
		);

		SMWOutputs::commitToParserOutput( $this->parserData->getOutput() );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $text
	 *
	 * @return boolean
	 */
	public static function hasMarker( $text ) {
		return strpos( $text, self::OFF ) !== false || strpos( $text, self::ON ) !== false;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $text
	 *
	 * @return text
	 */
	public static function decodeSquareBracket( $text ) {
		return Obfuscator::decodeSquareBracket( $text );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $text
	 *
	 * @return text
	 */
	public static function obfuscateAnnotation( $text ) {
		return Obfuscator::obfuscateAnnotation( $text );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $text
	 *
	 * @return text
	 */
	public static function removeAnnotation( $text ) {
		return Obfuscator::removeAnnotation( $text );
	}

	/**
	 * @since 2.1
	 *
	 * @param Title|null $redirectTarget
	 */
	public function setRedirectTarget( Title $redirectTarget = null ) {
		$this->redirectTargetFinder->setRedirectTarget( $redirectTarget );
	}

	protected function addRedirectTargetAnnotationFromText( $text ) {

		if ( !$this->isEnabledNamespace ) {
			return;
		}

		$this->redirectTargetFinder->findRedirectTargetFromText( $text );

		$propertyAnnotatorFactory = $this->applicationFactory->singleton( 'PropertyAnnotatorFactory' );

		$propertyAnnotator = $propertyAnnotatorFactory->newNullPropertyAnnotator(
			$this->parserData->getSemanticData()
		);

		$redirectPropertyAnnotator = $propertyAnnotatorFactory->newRedirectPropertyAnnotator(
			$propertyAnnotator,
			$this->redirectTargetFinder
		);

		$redirectPropertyAnnotator->addAnnotation();
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
	 * @see LinksProcessor::getRegexpPattern
	 * @since 1.9
	 *
	 * @param boolean $linksInValues
	 *
	 * @return string
	 */
	public function getRegexpPattern( $linksInValues = false ) {
		return LinksProcessor::getRegexpPattern( $linksInValues );
	}

	/**
	 * @see linksProcessor::preprocess
	 * @since 1.9
	 *
	 * @param array $semanticLink expects (linktext, properties, value|caption)
	 *
	 * @return string
	 */
	public function preprocess( array $semanticLink ) {

		$semanticLinks = $this->linksProcessor->preprocess( $semanticLink );

		if ( is_string( $semanticLinks ) ) {
			return $semanticLinks;
		}

		return $this->process( $semanticLinks );
	}

	/**
	 * @see linksProcessor::process
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

		$semanticLinks = $this->linksProcessor->process(
			$semanticLink
		);

		$this->isAnnotation = $this->linksProcessor->isAnnotation();

		if ( is_string( $semanticLinks ) ) {
			return $semanticLinks;
		}

		list( $properties, $value, $valueCaption ) = $semanticLinks;

		$subject = $this->parserData->getSubject();

		if ( ( $propertyLink = $this->getPropertyLink( $subject, $properties, $value, $valueCaption ) ) !== '' ) {
			return $propertyLink;
		}

		return $this->addPropertyValue( $subject, $properties, $value, $valueCaption );
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
	protected function addPropertyValue( $subject, array $properties, $value, $valueCaption ) {

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

	private function isSemanticEnabledForNamespace( Title $title ) {
		return $this->applicationFactory->getNamespaceExaminer()->isSemanticEnabled( $title->getNamespace() );
	}

	private function getPropertyLink( $subject, $properties, $value, $valueCaption ) {

		// #1855
		if ( substr( $value, 0, 3 ) !== '@@@' ) {
			return '';
		}

		$property = end( $properties );

		$dataValue = $this->dataValueFactory->newPropertyValueByLabel(
			$property,
			$valueCaption,
			$subject
		);

		if ( ( $lang = Localizer::getAnnotatedLanguageCodeFrom( $value ) ) !== false ) {
			$dataValue->setOption( $dataValue::OPT_USER_LANGUAGE, $lang );
			$dataValue->setCaption(
				$valueCaption === false ? $dataValue->getWikiValue() : $valueCaption
			);
		}

		$dataValue->setOption( $dataValue::OPT_HIGHLIGHT_LINKER, true );

		return $dataValue->getShortWikitext( smwfGetLinker() );
	}

}
