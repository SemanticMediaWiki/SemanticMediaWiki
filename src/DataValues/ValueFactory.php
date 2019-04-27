<?php

namespace SMW\DataValues;

use SMW\ApplicationFactory;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ValueFactory {

	/**
	 * @var []
	 */
	private $callables = [
		ConstraintSchemaValue::class => 'newConstraintSchemaValue'
	];

	/**
	 * @since 3.1
	 *
	 * @return []
	 */
	public function getCallableKeys() {
		return array_keys( $this->callables );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $id
	 *
	 * @return callable
	 */
	public function callableFor( $id ) {
		return [ $this, $this->callables[$id] ];
	}

	/**
	 * @since 3.1
	 *
	 * @param string $value
	 *
	 * @return ConstraintSchemaValue
	 */
	public static function newConstraintSchemaValue( $typeid ) {

		$applicationFactory = ApplicationFactory::getInstance();

		return new ConstraintSchemaValue(
			$typeid,
			$applicationFactory->getPropertySpecificationLookup()
		);
	}

}
