<?php

namespace SMW\SQLStore\TableBuilder;

use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder\Examiner\HashField;
use SMW\SQLStore\TableBuilder\Examiner\FixedProperties;
use SMW\SQLStore\TableBuilder\Examiner\TouchedField;
use SMW\SQLStore\TableBuilder\Examiner\IdBorder;
use SMW\SQLStore\TableBuilder\Examiner\PredefinedProperties;

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
	 * @since 3.1
	 *
	 * @return HashField
	 */
	public function newHashField( SQLStore $store ) {
		return new HashField( $store );
	}

	/**
	 * @since 3.1
	 *
	 * @return FixedProperties
	 */
	public function newFixedProperties( SQLStore $store ) {
		return new FixedProperties( $store );
	}

	/**
	 * @since 3.1
	 *
	 * @return TouchedField
	 */
	public function newTouchedField( SQLStore $store ) {
		return new TouchedField( $store );
	}

	/**
	 * @since 3.1
	 *
	 * @return HashField
	 */
	public function newIdBorder( SQLStore $store ) {
		return new IdBorder( $store );
	}

	/**
	 * @since 3.1
	 *
	 * @return PredefinedProperties
	 */
	public function newPredefinedProperties( SQLStore $store ) {
		return new PredefinedProperties( $store );
	}

}
