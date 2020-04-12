<?php

namespace SMW\SQLStore\TableBuilder;

use SMW\Services\ServicesFactory;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder\Examiner\HashField;
use SMW\SQLStore\TableBuilder\Examiner\FixedProperties;
use SMW\SQLStore\TableBuilder\Examiner\TouchedField;
use SMW\SQLStore\TableBuilder\Examiner\IdBorder;
use SMW\SQLStore\TableBuilder\Examiner\PredefinedProperties;
use SMW\SQLStore\TableBuilder\Examiner\CountMapField;
use SMW\SQLStore\TableBuilder\Examiner\EntityCollation;

/**
 * @private
 *
 * @license GNU GPL v2+
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
	public function newEntityCollation( SQLStore $store ) : EntityCollation {

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
	public function newCountMapField( SQLStore $store ) : CountMapField {

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
	public function newHashField( SQLStore $store ) {
		return new HashField( $store );
	}

	/**
	 * @since 3.1
	 *
	 * @param SQLStore $store
	 *
	 * @return FixedProperties
	 */
	public function newFixedProperties( SQLStore $store ) {
		return new FixedProperties( $store );
	}

	/**
	 * @since 3.1
	 *
	 * @param SQLStore $store
	 *
	 * @return TouchedField
	 */
	public function newTouchedField( SQLStore $store ) {
		return new TouchedField( $store );
	}

	/**
	 * @since 3.1
	 *
	 * @param SQLStore $store
	 *
	 * @return HashField
	 */
	public function newIdBorder( SQLStore $store ) {
		return new IdBorder( $store );
	}

	/**
	 * @since 3.1
	 *
	 * @param SQLStore $store
	 *
	 * @return PredefinedProperties
	 */
	public function newPredefinedProperties( SQLStore $store ) {
		return new PredefinedProperties( $store );
	}

}
