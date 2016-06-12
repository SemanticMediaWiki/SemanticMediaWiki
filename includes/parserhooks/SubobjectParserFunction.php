<?php

namespace SMW;

use Parser;

/**
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
	 * @var DataValueFactory
	 */
	private $dataValueFactory = null;

	/**
	 * @var boolean
	 */
	private $isEnabledFirstElementPropertyLabel = false;

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
		$this->dataValueFactory = DataValueFactory::getInstance();
	}

	/**
	 * @since 1.9
	 *
	 * @param boolean $isEnabledFirstElementPropertyLabel
	 *
	 * @return SubobjectParserFunction
	 */
	public function setFirstElementForPropertyLabel( $isEnabledFirstElementPropertyLabel = true ) {
		$this->isEnabledFirstElementPropertyLabel = (bool)$isEnabledFirstElementPropertyLabel;
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

		return $this->messageFormatter
			->addFromArray( $this->subobject->getErrors() )
			->addFromArray( $this->parserData->getErrors() )
			->addFromArray( $parameters->getErrors() )
			->getHtml();
	}

	protected function addDataValuesToSubobject( ParserParameterProcessor $parserParameterProcessor ) {

		// Named subobjects containing a "." in the first five characters are reserved to be
		// used by extensions only in order to separate them from user land and avoid having
		// them accidentally to refer to the same named ID
		// (i.e. different access restrictions etc.)
		if ( strpos( mb_substr( $parserParameterProcessor->getFirst(), 0, 5 ), '.' ) !== false ) {
			return $this->addErrorWithMsg(
				$this->parserData->getSemanticData()->getSubject(),
				wfMessage( 'smw-subobject-parser-invalid-naming-scheme', $parserParameterProcessor->getFirst() )->escaped()
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

				$dataValue = $this->dataValueFactory->newDataValueByText(
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

		$this->isEnabledFirstElementPropertyLabel = $this->isEnabledFirstElementPropertyLabel && !$isAnonymous;

		$parameters = $this->doPrepareParameters(
			$parserParameterProcessor
		);

		// Reclaim the ID to be hash based on the content
		if ( $this->isEnabledFirstElementPropertyLabel || $isAnonymous ) {
			$id = HashBuilder::createHashIdForContent( $parameters, '_' );
		}

		return array( $parameters, $id );
	}

	private function doPrepareParameters( ParserParameterProcessor $parserParameterProcessor ) {

		if ( $this->isEnabledFirstElementPropertyLabel ) {
			$parserParameterProcessor->addParameter(
				$parserParameterProcessor->getFirst(),
				$this->parserData->getTitle()->getPrefixedText()
			);
		}

		$parameters = $parserParameterProcessor->toArray();

		// FIXME 3.0 make sorting default by 3.0
		// Only sort for a modified sobj otherwise existing ID will change
		$sort = false;

		// This ensures that an unordered array is ordered and will produce
		// the same ID even if elements are placed differently
		if ( $sort ) {
			ksort( $parameters );
		}

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

	private function addErrorWithMsg( $subject, $errorMsg ) {

		$error = new Error( $subject );

		$this->parserData->getSemanticData()->addPropertyObjectValue(
			$error->getProperty(),
			$error->getContainerFor(
				new DIProperty( '_SOBJ' ),
				$errorMsg
			)
		);

		$this->parserData->addError( $errorMsg );

		return false;
	}

}
