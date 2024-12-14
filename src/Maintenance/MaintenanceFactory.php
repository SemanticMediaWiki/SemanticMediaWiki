<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\Store;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\MediaWiki\ManualEntryLogger;
use SMW\SQLStore\PropertyStatisticsStore;
use SMW\Localizer\LocalMessageProvider;

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
	 * @since 3.2
	 *
	 * @param string $file
	 * @param string $languageCode
	 *
	 * @return LocalMessageProvider
	 */
	public function newLocalMessageProvider( string $file, string $languageCode = 'en' ): LocalMessageProvider {
		return new LocalMessageProvider( $file, $languageCode );
	}

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 * @param Callable|null $reporterCallback
	 *
	 * @return DataRebuilder
	 */
	public function newDataRebuilder( Store $store, $reporterCallback = null ) {
		$messageReporter = $this->newMessageReporter( $reporterCallback );

		$dataRebuilder = new DataRebuilder(
			$store,
			ApplicationFactory::getInstance()->newTitleFactory()
		);

		$dataRebuilder->setMessageReporter(
			$messageReporter
		);

		return $dataRebuilder;
	}

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 * @param Callable|null $reporterCallback
	 *
	 * @return ConceptCacheRebuilder
	 */
	public function newConceptCacheRebuilder( Store $store, $reporterCallback = null ) {
		$conceptCacheRebuilder = new ConceptCacheRebuilder(
			$store,
			ApplicationFactory::getInstance()->getSettings()
		);

		$conceptCacheRebuilder->setMessageReporter(
			$this->newMessageReporter( $reporterCallback )
		);

		return $conceptCacheRebuilder;
	}

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 * @param Callable|null $reporterCallback
	 *
	 * @return PropertyStatisticsRebuilder
	 */
	public function newPropertyStatisticsRebuilder( Store $store, $reporterCallback = null ) {
		$propertyStatisticsStore = new PropertyStatisticsStore(
			$store->getConnection( 'mw.db' )
		);

		$propertyStatisticsRebuilder = new PropertyStatisticsRebuilder(
			$store,
			$propertyStatisticsStore
		);

		$propertyStatisticsRebuilder->setMessageReporter(
			$this->newMessageReporter( $reporterCallback )
		);

		return $propertyStatisticsRebuilder;
	}

	/**
	 * @since 2.4
	 *
	 * @return rebuildPropertyStatistics
	 */
	public function newRebuildPropertyStatistics() {
		return new rebuildPropertyStatistics();
	}

	/**
	 * @since 3.0
	 *
	 * @return DuplicateEntitiesDisposer
	 */
	public function newDuplicateEntitiesDisposer( Store $store, $reporterCallback = null ) {
		$duplicateEntitiesDisposer = new DuplicateEntitiesDisposer(
			$store,
			ApplicationFactory::getInstance()->getCache()
		);

		$duplicateEntitiesDisposer->setMessageReporter(
			$this->newMessageReporter( $reporterCallback )
		);

		return $duplicateEntitiesDisposer;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $performer
	 *
	 * @return MaintenanceLogger
	 */
	public function newMaintenanceLogger( $performer ) {
		$maintenanceLogger = new MaintenanceLogger( $performer, new ManualEntryLogger() );
		$maintenanceLogger->setMaxNameChars( $GLOBALS['wgMaxNameChars'] );

		return $maintenanceLogger;
	}

	/**
	 * @since 3.0
	 */
	public function newMessageReporter( $reporterCallback = null ): MessageReporter {
		$messageReporter = MessageReporterFactory::getInstance()->newObservableMessageReporter();
		$messageReporter->registerReporterCallback( $reporterCallback );

		return $messageReporter;
	}

	/**
	 * @since 3.1
	 */
	public function newAutoRecovery( string $identifier ): AutoRecovery {
		return new AutoRecovery( $identifier );
	}

}
