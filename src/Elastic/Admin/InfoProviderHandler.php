<?php

namespace SMW\Elastic\Admin;

use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Specials\Admin\TaskHandler;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
abstract class InfoProviderHandler extends TaskHandler {

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
	 * {@inheritDoc}
	 */
	public function hasAction() {
		return true;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function isTaskFor( $task ) {
		return $task === $this->getTask();
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
	public function getTask() {
		return $this->getParentTask() . '/' . $this->getSupplementTask();
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	abstract public function getSupplementTask();

}
