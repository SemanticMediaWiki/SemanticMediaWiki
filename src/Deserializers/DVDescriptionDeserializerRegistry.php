<?php

namespace SMW\Deserializers;

use SMW\Deserializers\DVDescriptionDeserializer\DescriptionDeserializer;
use SMW\Deserializers\DVDescriptionDeserializer\DispatchingDescriptionDeserializer;
use SMW\Deserializers\DVDescriptionDeserializer\MonolingualTextValueDescriptionDeserializer;
use SMW\Deserializers\DVDescriptionDeserializer\RecordValueDescriptionDeserializer;
use SMW\Deserializers\DVDescriptionDeserializer\SomeValueDescriptionDeserializer;
use SMW\Deserializers\DVDescriptionDeserializer\TimeValueDescriptionDeserializer;
use SMWDataValue as DataValue;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class DVDescriptionDeserializerRegistry {

	/**
	 * @var DVDescriptionDeserializerRegistry
	 */
	private static $instance = null;

	/**
	 * @var DispatchingDescriptionDeserializer
	 */
	private $dispatchingDescriptionDeserializer = null;

	/**
	 * @since 2.3
	 *
	 * @param DispatchingDescriptionDeserializer|null $dispatchingDescriptionDeserializer
	 */
	public function __construct( DispatchingDescriptionDeserializer $dispatchingDescriptionDeserializer = null ) {
		$this->dispatchingDescriptionDeserializer = $dispatchingDescriptionDeserializer;
	}

	/**
	 * @since 2.3
	 *
	 * @return self
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @since 2.3
	 */
	public static function clear() {
		self::$instance = null;
	}

	/**
	 * @note This allows extensions to inject their own DescriptionDeserializer
	 * without further violating SRP of the DataType or DataValue.
	 *
	 * @since 2.3
	 *
	 * @param DescriptionDeserializer $descriptionDeserializer
	 */
	public function registerDescriptionDeserializer( DescriptionDeserializer $descriptionDeserializer ) {

		if ( $this->dispatchingDescriptionDeserializer === null ) {
			$this->dispatchingDescriptionDeserializer = $this->newDispatchingDescriptionDeserializer();
		}

		$this->dispatchingDescriptionDeserializer->addDescriptionDeserializer( $descriptionDeserializer );
	}

	/**
	 * @since 2.3
	 *
	 * @param DataValue $dataValue
	 *
	 * @return DescriptionDeserializer
	 */
	public function getDescriptionDeserializerFor( DataValue $dataValue ) {

		if ( $this->dispatchingDescriptionDeserializer === null ) {
			$this->dispatchingDescriptionDeserializer = $this->newDispatchingDescriptionDeserializer();
		}

		return $this->dispatchingDescriptionDeserializer->getDescriptionDeserializerFor( $dataValue );
	}

	private function newDispatchingDescriptionDeserializer() {

		$dispatchingDescriptionDeserializer = new DispatchingDescriptionDeserializer();
		$dispatchingDescriptionDeserializer->addDescriptionDeserializer( new TimeValueDescriptionDeserializer() );
		$dispatchingDescriptionDeserializer->addDescriptionDeserializer( new RecordValueDescriptionDeserializer() );
		$dispatchingDescriptionDeserializer->addDescriptionDeserializer( new MonolingualTextValueDescriptionDeserializer() );

		$dispatchingDescriptionDeserializer->addDefaultDescriptionDeserializer( new SomeValueDescriptionDeserializer() );

		return $dispatchingDescriptionDeserializer;
	}

}
