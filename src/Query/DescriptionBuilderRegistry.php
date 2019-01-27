<?php

namespace SMW\Query;

use SMW\Query\DescriptionBuilders\DescriptionBuilder;
use SMW\Query\DescriptionBuilders\DispatchingDescriptionBuilder;
use SMW\Query\DescriptionBuilders\MonolingualTextValueDescriptionBuilder;
use SMW\Query\DescriptionBuilders\NumberValueDescriptionBuilder;
use SMW\Query\DescriptionBuilders\RecordValueDescriptionBuilder;
use SMW\Query\DescriptionBuilders\SomeValueDescriptionBuilder;
use SMW\Query\DescriptionBuilders\TimeValueDescriptionBuilder;
use SMWDataValue as DataValue;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class DescriptionBuilderRegistry {

	/**
	 * @var DescriptionBuilder[]
	 */
	private $descriptionBuilders = [];

	/**
	 * @var DescriptionBuilder
	 */
	private $defaultDescriptionBuilder;

	/**
	 * @note This allows extensions to inject their own DescriptionBuilder
	 * without further violating SRP of the DataType or DataValue.
	 *
	 * @since 2.3
	 *
	 * @param DescriptionBuilder $descriptionBuilder
	 */
	public function registerDescriptionBuilder( DescriptionBuilder $descriptionBuilder ) {

		if ( $this->descriptionBuilders === [] ) {
			$this->initDescriptionBuilders();
		}

		$this->descriptionBuilders[] = $descriptionBuilder;
	}

	/**
	 * @since 2.3
	 *
	 * @param DataValue $dataValue
	 *
	 * @return DescriptionBuilder
	 * @throws RuntimeException
	 */
	public function getDescriptionBuilder( DataValue $dataValue ) {

		if ( $this->descriptionBuilders === [] ) {
			$this->initDescriptionBuilders();
		}

		foreach ( $this->descriptionBuilders as $descriptionBuilder ) {
			if ( $descriptionBuilder->isBuilderFor( $dataValue ) ) {
				return $descriptionBuilder;
			}
		}

		if ( $this->defaultDescriptionBuilder->isBuilderFor( $dataValue ) ) {
			return $this->defaultDescriptionBuilder;
		}

		throw new RuntimeException( "Missing registered DescriptionBuilder for: " . $dataValue->getTypeID() );
	}

	private function initDescriptionBuilders() {

		$this->descriptionBuilders[] = new TimeValueDescriptionBuilder();
		$this->descriptionBuilders[] = new NumberValueDescriptionBuilder();
		$this->descriptionBuilders[] = new RecordValueDescriptionBuilder();
		$this->descriptionBuilders[] = new MonolingualTextValueDescriptionBuilder();

		$this->defaultDescriptionBuilder = new SomeValueDescriptionBuilder();
	}

}
