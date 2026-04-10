<?php

namespace SMW\DataModel;

use SMW\DataItems\Property;
use SMW\Services\ServicesFactory;

/**
 * Holds the annotation value input order by property.
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class SequenceMap {

	/**
	 * @var
	 */
	private static array $canMap = [];

	/**
	 * @since 3.2
	 *
	 * @param Property $property
	 *
	 * @param boolean
	 */
	public function hasSequenceMap( Property $property ): bool {
		return self::canMap( $property );
	}

	/**
	 * @since 3.1
	 *
	 * @param boolean
	 */
	public static function canMap( Property $property ) {
		$key = $property->getKey();

		if ( isset( self::$canMap[$key] ) ) {
			return self::$canMap[$key];
		}

		$schemaFactory = ServicesFactory::getInstance()->singleton( 'SchemaFactory' );
		$schemaFinder = $schemaFactory->newSchemaFinder();

		$schemaList = $schemaFinder->newSchemaList(
			$property,
			new Property( '_PROFILE_SCHEMA' )
		);

		if ( $schemaList === null ) {
			self::$canMap[$key] = false;
			return self::$canMap[$key];
		}

		$profile = $schemaList->get( 'profile' );

		self::$canMap[$key] = $profile['sequence_map'] ?? false;
		return self::$canMap[$key];
	}

}
