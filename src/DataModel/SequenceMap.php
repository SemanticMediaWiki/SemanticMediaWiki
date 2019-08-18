<?php

namespace SMW\DataModel;

use SMW\DIProperty;
use SMW\Services\ServicesFactory;

/**
 * Holds the annotation value input order by property.
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SequenceMap {

	/**
	 * @var []
	 */
	private static $canMap = [];

	/**
	 * @since 3.1
	 *
	 * @param boolean
	 */
	public static function canMap( DIProperty $property ) {

		$key = $property->getKey();

		if ( isset( self::$canMap[$key] ) ) {
			return self::$canMap[$key];
		}

		$schemaFactory = ServicesFactory::getInstance()->singleton( 'SchemaFactory' );
		$schemaFinder = $schemaFactory->newSchemaFinder();

		$schemaList = $schemaFinder->newSchemaList(
			$property,
			new DIProperty( '_PROFILE_SCHEMA' )
		);

		if ( $schemaList === [] ) {
			return self::$canMap[$key] = false;
		}

		$profile = $schemaList->get( 'profile' );

		return self::$canMap[$key] = $profile['sequence_map'] ?? false;
	}


}
