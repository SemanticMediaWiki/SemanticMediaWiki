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
	 * Fixed identifier that describes the subobject category parameter.
	 *
	 * We keep it as a @ fixed parameter since the standard annotation would
	 * require special attention (Category:;instead of ::) when annotating a
	 * category
	 */
	const PARAM_CATEGORY = '@category';

	/**
	 * Fixed identifier that describes a property by which a subobject is auto
	 * linked to the an embededding subject
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
	private $isEnabledFirstElementAsPropertyLabel = false;

	/**
	 * @var boolean
	 */
	private $usesCapitalLinks = true;

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
	 * @note If $wgCapitalLinks is set false then it will avoid forcing the first
	 * letter of page titles (including included pages, images and categories)
	 * to capitals
	 *
	 * @since 2.5
	 *
	 * @param booelan $usesCapitalLinks
	 */
	public function usesCapitalLinks( $usesCapitalLinks ) {
		$this->usesCapitalLinks = $usesCapitalLinks;
	}

	/**
	 * FIXME 3.0, make sorting default with 3.0
	 *
	 * Ensures that unordered parameters is ordered and normalized and will
	 * produce the same ID even if elements are placed differently
	 *
	 * @since 2.5
	 *
	 * @param booelan $enabledNormalization
	 */
	public function enabledNormalization( $enabledNormalization = true ) {
		$this->enabledNormalization = (bool)$enabledNormalization;
	}

	/**
	 * @since 1.9
	 *
	 * @param boolean $isEnabledFirstElementAsPropertyLabel
	 *
	 * @return SubobjectParserFunction
	 */
	public function setFirstElementAsPropertyLabel( $isEnabledFirstElementAsPropertyLabel = true ) {
		$this->isEnabledFirstElementAsPropertyLabel = (bool)$isEnabledFirstElementAsPropertyLabel;
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

		// Named subobjects containing a "." in the first five characters are reserved to be
		// used by extensions only in order to separate them from user land and avoid having
		// them accidentally to refer to the same named ID
		// (i.e. different access restrictions etc.)
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

		$this->doAugmentSortKeyForWhenDisplayTitleIsAccessible(
			$this->subobject->getSemanticData()
		);

		return true;
	}

	private function getParameters( ParserParameterProcessor $parserParameterProcessor ) {

		$id = $parserParameterProcessor->getFirst();
		$isAnonymous = in_array( $id, array( null, '' ,'-' ) );

		$this->isEnabledFirstElementAsPropertyLabel = $this->isEnabledFirstElementAsPropertyLabel && !$isAnonymous;

		$parameters = $this->doPrepareParameters(
			$parserParameterProcessor
		);

		// Reclaim the ID to be hash based on the content
		if ( $this->isEnabledFirstElementAsPropertyLabel || $isAnonymous ) {
			$id = HashBuilder::createHashIdForContent( $parameters, '_' );
		}

		return array( $parameters, $id );
	}

	private function doPrepareParameters( ParserParameterProcessor $parserParameterProcessor ) {

		if ( $parserParameterProcessor->hasParameter( self::PARAM_LINKWITH ) ) {
			$val = $parserParameterProcessor->getParameterValuesByKey( self::PARAM_LINKWITH );
			$parserParameterProcessor->addParameter(
				end( $val ),
				$this->parserData->getTitle()->getPrefixedText()
			);

			$parserParameterProcessor->removeParameterByKey( self::PARAM_LINKWITH );
		}

		if ( $this->isEnabledFirstElementAsPropertyLabel ) {
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

			if ( $property{0} === '@' || !$this->usesCapitalLinks ) {
				continue;
			}

			$prop = mb_strtoupper( mb_substr( $property, 0, 1 ) ) . mb_substr( $property, 1 );

			unset( $parameters[$property] );
			$parameters[$prop] = $values;
		}

		ksort( $parameters );

		return $parameters;
	}

	private function doAugmentSortKeyForWhenDisplayTitleIsAccessible( $semanticData ) {

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
