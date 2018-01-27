<?php

namespace SMW\MediaWiki\Specials\Admin;

use WebRequest;
use SMW\Message;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
abstract class TaskHandler {

	/**
	 * Identifies an individual section to where the task is associated with.
	 */
	const SECTION_SUPPLEMENT = 'section.supplement';
	const SECTION_SCHEMA = 'section.schema';
	const SECTION_DATAREPAIR = 'section.datarepair';
	const SECTION_DEPRECATION ='section.deprecation';
	const SECTION_SUPPORT ='section.support';

	/**
	 * @var integer
	 */
	private $enabledFeatures = 0;

	/**
	 * @since 2.5
	 *
	 * @param integer $feature
	 *
	 * @return boolean
	 */
	public function isEnabledFeature( $feature ) {
		return ( ( $this->enabledFeatures & $feature ) == $feature );
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $enabledFeatures
	 */
	public function setEnabledFeatures( $enabledFeatures ) {
		$this->enabledFeatures = $enabledFeatures;
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getSection() {
		return '';
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function hasAction() {
		return false;
	}

	/**
	 * @since 2.5
	 *
	 * @return boolean
	 */
	abstract public function isTaskFor( $task );

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	abstract public function getHtml();

	/**
	 * @since 2.5
	 *
	 * @param WebRequest $webRequest
	 */
	abstract public function handleRequest( WebRequest $webRequest );

	protected function getMessageAsString( $key, $type = Message::TEXT ) {
		return Message::get( $key, $type, Message::USER_LANGUAGE );
	}

}
