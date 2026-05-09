<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\Localizer\MessageLocalizerTrait;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
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
	const SECTION_DEPRECATION = 'section/deprecation';
	const SECTION_ALERTS = 'section/alerts';
	const SECTION_SUPPORT = 'section/support';
	const ACTIONABLE = 'actionable';

	protected int $featureSet = 0;

	private ?Store $store = null;

	protected bool $isApiTask = false;

	/**
	 * @deprecated since 3.1, use TaskHandler::hasFeature
	 * @since 2.5
	 */
	public function isEnabledFeature( int $feature ): bool {
		return $this->hasFeature( $feature );
	}

	/**
	 * @since 3.1
	 */
	public function hasFeature( int $feature ): bool {
		return ( ( $this->featureSet & $feature ) == $feature );
	}

	/**
	 * @deprecated since 3.1, use TaskHandler::setFeatureSet
	 * @since 2.5
	 */
	public function setEnabledFeatures( int $enabledFeatures ): void {
		$this->setFeatureSet( $enabledFeatures );
	}

	/**
	 * @since 3.1
	 */
	public function setFeatureSet( int $featureSet ): void {
		$this->featureSet = $featureSet;
	}

	/**
	 * @since 3.0
	 */
	public function setStore( Store $store ): void {
		$this->store = $store;
	}

	/**
	 * @since 3.0
	 */
	public function getStore(): ?Store {
		return $this->store;
	}

	/**
	 * @since 3.0
	 */
	public function getSection(): string {
		return '';
	}

	/**
	 * @since 3.2
	 */
	public function getName(): string {
		return '';
	}

	/**
	 * @since 3.0
	 */
	public function isApiTask(): bool {
		return false;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	abstract public function getHtml();

}
