<?php

namespace SMW\ParserFunctions;

use SMW\ParserData;
use SMW\ParserParameterProcessor;
use SMW\Subobject;
use SMW\MessageFormatter;
use SMW\Message;
use SMW\HashBuilder;
use SMW\DataValueFactory;
use SMW\DIProperty;
use Parser;

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
	 * embededding subject
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
	private $enabledNormalization = false;

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
	 * FIXME 3.0, make sorting default with 3.0
	 *
	 * Ensures that unordered parameters and property names are normalized in
	 * order to produce the same has even if elements are placed differently
	 *
	 * @since 2.5
	 *
	 * @param boolean $enabledNormalization
	 */
	public function enabledNormalization( $enabledNormalization = true ) {
		$this->enabledNormalization = (bool)$enabledNormalization;
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
	 * @since 1.9
	 *
	 * @param ParserParameterProcessor $params
	 *
	 * @return string|null
	 */
	public function parse( ParserParameterProcessor $parameters ) {

		if (
			$this->parserData->canModifySemanticData() &&
			$this->addDataValuesToSubobject( $parameters ) &&
			!$this->subobject->getSemanticData()->isEmpty()  ) {
			$this->parserData->getSemanticData()->addSubobject( $this->subobject );
		}

		$this->parserData->pushSemanticDataToParserOutput();

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
				Message::encode( array( 'smw-subobject-parser-invalid-naming-scheme', $parserParameterProcessor->getFirst() ) )
			);
		}

		list( $parameters, $id ) = $this->getParameters(
			$parserParameterProcessor
		);

		$this->subobject->setEmptyContainerForId(
			$id
		);

		$subject = $this->subobject->getSubject();

		foreach ( $parameters as $property => $values ) {

			if ( $property === self::PARAM_SORTKEY ) {
				$property = DIProperty::TYPE_SORTKEY;
			}

			if ( $property === self::PARAM_CATEGORY ) {
				$property = DIProperty::TYPE_CATEGORY;
			}

			foreach ( $values as $value ) {

				$dataValue = DataValueFactory::getInstance()->newDataValueByText(
						$property,
						$value,
						false,
						$subject
					);

				$this->subobject->addDataValue( $dataValue );
			}
		}

		$this->doAugmentSortKeyOnAccessibleDisplayTitle(
			$this->subobject->getSemanticData()
		);

		return true;
	}

	private function getParameters( ParserParameterProcessor $parserParameterProcessor ) {

		$id = $parserParameterProcessor->getFirst();
		$isAnonymous = in_array( $id, array( null, '' ,'-' ) );

		$useFirstElementAsPropertyLabel = $this->useFirstElementAsPropertyLabel && !$isAnonymous;

		$parameters = $this->doPrepareParameters(
			$parserParameterProcessor,
			$useFirstElementAsPropertyLabel
		);

		// Reclaim the ID to be content hash based
		if ( $useFirstElementAsPropertyLabel || $isAnonymous ) {
			$id = HashBuilder::createFromContent( $parameters, '_' );
		}

		return array( $parameters, $id );
	}

	private function doPrepareParameters( ParserParameterProcessor $parserParameterProcessor, $useFirstElementAsPropertyLabel ) {

		if ( $parserParameterProcessor->hasParameter( self::PARAM_LINKWITH ) ) {
			$val = $parserParameterProcessor->getParameterValuesByKey( self::PARAM_LINKWITH );
			$parserParameterProcessor->addParameter(
				end( $val ),
				$this->parserData->getTitle()->getPrefixedText()
			);

			$parserParameterProcessor->removeParameterByKey( self::PARAM_LINKWITH );
		}

		if ( $useFirstElementAsPropertyLabel ) {
			$parserParameterProcessor->addParameter(
				$parserParameterProcessor->getFirst(),
				$this->parserData->getTitle()->getPrefixedText()
			);
		}

		$parameters = $parserParameterProcessor->toArray();

		if ( !$this->enabledNormalization ) {
			return $parameters;
		}

		// Normalize property names to generate the same hash for when
		// CapitalLinks is enabled (has foo === Has foo)
		foreach ( $parameters as $property => $values ) {

			$prop = $property;

			// Order of the values is not guaranteed
			rsort( $values );

			if ( $property{0} !== '@' && $this->isCapitalLinks ) {
				$property = mb_strtoupper( mb_substr( $property, 0, 1 ) ) . mb_substr( $property, 1 );
			}

			unset( $parameters[$prop] );
			$parameters[$property] = $values;
		}

		// Sort the array by property name to ensure that a different order would
		// always create the same hash
		ksort( $parameters );

		return $parameters;
	}

	private function doAugmentSortKeyOnAccessibleDisplayTitle( $semanticData ) {

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
