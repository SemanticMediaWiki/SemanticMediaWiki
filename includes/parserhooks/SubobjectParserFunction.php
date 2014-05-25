<?php

namespace SMW;

use Parser;

/**
 * Provides the {{#subobject}} parser function
 *
 * @see http://www.semantic-mediawiki.org/wiki/Help:ParserFunction
 *
 * @ingroup ParserFunction
 *
 * @licence GNU GPL v2+
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
	protected $msgFormatter;

	/** @var boolean */
	protected $objectReference = false;

	/**
	 * @since 1.9
	 *
	 * @param ParserData $parserData
	 * @param Subobject $subobject
	 * @param MessageFormatter $msgFormatter
	 */
	public function __construct( ParserData $parserData, Subobject $subobject, MessageFormatter $msgFormatter ) {
		$this->parserData = $parserData;
		$this->subobject = $subobject;
		$this->msgFormatter = $msgFormatter;
	}

	/**
	 * Enables/disables to create an object reference pointing to the original
	 * subject
	 *
	 * @since 1.9
	 *
	 * @param boolean $objectReference
	 *
	 * @return SubobjectParserFunction
	 */
	public function setObjectReference( $objectReference ) {
		$this->objectReference = $objectReference;
		return $this;
	}

	/**
	 * Parse parameters and return results to the ParserOutput object
	 *
	 * @since 1.9
	 *
	 * @param ArrayFormatter $params
	 *
	 * @return string|null
	 */
	public function parse( ArrayFormatter $parameters ) {

		$this->addSubobjectValues( $parameters );

		$this->parserData->getSemanticData()->addPropertyObjectValue(
			$this->subobject->getProperty(),
			$this->subobject->getContainer()
		);

		$this->parserData->updateOutput();

		return $this->msgFormatter
			->addFromArray( $this->subobject->getErrors() )
			->addFromArray( $this->parserData->getErrors() )
			->addFromArray( $parameters->getErrors() )
			->getHtml();
	}

	protected function addSubobjectValues( ArrayFormatter $parameters ) {

		$subject = $this->parserData->getSemanticData()->getSubject();

		$this->subobject->setSemanticData( $this->createSubobjectId( $parameters ) );

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

		$this->objectReference = $this->objectReference && !$isAnonymous;

		if ( $this->objectReference || $isAnonymous ) {
			return $this->subobject->generateId( new HashIdGenerator( $parameters->toArray(), '_' ) );
		}

		return $parameters->getFirst();
	}

	protected function transformParametersToArray( ArrayFormatter $parameters ) {

		if ( $this->objectReference ) {
			$parameters->addParameter(
				$parameters->getFirst(),
				$this->parserData->getTitle()->getPrefixedText()
			);
		}

		return $parameters->toArray();
	}

}
