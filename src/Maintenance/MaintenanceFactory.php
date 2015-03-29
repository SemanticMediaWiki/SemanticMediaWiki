<?php

namespace SMW\Maintenance;

use SMW\Store;
use SMW\ApplicationFactory;
use SMW\Store\PropertyStatisticsStore;

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
		return new DataRebuilder( $store, ApplicationFactory::getInstance()->newTitleCreator() );
	}

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 *
	 * @return ConceptCacheRebuilder
	 */
	public function newConceptCacheRebuilder( Store $store ) {
		return new ConceptCacheRebuilder( $store, ApplicationFactory::getInstance()->getSettings() );
	}

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 * @param PropertyStatisticsStore $propertyStatisticsStore
	 *
	 * @return PropertyStatisticsRebuilder
	 */
	public function newPropertyStatisticsRebuilder( Store $store, PropertyStatisticsStore $propertyStatisticsStore ) {
		return new PropertyStatisticsRebuilder( $store, $propertyStatisticsStore );
	}

}
