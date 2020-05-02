<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\MediaWiki\Specials\Admin\Maintenance\DataRefreshJobTaskHandler;
use SMW\MediaWiki\Specials\Admin\Maintenance\DisposeJobTaskHandler;
use SMW\MediaWiki\Specials\Admin\Maintenance\FulltextSearchTableRebuildJobTaskHandler;
use SMW\MediaWiki\Specials\Admin\Maintenance\PropertyStatsRebuildJobTaskHandler;
use SMW\MediaWiki\Specials\Admin\Maintenance\TableSchemaTaskHandler;
use SMW\MediaWiki\Specials\Admin\Supplement\CacheStatisticsListTaskHandler;
use SMW\MediaWiki\Specials\Admin\Supplement\ConfigurationListTaskHandler;
use SMW\MediaWiki\Specials\Admin\Supplement\DuplicateLookupTaskHandler;
use SMW\MediaWiki\Specials\Admin\Supplement\EntityLookupTaskHandler;
use SMW\MediaWiki\Specials\Admin\Supplement\OperationalStatisticsListTaskHandler;
use SMW\MediaWiki\Specials\Admin\Supplement\TableStatisticsTaskHandler;
use SMW\MediaWiki\Specials\Admin\Alerts\DeprecationNoticeTaskHandler;
use SMW\MediaWiki\Specials\Admin\Alerts\MaintenanceAlertsTaskHandler;
use SMW\MediaWiki\Specials\Admin\Alerts\LastOptimizationRunMaintenanceAlertTaskHandler;
use SMW\MediaWiki\Specials\Admin\Alerts\OutdatedEntitiesMaxCountThresholdMaintenanceAlertTaskHandler;
use SMW\MediaWiki\Specials\Admin\Alerts\ByNamespaceInvalidEntitiesMaintenanceAlertTaskHandler;
use SMW\MediaWiki\HookDispatcherAwareTrait;
use SMW\Store;
use SMW\SetupFile;
use SMw\ApplicationFactory;
use SMW\Utils\FileFetcher;
use User;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class TaskHandlerFactory {

	use HookDispatcherAwareTrait;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var HtmlFormRenderer
	 */
	private $htmlFormRenderer;

	/**
	 * @var OutputFormatter
	 */
	private $outputFormatter;

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param HtmlFormRenderer $htmlFormRenderer
	 * @param OutputFormatter $outputFormatter
	 */
	public function __construct( Store $store, HtmlFormRenderer $htmlFormRenderer, OutputFormatter $outputFormatter ) {
		$this->store = $store;
		$this->htmlFormRenderer = $htmlFormRenderer;
		$this->outputFormatter = $outputFormatter;
	}

	/**
	 * @since 2.5
	 *
	 * @param User $user
	 * @param int $adminFeatures
	 *
	 * @return TaskHandlerRegistry
	 */
	public function newTaskHandlerRegistry( User $user, int $adminFeatures ) {

		$taskHandlerRegistry = new TaskHandlerRegistry(
			$this->store,
			$this->outputFormatter
		);

		$taskHandlerRegistry->setHookDispatcher(
			$this->hookDispatcher
		);

		$taskHandlerRegistry->setFeatureSet(
			$adminFeatures
		);

		$taskHandlerRegistry->registerTaskHandlers(
			[
				$this->newMaintenanceTaskHandler( $adminFeatures ),
				$this->newAlertsTaskHandler( $adminFeatures ),
				$this->newSupplementTaskHandler( $adminFeatures, $user ),
				$this->newSupportListTaskHandler()
			],
			$user
		);

		return $taskHandlerRegistry;
	}

	/**
	 * @since 2.5
	 *
	 * @return TableSchemaTaskHandler
	 */
	public function newTableSchemaTaskHandler() {
		return new TableSchemaTaskHandler( $this->store, $this->htmlFormRenderer, $this->outputFormatter );
	}

	/**
	 * @since 2.5
	 *
	 * @return SupportListTaskHandler
	 */
	public function newSupportListTaskHandler() {
		return new SupportListTaskHandler( $this->htmlFormRenderer );
	}

	/**
	 * @since 3.1
	 *
	 * @param integer $adminFeatures
	 * @param User|null $user
	 *
	 * @return SupplementTaskHandler
	 */
	public function newSupplementTaskHandler( $adminFeatures = 0, $user = null ) {

		$settings = ApplicationFactory::getInstance()->getSettings();

		$taskHandlers = [
			$this->newConfigurationListTaskHandler(),
			$this->newOperationalStatisticsListTaskHandler(),
			$this->newDuplicateLookupTaskHandler(),
			$this->newEntityLookupTaskHandler( $user ),
		];

		foreach ( $taskHandlers as $taskHandler ) {
			$taskHandler->setFeatureSet( $adminFeatures );
		}

		$supplementTaskHandler = new SupplementTaskHandler(
			$this->outputFormatter,
			$taskHandlers
		);

		return $supplementTaskHandler;
	}

	/**
	 * @since 2.5
	 *
	 * @return ConfigurationListTaskHandler
	 */
	public function newConfigurationListTaskHandler() {
		return new ConfigurationListTaskHandler( $this->outputFormatter );
	}

	/**
	 * @since 2.5
	 *
	 * @return OperationalStatisticsListTaskHandler
	 */
	public function newOperationalStatisticsListTaskHandler() {

		$entityCache = ApplicationFactory::getInstance()->getEntityCache();

		$taskHandlers = [
			new CacheStatisticsListTaskHandler( $this->outputFormatter ),
			new TableStatisticsTaskHandler( $this->outputFormatter, $entityCache )
		];

		return new OperationalStatisticsListTaskHandler( $this->outputFormatter, $taskHandlers );
	}

	/**
	 * @since 3.1
	 *
	 * @param integer $adminFeatures
	 *
	 * @return MaintenanceTaskHandler
	 */
	public function newMaintenanceTaskHandler( $adminFeatures = 0 ) {

		$settings = ApplicationFactory::getInstance()->getSettings();

		$taskHandlers = [
			$this->newTableSchemaTaskHandler(),
			$this->newDataRefreshJobTaskHandler(),
			$this->newDisposeJobTaskHandler(),
			$this->newPropertyStatsRebuildJobTaskHandler(),
			$this->newFulltextSearchTableRebuildJobTaskHandler()
		];

		foreach ( $taskHandlers as $taskHandler ) {
			$taskHandler->setFeatureSet( $adminFeatures );
		}

		$maintenanceTaskHandler = new MaintenanceTaskHandler(
			$this->outputFormatter,
			new FileFetcher( $settings->get( 'smwgMaintenanceDir' ) ),
			$taskHandlers
		);

		return $maintenanceTaskHandler;
	}

	/**
	 * @since 2.5
	 *
	 * @return EntityLookupTaskHandler
	 */
	public function newEntityLookupTaskHandler( $user = null ) {

		$entityLookupTaskHandler = new EntityLookupTaskHandler(
			$this->store,
			$this->htmlFormRenderer,
			$this->outputFormatter
		);

		$entityLookupTaskHandler->setUser(
			$user
		);

		return $entityLookupTaskHandler;
	}

	/**
	 * @since 2.5
	 *
	 * @return DataRefreshJobTaskHandler
	 */
	public function newDataRefreshJobTaskHandler() {
		return new DataRefreshJobTaskHandler( $this->htmlFormRenderer, $this->outputFormatter );
	}

	/**
	 * @since 2.5
	 *
	 * @return DisposeJobTaskHandler
	 */
	public function newDisposeJobTaskHandler() {
		return new DisposeJobTaskHandler( $this->htmlFormRenderer, $this->outputFormatter );
	}

	/**
	 * @since 2.5
	 *
	 * @return PropertyStatsRebuildJobTaskHandler
	 */
	public function newPropertyStatsRebuildJobTaskHandler() {
		return new PropertyStatsRebuildJobTaskHandler( $this->htmlFormRenderer, $this->outputFormatter );
	}

	/**
	 * @since 2.5
	 *
	 * @return FulltextSearchTableRebuildJobTaskHandler
	 */
	public function newFulltextSearchTableRebuildJobTaskHandler() {
		return new FulltextSearchTableRebuildJobTaskHandler( $this->htmlFormRenderer, $this->outputFormatter );
	}

	/**
	 * @since 3.2
	 *
	 * @param integer $adminFeatures
	 *
	 * @return AlertsTaskHandler
	 */
	public function newAlertsTaskHandler( $adminFeatures = 0 ) {

		$settings = ApplicationFactory::getInstance()->getSettings();

		$byNamespaceInvalidEntitiesMaintenanceAlertTaskHandler = new ByNamespaceInvalidEntitiesMaintenanceAlertTaskHandler(
			$this->store
		);

		$byNamespaceInvalidEntitiesMaintenanceAlertTaskHandler->setNamespacesWithSemanticLinks(
			$settings->get( 'smwgNamespacesWithSemanticLinks' )
		);

		$maintenanceAlertsTaskHandlers = [
			new LastOptimizationRunMaintenanceAlertTaskHandler(
				new SetupFile()
			),
			new OutdatedEntitiesMaxCountThresholdMaintenanceAlertTaskHandler(
				$this->store
			),
			$byNamespaceInvalidEntitiesMaintenanceAlertTaskHandler
		];

		$maintenanceAlertsTaskHandler = new MaintenanceAlertsTaskHandler(
			$maintenanceAlertsTaskHandlers
		);

		$maintenanceAlertsTaskHandler->setFeatureSet(
			$adminFeatures
		);

		$taskHandlers = [
			$this->newDeprecationNoticeTaskHandler(),
			$maintenanceAlertsTaskHandler
		];

		return new AlertsTaskHandler( $this->outputFormatter, $taskHandlers );
	}

	/**
	 * @since 3.0
	 *
	 * @return DeprecationNoticeTaskHandler
	 */
	public function newDeprecationNoticeTaskHandler() {
		return new DeprecationNoticeTaskHandler( $this->outputFormatter, $GLOBALS['smwgDeprecationNotices'] );
	}

	/**
	 * @since 3.0
	 *
	 * @return DuplicateLookupTaskHandler
	 */
	public function newDuplicateLookupTaskHandler() {
		return new DuplicateLookupTaskHandler( $this->outputFormatter );
	}

}
