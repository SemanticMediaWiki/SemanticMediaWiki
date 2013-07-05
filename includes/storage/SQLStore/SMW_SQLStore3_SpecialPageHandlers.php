<?php
/**
 * @file
 * @ingroup SMWStore
 * @since 1.8
 */

/**
 * Class Handling all the Special Page methods for SMWSQLStore3
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author Nischay Nahata
 *
 * @since 1.8
 * @ingroup SMWStore
 */
class SMWSQLStore3SpecialPageHandlers {

	/**
	 * The store used by this specialPageHandler
	 *
	 * @since 1.8
	 * @var SMWSQLStore3
	 */
	protected $store;


	public function __construct( SMWSQLStore3 $parentstore ) {
		$this->store = $parentstore;
	}

	/**
	 * Implementation of SMWStore::getPropertiesSpecial(). It works by
	 * querying for all properties in the SMW IDs table (based on their
	 * namespace) and getting their usage from the property statistics
	 * table. When asking for unused properties, the result does not
	 * include the usage count (which is always 0 then).
	 *
	 * @bug Properties that are used as super properties of others are reported as unused now.
	 *
	 * FIXME: this method is doing to uch things. Getting unused properties and getting usage counts
	 * for all properties are two different tasks.
	 *
	 * @see SMWStore::getPropertiesSpecial()
	 * @see SMWStore::getUnusedPropertiesSpecial()
	 * @since 1.8
	 * @param SMWRequestOptions $requestoptions
	 * @param boolean $unusedProperties
	 * @return array
	 */
	public function getPropertiesSpecial( SMWRequestOptions $requestoptions = null ) {
		return \SMW\SQLStore\PropertiesCollector::newFromStore( $this->store )->setRequestOptions( $requestoptions );
	}

	/**
	 * Implementation of SMWStore::getUnusedPropertiesSpecial(). It works by
	 * calling getPropertiesSpecial() with additional parameters.
	 *
	 * @see SMWStore::getUnusedPropertiesSpecial()
	 *
	 * @since 1.8
	 *
	 * @param SMWRequestOptions $requestoptions
	 *
	 * @return Collector
	 */
	public function getUnusedPropertiesSpecial( SMWRequestOptions $requestoptions = null ) {
		return \SMW\SQLStore\UnusedPropertiesCollector::newFromStore( $this->store )->setRequestOptions( $requestoptions );
	}

	/**
	 * Implementation of SMWStore::getWantedPropertiesSpecial(). Like all
	 * WantedFoo specials, this function is very resource intensive and needs
	 * to be cached on medium/large wikis.
	 *
	 * @param SMWRequestOptions $requestoptions
	 *
	 * @return Collector
	 */
	public function getWantedPropertiesSpecial( $requestoptions = null ) {
		return \SMW\SQLStore\WantedPropertiesCollector::newFromStore( $this->store )->setRequestOptions( $requestoptions );
	}

	/**
	 * @see SMWStore::getStatistics
	 * @see StatisticsCollector:getResults
	 *
	 * @return array
	 */
	public function getStatistics() {
		// Until the settings object is invoke during Store setup, use the factory method here
		return \SMW\SQLStore\StatisticsCollector::newFromStore( $this->store )->getResults();
	}

}
