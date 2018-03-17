<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\Store;

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
			// TaskHandler::SECTION_SCHEMA
			$this->newTableSchemaTaskHandler(),

			// TaskHandler::SECTION_DATAREPAIR
			$this->newDataRefreshJobTaskHandler(),
			$this->newDisposeJobTaskHandler(),
			$this->newPropertyStatsRebuildJobTaskHandler(),
			$this->newFulltextSearchTableRebuildJobTaskHandler(),

			// TaskHandler::SECTION_DEPRECATION
			$this->newDeprecationNoticeTaskHandler(),

			// TaskHandler::SECTION_SUPPLEMENT
			$this->newConfigurationListTaskHandler(),
			$this->newOperationalStatisticsListTaskHandler(),
			$this->newDuplicateLookupTaskHandler(),
			$this->newEntityLookupTaskHandler( $user ),

			// TaskHandler::SECTION_SUPPORT
			$this->newSupportListTaskHandler()
		];

		\Hooks::run( 'SMW::Admin::TaskHandlerFactory', [ &$taskHandlers, $this->store, $this->outputFormatter, $user ] );

		$taskHandlerList = [
			TaskHandler::SECTION_SCHEMA => [],
			TaskHandler::SECTION_DATAREPAIR => [],
			TaskHandler::SECTION_DEPRECATION => [],
			TaskHandler::SECTION_SUPPLEMENT => [],
			TaskHandler::SECTION_SUPPORT => [],
			'actions' => []
		];

		foreach ( $taskHandlers as $taskHandler ) {

			if ( !is_a( $taskHandler, 'SMW\MediaWiki\Specials\Admin\TaskHandler' ) ) {
				continue;
			}

			$taskHandler->setEnabledFeatures(
				$adminFeatures
			);

			$taskHandler->setStore(
				$this->store
			);

			switch ( $taskHandler->getSection() ) {
				case TaskHandler::SECTION_SCHEMA:
					$taskHandlerList[TaskHandler::SECTION_SCHEMA][] = $taskHandler;
					break;
				case TaskHandler::SECTION_DATAREPAIR:
					$taskHandlerList[TaskHandler::SECTION_DATAREPAIR][] = $taskHandler;
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
