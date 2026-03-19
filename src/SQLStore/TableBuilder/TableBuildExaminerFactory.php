<?php

namespace SMW\SQLStore\TableBuilder;

use SMW\Services\ServicesFactory;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder\Examiner\CountMapField;
use SMW\SQLStore\TableBuilder\Examiner\EntityCollation;
use SMW\SQLStore\TableBuilder\Examiner\FixedProperties;
use SMW\SQLStore\TableBuilder\Examiner\HashField;
use SMW\SQLStore\TableBuilder\Examiner\IdBorder;
use SMW\SQLStore\TableBuilder\Examiner\PredefinedProperties;
use SMW\SQLStore\TableBuilder\Examiner\TouchedField;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class TableBuildExaminerFactory {

	/**
	 * @since 3.2
	 *
	 * @param SQLStore $store
	 *
	 * @return EntityCollation
	 */
	public function newEntityCollation( SQLStore $store ): EntityCollation {
		$servicesFactory = ServicesFactory::getInstance();

		$entityCollation = new EntityCollation(
			$store
		);

		$entityCollation->setSetupFile(
			$servicesFactory->singleton( 'SetupFile' )
		);

		$entityCollation->setEntityCollation(
			$servicesFactory->getSettings()->get( 'smwgEntityCollation' )
		);

		return $entityCollation;
	}

	/**
	 * @since 3.2
	 *
	 * @param SQLStore $store
	 *
	 * @return CountMapField
	 */
	public function newCountMapField( SQLStore $store ): CountMapField {
		$countMapField = new CountMapField(
			$store
		);

		$countMapField->setSetupFile(
			ServicesFactory::getInstance()->singleton( 'SetupFile' )
		);

		return $countMapField;
	}

	/**
	 * @since 3.1
	 *
	 * @param SQLStore $store
	 *
	 * @return HashField
	 */
	public function newHashField( SQLStore $store ): HashField {
		return new HashField( $store );
	}

	/**
	 * @since 3.1
	 *
	 * @param SQLStore $store
	 *
	 * @return FixedProperties
	 */
	public function newFixedProperties( SQLStore $store ): FixedProperties {
		return new FixedProperties( $store );
	}

	/**
	 * @since 3.1
	 *
	 * @param SQLStore $store
	 *
	 * @return TouchedField
	 */
	public function newTouchedField( SQLStore $store ): TouchedField {
		return new TouchedField( $store );
	}

	/**
	 * @since 3.1
	 *
	 * @param SQLStore $store
	 *
	 * @return HashField
	 */
	public function newIdBorder( SQLStore $store ): IdBorder {
		return new IdBorder( $store );
	}

	/**
	 * @since 3.1
	 *
	 * @param SQLStore $store
	 *
	 * @return PredefinedProperties
	 */
	public function newPredefinedProperties( SQLStore $store ): PredefinedProperties {
		return new PredefinedProperties( $store );
	}

}
