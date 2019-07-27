<?php

namespace SMW\Parser;

use Hooks;
use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\Localizer;
use SMW\SemanticData;
use SMW\MediaWiki\MagicWordsFinder;
use SMW\MediaWiki\RedirectTargetFinder;
use SMW\MediaWiki\StripMarkerDecoder;
use SMW\ParserData;
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
	 * @var AnnotationProcessor
	 */
	private $annotationProcessor;

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory = null;

	/**
	 * @var StripMarkerDecoder
	 */
	private $stripMarkerDecoder;

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
	private $isLinksInValues = false;

	/**
	 * @var boolean
	 */
	private $showErrors = true;

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
		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	/**
	 * @since 2.5
	 *
	 * @param boolean $isLinksInValues
	 */
	public function isLinksInValues( $isLinksInValues ) {
		$this->isLinksInValues = $isLinksInValues;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $showErrors
	 */
	public function showErrors( $showErrors ) {
		$this->showErrors = (bool)$showErrors;
	}

	/**
	 * @since 3.1
	 *
	 * @return SemanticData
	 */
	public function getSemanticData() {
		return $this->parserData->getSemanticData();
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
		Timer::start( __CLASS__ );

		// Identifies the current parser run (especially when called recursively)
		$this->parserData->getSubject()->setContextReference( 'intp:' . uniqid() );

		$this->doStripMagicWordsFromText( $text );

		$this->isEnabledNamespace = $this->isSemanticEnabledForNamespace( $title );

		$this->addRedirectTargetAnnotationFromText(
			$text
		);

		$this->annotationProcessor = new AnnotationProcessor(
			$this->parserData->getSemanticData(),
			DataValueFactory::getInstance()
		);

		// Obscure [/] to find a set of [[ :: ... ]] while those in-between are left for
		// decoding in a post-processing so that the regex can split the text
		// appropriately
		if ( $this->isLinksInValues ) {
			$text = LinksEncoder::findAndEncodeLinks( $text, $this );
		}

		// No longer used with 3.0 given that the LinksEncoder is safer and faster
		$linksInValuesPcre = false;

		$text = preg_replace_callback(
			$this->getRegexpPattern( $linksInValuesPcre ),
			$linksInValuesPcre ? 'self::process' : 'self::preprocess',
			$text
		);

		$this->annotationProcessor->setCanAnnotate(
			$this->parserData->canUse()
		);

		Hooks::run( 'SMW::Parser::AfterLinksProcessingComplete',
			[
				&$text,
				$this->annotationProcessor
			]
		);

		// Ensure remaining encoded entities are decoded again
		$text = LinksEncoder::removeLinkObfuscation( $text );

		if ( $this->isEnabledNamespace ) {
			$this->parserData->getOutput()->addModules( $this->getModules() );
			$this->parserData->addExtraParserKey( 'userlang' );
		}

		$this->parserData->copyToParserOutput();

		// Remove context
		$this->annotationProcessor->release();

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
	 * @since 3.1
	 *
	 * @param string $text
	 *
	 * @return boolean
	 */
	public static function hasPropertyLink( $text ) {
		return strpos( $text, '::@@@' ) !== false;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $text
	 *
	 * @return text
	 */
	public static function decodeSquareBracket( $text ) {
		return LinksEncoder::decodeSquareBracket( $text );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $text
	 *
	 * @return text
	 */
	public static function obfuscateAnnotation( $text ) {
		return LinksEncoder::obfuscateAnnotation( $text );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $text
	 *
	 * @return text
	 */
	public static function removeAnnotation( $text ) {
		return LinksEncoder::removeAnnotation( $text );
	}

	/**
	 * @since 3.0
	 *
	 * @param StripMarkerDecoder $stripMarkerDecoder
	 */
	public function setStripMarkerDecoder( StripMarkerDecoder $stripMarkerDecoder ) {
		$this->stripMarkerDecoder = $stripMarkerDecoder;
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
		return [
			'ext.smw.style',
			'ext.smw.tooltips'
		];
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

		// #1855
		if ( substr( $value, 0, 3 ) === '@@@' ) {
			return $this->makePropertyLink( $subject, $properties, $value, $valueCaption );
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

		$origValue = $value;

		if ( $this->stripMarkerDecoder !== null ) {
			$value = $this->stripMarkerDecoder->decode( $value );
		}

		// Add properties to the semantic container
		foreach ( $properties as $property ) {
			$dataValue = $this->annotationProcessor->newDataValueByText(
				$property,
				$value,
				$valueCaption,
				$subject
			);

			if (
				$this->isEnabledNamespace &&
				$this->isAnnotation &&
				$this->parserData->canUse() ) {
				$this->parserData->addDataValue( $dataValue );
			}
		}

		// Return the wikitext or the unmodified text representation in case of
		// a strip marker in order for the standard Parser to work its magic since
		// we were only interested in the value for the annotation
		if ( $origValue !== $value ) {
			$result = $origValue;
		} else {
			$result = $dataValue->getShortWikitext( true );
		}

		// If necessary add an error text
		if ( ( $this->showErrors &&
			$this->isEnabledNamespace && $this->isAnnotation ) &&
			( !$dataValue->isValid() ) ) {
			// Encode `:` to avoid a comment block and instead of the nowiki tag
			// use &#58; as placeholder
			$result = str_replace( ':', '&#58;', $result ) . $dataValue->getErrorText();
		}

		return $result;
	}

	protected function doStripMagicWordsFromText( &$text ) {

		$words = [];

		$this->magicWordsFinder->setOutput( $this->parserData->getOutput() );

		$magicWords = [
			'SMW_NOFACTBOX',
			'SMW_SHOWFACTBOX'
		];

		Hooks::run( 'SMW::Parser::BeforeMagicWordsFinder', [ &$magicWords ] );

		foreach ( $magicWords as $magicWord ) {
			$words[] = $this->magicWordsFinder->findMagicWordInText( $magicWord, $text );
		}

		$this->magicWordsFinder->pushMagicWordsToParserOutput( $words );

		return $words;
	}

	private function isSemanticEnabledForNamespace( Title $title ) {
		return $this->applicationFactory->getNamespaceExaminer()->isSemanticEnabled( $title->getNamespace() );
	}

	private function makePropertyLink( $subject, $properties, $value, $caption ) {

		$property = end( $properties );
		$linker = smwfGetLinker();
		$class = 'smw-property';

		// #4037
		// [[Foo::@@@|#] where `|#` indicates a noLink request
		if ( $caption === '#' ) {
			$linker = false;
			$caption = false;
			$class = 'smw-property nolink';
		}

		$dataValue = DataValueFactory::getInstance()->newPropertyValueByLabel(
			$property,
			$caption,
			$subject
		);

		$dataValue->setLinkAttributes( [ 'class' => $class ] );

		if ( ( $lang = Localizer::getAnnotatedLanguageCodeFrom( $value ) ) !== false ) {
			$dataValue->setOption( $dataValue::OPT_USER_LANGUAGE, $lang );
			$dataValue->setCaption(
				$caption === false ? $dataValue->getWikiValue() : $caption
			);
		}

		$dataValue->setOption( $dataValue::OPT_HIGHLIGHT_LINKER, true );

		return $dataValue->getShortWikitext( $linker );
	}

}
