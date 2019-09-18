<?php

namespace SMW\ParserFunctions;

use Parser;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\HashBuilder;
use SMW\MediaWiki\StripMarkerDecoder;
use SMW\Message;
use SMW\MessageFormatter;
use SMW\ParserData;
use SMW\ParserParameterProcessor;
use SMW\SemanticData;
use SMW\Subobject;
use SMW\Parser\AnnotationProcessor;

/**
 * @private This class should not be instantiated directly, please use
 * ParserFunctionFactory::newSubobjectParserFunction
 *
 * Provides the {{#subobject}} parser function
 *
 * @see http://www.semantic-mediawiki.org/wiki/Help:ParserFunction
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SubobjectParserFunction {

	/**
	 * Fixed identifier that describes the sortkey annotation parameter
	 */
	const PARAM_SORTKEY = '@sortkey';

	/**
	 * Fixed identifier that describes a category parameter
	 *
	 * Those will not be visible by the "standard" category list as the handling
	 * of assigned categories is SMW specific for subobjects.
	 */
	const PARAM_CATEGORY = '@category';

	/**
	 * Fixed identifier that describes a property that can auto-linked the
	 * embeddedding subject
	 */
	const PARAM_LINKWITH = '@linkWith';

	/**
	 * @var ParserData
	 */
	protected $parserData;

	/**
	 * @var Subobject
	 */
	protected $subobject;

	/**
	 * @var MessageFormatter
	 */
	protected $messageFormatter;

	/**
	 * @var StripMarkerDecoder
	 */
	private $stripMarkerDecoder;

	/**
	 * @var boolean
	 */
	private $useFirstElementAsPropertyLabel = false;

	/**
	 * @var boolean
	 */
	private $isCapitalLinks = true;

	/**
	 * @var boolean
	 */
	private $isComparableContent = false;

	/**
	 * @since 1.9
	 *
	 * @param ParserData $parserData
	 * @param Subobject $subobject
	 * @param MessageFormatter $messageFormatter
	 */
	public function __construct( ParserData $parserData, Subobject $subobject, MessageFormatter $messageFormatter ) {
		$this->parserData = $parserData;
		$this->subobject = $subobject;
		$this->messageFormatter = $messageFormatter;
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
	 * @see $wgCapitalLinks
	 *
	 * @since 2.5
	 *
	 * @param boolean $isCapitalLinks
	 */
	public function isCapitalLinks( $isCapitalLinks ) {
		$this->isCapitalLinks = $isCapitalLinks;
	}

	/**
	 * Ensures that unordered parameters and property names are normalized and
	 * sorted to produce the same hash even if elements of the same literal
	 * representation are placed differently.
	 *
	 * @since 3.0
	 *
	 * @param boolean $isComparableContent
	 */
	public function isComparableContent( $isComparableContent = true ) {
		$this->isComparableContent = (bool)$isComparableContent;
	}

	/**
	 * @since 1.9
	 *
	 * @param boolean $useFirstElementAsPropertyLabel
	 *
	 * @return SubobjectParserFunction
	 */
	public function useFirstElementAsPropertyLabel( $useFirstElementAsPropertyLabel = true ) {
		$this->useFirstElementAsPropertyLabel = (bool)$useFirstElementAsPropertyLabel;
		return $this;
	}

	/**
	 * @since 3.1
	 *
	 * @return SemanticData
	 */
	public function getSemanticData() {
		return $this->subobject->getSemanticData();
	}

	/**
	 * @since 1.9
	 *
	 * @param ParserParameterProcessor $params
	 *
	 * @return string|null
	 */
	public function parse( ParserParameterProcessor $parameters ) {

		if (
			$this->parserData->canUse() &&
			$this->addDataValuesToSubobject( $parameters ) &&
			$this->subobject->getSemanticData()->isEmpty() === false ) {
			$this->parserData->getSemanticData()->addSubobject( $this->subobject );
		}

		$this->parserData->copyToParserOutput();

		$html = $this->messageFormatter->addFromArray( $this->subobject->getErrors() )
			->addFromArray( $this->parserData->getErrors() )
			->addFromArray( $parameters->getErrors() )
			->getHtml();

		// An empty output in MW forces an extra <br> element.
		//if ( $html == '' ) {
		//	$html = '<p></p>';
		//}

		return $html;
	}

	protected function addDataValuesToSubobject( ParserParameterProcessor $parserParameterProcessor ) {

		// Named subobjects containing a "." in the first five characters are
		// reserved to be used by extensions only in order to separate them from
		// user land and avoid having them accidentally to refer to the same
		// named ID (i.e. different access restrictions etc.)
		if ( strpos( mb_substr( $parserParameterProcessor->getFirst(), 0, 5 ), '.' ) !== false ) {
			return $this->parserData->addError(
				Message::encode( [ 'smw-subobject-parser-invalid-naming-scheme', $parserParameterProcessor->getFirst() ] )
			);
		}

		list( $parameters, $id ) = $this->getParameters(
			$parserParameterProcessor
		);

		$this->subobject->setEmptyContainerForId(
			$id
		);

		$subject = $this->subobject->getSubject();

		$annotationProcessor = new AnnotationProcessor(
			$this->subobject->getSemanticData(),
			DataValueFactory::getInstance()
		);

		foreach ( $parameters as $property => $values ) {

			if ( $property === self::PARAM_SORTKEY ) {
				$property = DIProperty::TYPE_SORTKEY;
			}

			if ( $property === self::PARAM_CATEGORY ) {
				$property = DIProperty::TYPE_CATEGORY;
			}

			foreach ( $values as $value ) {

				$dataValue = $annotationProcessor->newDataValueByText(
						$property,
						$value,
						false,
						$subject
					);

				$this->subobject->addDataValue( $dataValue );

			}
		}

		$this->augment( $this->subobject->getSemanticData() );

		$annotationProcessor->release( SemanticData::class );

		return true;
	}

	private function getParameters( ParserParameterProcessor $parserParameterProcessor ) {

		$id = $parserParameterProcessor->getFirst();
		$isAnonymous = in_array( $id, [ null, '' ,'-' ] );

		$useFirst = $this->useFirstElementAsPropertyLabel && !$isAnonymous;

		$parameters = $this->preprocess(
			$parserParameterProcessor,
			$useFirst
		);

		// FIXME remove the check with 3.1, should be standard by then!
		if ( !$this->isComparableContent ) {
			$p = $parameters;
		} else {
			$p = $parameters;
			// Sort the copy not the parameters itself
			$parserParameterProcessor->sort( $p );
		}

		// Reclaim the ID to be content hash based
		if ( $useFirst || $isAnonymous ) {
			$id = HashBuilder::createFromContent( $p, '_' );
		}

		return [ $parameters, $id ];
	}

	private function preprocess( ParserParameterProcessor $parserParameterProcessor, $useFirst ) {

		if ( $parserParameterProcessor->hasParameter( self::PARAM_LINKWITH ) ) {
			$val = $parserParameterProcessor->getParameterValuesByKey( self::PARAM_LINKWITH );
			$parserParameterProcessor->addParameter(
				end( $val ),
				$this->parserData->getTitle()->getPrefixedText()
			);

			$parserParameterProcessor->removeParameterByKey( self::PARAM_LINKWITH );
		}

		if ( $useFirst ) {
			$parserParameterProcessor->addParameter(
				$parserParameterProcessor->getFirst(),
				$this->parserData->getTitle()->getPrefixedText()
			);
		}

		$parameters = $this->decode(
			$parserParameterProcessor->toArray()
		);

		foreach ( $parameters as $property => $values ) {

			$prop = $property;

			// Normalize property names to generate the same hash for when
			// CapitalLinks is enabled (has foo === Has foo)
			if ( $property !== '' && $property[0] !== '@' && $this->isCapitalLinks ) {
				$property = mb_strtoupper( mb_substr( $property, 0, 1 ) ) . mb_substr( $property, 1 );
			}

			unset( $parameters[$prop] );
			$parameters[$property] = $values;
		}

		return $parameters;
	}

	private function decode( $parameters ) {

		if ( $this->stripMarkerDecoder === null || !$this->stripMarkerDecoder->canUse() ) {
			return $parameters;
		}

		// Any decoding has to happen before the subject ID is generated otherwise
		// the value would contain something like `UNIQ--nowiki-00000011-QINU`
		// and be part of the hash. `UNIQ--nowiki-00000011-QINU` isn't stable
		// and changes to text will create new marker positions therefore it
		// cannot be part of the hash computation
		foreach ( $parameters as $property => &$values ) {
			foreach ( $values as &$value ) {
				$value = $this->stripMarkerDecoder->decode( $value );
			}
		}

		return $parameters;
	}

	private function augment( $semanticData ) {

		// Data block created by a user
		$semanticData->setOption( SemanticData::PROC_USER, true );

		$sortkey = new DIProperty( DIProperty::TYPE_SORTKEY );
		$displayTitle = new DIProperty( DIProperty::TYPE_DISPLAYTITLE );

		if ( $semanticData->hasProperty( $sortkey ) || !$semanticData->hasProperty( $displayTitle ) ) {
			return null;
		}

		$pv = $semanticData->getPropertyValues(
			$displayTitle
		);

		$semanticData->addPropertyObjectValue(
			$sortkey,
			end( $pv )
		);
	}

}
