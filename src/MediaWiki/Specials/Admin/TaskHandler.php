<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\Message;
use SMW\Store;
use WebRequest;
use SMW\Localizer\MessageLocalizerTrait;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
abstract class TaskHandler {

	use MessageLocalizerTrait;

	/**
	 * Identifies an individual section to where the task is associated with.
	 */
	const SECTION_SUPPLEMENT = 'section/supplement';
	const SECTION_SCHEMA = 'section/schema';
	const SECTION_MAINTENANCE = 'section/maintenance';
	const SECTION_DEPRECATION ='section/deprecation';
	const SECTION_ALERTS ='section/alerts';
	const SECTION_SUPPORT ='section/support';
	const ACTIONABLE ='actionable';

	/**
	 * @var integer
	 */
	protected $featureSet = 0;

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
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getName() : string {
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
	 * @since 2.5
	 *
	 * @return string
	 */
	abstract public function getHtml();

}
