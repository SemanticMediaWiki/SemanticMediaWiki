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
		return new OperationalStatisticsListTaskHandler( $this->outputFormatter );
	}

	/**
	 * @since 2.5
	 *
	 * @return IdTaskHandler
	 */
	public function newIdTaskHandler() {
		return new IdTaskHandler( $this->store, $this->htmlFormRenderer, $this->outputFormatter );
	}

	/**
	 * @since 2.5
	 *
	 * @return DataRefreshJobTaskHandler
	 */
	public function newDataRefreshJobTaskHandler() {
		return new DataRefreshJobTaskHandler( $this->store, $this->htmlFormRenderer, $this->outputFormatter );
	}

	/**
	 * @since 2.5
	 *
	 * @return DisposeJobTaskHandler
	 */
	public function newDisposeJobTaskHandler() {
		return new DisposeJobTaskHandler( $this->store, $this->htmlFormRenderer, $this->outputFormatter );
	}

	/**
	 * @since 2.5
	 *
	 * @return PropertyStatsRebuildJobTaskHandler
	 */
	public function newPropertyStatsRebuildJobTaskHandler() {
		return new PropertyStatsRebuildJobTaskHandler( $this->store, $this->htmlFormRenderer, $this->outputFormatter );
	}

	/**
	 * @since 2.5
	 *
	 * @return FulltextSearchTableRebuildJobTaskHandler
	 */
	public function newFulltextSearchTableRebuildJobTaskHandler() {
		return new FulltextSearchTableRebuildJobTaskHandler( $this->store, $this->htmlFormRenderer, $this->outputFormatter );
	}

	/**
	 * @since 3.0
	 *
	 * @return DeprecationNoticeTaskHandler
	 */
	public function newDeprecationNoticeTaskHandler() {
		return new DeprecationNoticeTaskHandler( $this->outputFormatter, $GLOBALS['smwgDeprecationNotices'] );
	}

}
