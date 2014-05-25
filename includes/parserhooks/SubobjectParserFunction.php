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

	const PARAM_SORTKEY = '@sortkey';

	/** @var ParserData */
	protected $parserData;

	/** @var Subobject */
	protected $subobject;

	/** @var MessageFormatter */
	protected $messageFormatter;

	/** @var boolean */
	protected $firstElementAsProperty = false;

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
	 * @since 1.9
	 *
	 * @param boolean $firstElementAsProperty
	 *
	 * @return SubobjectParserFunction
	 */
	public function setFirstElementAsProperty( $firstElementAsProperty = true ) {
		$this->firstElementAsProperty = (bool)$firstElementAsProperty;
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

		$this->parserData->getSemanticData()->addPropertyObjectValue(
			$this->subobject->getProperty(),
			$this->subobject->getContainer()
		);

		$this->parserData->updateOutput();

		return $this->messageFormatter
			->addFromArray( $this->subobject->getErrors() )
			->addFromArray( $this->parserData->getErrors() )
			->addFromArray( $parameters->getErrors() )
			->getHtml();
	}

	protected function addDataValuesToSubobject( ArrayFormatter $parameters ) {

		$subject = $this->parserData->getSemanticData()->getSubject();

		$this->subobject->setEmptySemanticDataForId( $this->createSubobjectId( $parameters ) );

		foreach ( $this->transformParametersToArray( $parameters ) as $property => $values ) {

			if ( $property === self::PARAM_SORTKEY ) {
				$property = DIProperty::TYPE_SORTKEY;
			}

			foreach ( $values as $value ) {

				$dataValue = DataValueFactory::getInstance()->newPropertyValue(
						$property,
						$value,
						false,
						$subject
					);

				$this->subobject->addDataValue( $dataValue );
			}
		}
	}

	protected function createSubobjectId( ArrayFormatter $parameters ) {

		$isAnonymous = in_array( $parameters->getFirst(), array( null, '' ,'-' ) );

		$this->firstElementAsProperty = $this->firstElementAsProperty && !$isAnonymous;

		if ( $this->firstElementAsProperty || $isAnonymous ) {
			return $this->subobject->generateId( new HashIdGenerator( $parameters->toArray(), '_' ) );
		}

		return $parameters->getFirst();
	}

	protected function transformParametersToArray( ArrayFormatter $parameters ) {

		if ( $this->firstElementAsProperty ) {
			$parameters->addParameter(
				$parameters->getFirst(),
				$this->parserData->getTitle()->getPrefixedText()
			);
		}

		return $parameters->toArray();
	}

}
