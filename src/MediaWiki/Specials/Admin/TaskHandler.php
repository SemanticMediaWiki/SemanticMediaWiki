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
		return ( $this->enabledFeatures & $feature ) != 0;
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
