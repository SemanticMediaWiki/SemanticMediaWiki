<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\Message;
use SMW\Store;
use WebRequest;

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
	const SECTION_MAINTENANCE = 'section.maintenance';
	const SECTION_DEPRECATION ='section.deprecation';
	const SECTION_SUPPORT ='section.support';

	/**
	 * @var integer
	 */
	private $featureSet = 0;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var boolean
	 */
	protected $isApiTask = false;

	/**
	 * @deprecated since 3.1, use TaskHandler::hasFeature
	 * @since 2.5
	 *
	 * @param integer $feature
	 *
	 * @return boolean
	 */
	public function isEnabledFeature( $feature ) {
		return $this->hasFeature( $feature );
	}

	/**
	 * @since 3.1
	 *
	 * @param integer $feature
	 *
	 * @return boolean
	 */
	public function hasFeature( $feature ) {
		return ( ( (int)$this->featureSet & $feature ) == $feature );
	}

	/**
	 * @deprecated since 3.1, use TaskHandler::setFeatureSet
	 * @since 2.5
	 *
	 * @param integer $enabledFeatures
	 */
	public function setEnabledFeatures( $enabledFeatures ) {
		$this->setFeatureSet( $enabledFeatures );
	}

	/**
	 * @since 3.1
	 *
	 * @param integer $enabledFeatures
	 */
	public function setFeatureSet( $featureSet ) {
		$this->featureSet = $featureSet;
	}

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 */
	public function setStore( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.0
	 *
	 * @return Store
	 */
	public function getStore() {
		return $this->store;
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
	 * @return string
	 */
	public function getTask() {
		return '';
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function isApiTask() {
		return false;
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

	protected function msg( $key, $type = Message::TEXT ) {
		return Message::get( $key, $type, Message::USER_LANGUAGE );
	}

}
