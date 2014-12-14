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

	// Fixed identifier that describes the sortkey annotation
	// parameter
	const PARAM_SORTKEY = '@sortkey';

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
	private $useFirstElementForPropertyLabel = false;

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
	 * @param boolean $useFirstElementForPropertyLabel
	 *
	 * @return SubobjectParserFunction
	 */
	public function setFirstElementForPropertyLabel( $useFirstElementForPropertyLabel = true ) {
		$this->useFirstElementForPropertyLabel = (bool)$useFirstElementForPropertyLabel;
		return $this;
	}

	/**
	 * @since 1.9
	 *
	 * @param ArrayFormatter $params
	 *
	 * @return string|null
	 */
	public function parse( ArrayFormatter $parameters ) {

		$this->addDataValuesToSubobject( $parameters );

		$this->parserData->getSemanticData()->addSubobject( $this->subobject );
		$this->parserData->pushSemanticDataToParserOutput();

		return $this->messageFormatter
			->addFromArray( $this->subobject->getErrors() )
			->addFromArray( $this->parserData->getErrors() )
			->addFromArray( $parameters->getErrors() )
			->getHtml();
	}

	protected function addDataValuesToSubobject( ArrayFormatter $parameters ) {

		$subject = $this->parserData->getSemanticData()->getSubject();

		$this->subobject->setEmptyContainerForId( $this->createSubobjectId( $parameters ) );

		foreach ( $this->transformParametersToArray( $parameters ) as $property => $values ) {

			if ( $property === self::PARAM_SORTKEY ) {
				$property = DIProperty::TYPE_SORTKEY;
			}

			foreach ( $values as $value ) {

				$dataValue = $this->dataValueFactory->newPropertyValue(
						$property,
						$value,
						false,
						$subject
					);

				$this->subobject->addDataValue( $dataValue );
			}
		}
	}

	private function createSubobjectId( ArrayFormatter $parameters ) {

		$isAnonymous = in_array( $parameters->getFirst(), array( null, '' ,'-' ) );

		$this->useFirstElementForPropertyLabel = $this->useFirstElementForPropertyLabel && !$isAnonymous;

		if ( $this->useFirstElementForPropertyLabel || $isAnonymous ) {
			return HashBuilder::createHashIdForContent( $parameters->toArray(), '_' );
		}

		return $parameters->getFirst();
	}

	private function transformParametersToArray( ArrayFormatter $parameters ) {

		if ( $this->useFirstElementForPropertyLabel ) {
			$parameters->addParameter(
				$parameters->getFirst(),
				$this->parserData->getTitle()->getPrefixedText()
			);
		}

		return $parameters->toArray();
	}

}
