<?php

namespace SMW\Maintenance;

use SMW\Store;
use SMW\ApplicationFactory;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class MaintenanceFactory {

	/**
	 * @since 2.2
	 *
	 * @return MaintenanceHelper
	 */
	public function newMaintenanceHelper() {
		return new MaintenanceHelper();
	}

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 *
	 * @return DataRebuilder
	 */
	public function newDataRebuilder( Store $store ) {

		$titleCreator = ApplicationFactory::getInstance()->newTitleCreator();

		return new DataRebuilder( $store, $titleCreator );
	}

}
