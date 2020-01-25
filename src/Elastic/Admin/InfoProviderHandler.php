<?php

namespace SMW\Elastic\Admin;

use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\MediaWiki\Specials\Admin\ActionableTask;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
abstract class InfoProviderHandler extends TaskHandler implements ActionableTask {

	// ElasticsClientInfoTaskHandler

	/**
	 * @var OutputFormatter
	 */
	protected $outputFormatter;

	/**
	 * @since 3.0
	 *
	 * @param OutputFormatter $outputFormatter
	 */
	public function __construct( OutputFormatter $outputFormatter ) {
		$this->outputFormatter = $outputFormatter;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getSection() {
		return self::SECTION_SUPPLEMENT;
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getTask() : string {
		return $this->getParentTask() . '/' . $this->getSupplementTask();
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function isTaskFor( string $action ) : bool {
		return $action === $this->getTask();
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getParentTask() {
		return 'elastic';
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	abstract public function getSupplementTask();

}
