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
use SMW\Store;
use SMw\ApplicationFactory;
use SMW\Utils\FileFetcher;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class TaskHandlerFactory {

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
	 * @return []
	 */
	public function getTaskHandlerList( $user, $adminFeatures ) {

		$taskHandlers = [
			// TaskHandler::SECTION_MAINTENANCE
			$this->newMaintenanceTaskHandler( $adminFeatures ),

			// TaskHandler::SECTION_DEPRECATION
			$this->newDeprecationNoticeTaskHandler(),

			// TaskHandler::SECTION_SUPPLEMENT
			$this->newSupplementTaskHandler( $adminFeatures, $user ),

			// TaskHandler::SECTION_SUPPORT
			$this->newSupportListTaskHandler()
		];

		\Hooks::run( 'SMW::Admin::TaskHandlerFactory', [ &$taskHandlers, $this->store, $this->outputFormatter, $user ] );

		$taskHandlerList = [
			TaskHandler::SECTION_MAINTENANCE => [],
			TaskHandler::SECTION_DEPRECATION => [],
			TaskHandler::SECTION_SUPPLEMENT => [],
			TaskHandler::SECTION_SUPPORT => [],
			'actions' => []
		];

		foreach ( $taskHandlers as $taskHandler ) {

			if ( !is_a( $taskHandler, 'SMW\MediaWiki\Specials\Admin\TaskHandler' ) ) {
				continue;
			}

			$taskHandler->setFeatureSet(
				$adminFeatures
			);

			$taskHandler->setStore(
				$this->store
			);

			switch ( $taskHandler->getSection() ) {
				case TaskHandler::SECTION_MAINTENANCE:
					$taskHandlerList[TaskHandler::SECTION_MAINTENANCE][] = $taskHandler;
					break;
				case TaskHandler::SECTION_DEPRECATION:
					$taskHandlerList[TaskHandler::SECTION_DEPRECATION][] = $taskHandler;
					break;
				case TaskHandler::SECTION_SUPPLEMENT:
					$taskHandlerList[TaskHandler::SECTION_SUPPLEMENT][] = $taskHandler;
					break;
				case TaskHandler::SECTION_SUPPORT:
					$taskHandlerList[TaskHandler::SECTION_SUPPORT][] = $taskHandler;
					break;
			}

			if ( $taskHandler->hasAction() ) {
				$taskHandlerList['actions'][] = $taskHandler;
			}
		}

		return $taskHandlerList;
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
	public function newSupplementTaskHandler( $adminFeatures = 0 , $user = null ) {

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

		$taskHandlers = [
			new CacheStatisticsListTaskHandler( $this->outputFormatter )
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
