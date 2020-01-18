<?php

namespace SMW\Listener\ChangeListener\ChangeListeners;

use SMW\Listener\ChangeListener\ChangeListener;
use SMW\Listener\ChangeListener\CallableChangeListenerTrait;
use SMW\Listener\ChangeListener\ChangeRecord;
use SMW\Store;
use SMW\DIProperty;
use SMW\Exception\PropertyLabelNotResolvedException;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class PropertyChangeListener implements ChangeListener {

	use CallableChangeListenerTrait;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var []
	 */
	private $propertyIdKeyMap = [];

	/**
	 * @var []
	 */
	private $changes = [];

	/**
	 * @var boolean|null
	 */
	private $initListeners;

	/**
	 * @since 3.2
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.2
	 *
	 * @param DIProperty $property
	 * @param callable $callback
	 */
	public function addListenerCallback( DIProperty $property, callable $callback ) {

		$key = $property->getKey();

		$pid = $this->store->getObjectIds()->getSMWPropertyID(
			$property
		);

		$this->propertyIdKeyMap[$pid] = $key;

		if ( !isset( $this->changeListeners[$key] ) ) {
			$this->changeListeners[$key] = [];
		}

		$this->changeListeners[$key][] = $callback;
	}

	/**
	 * @since 3.2
	 *
	 * @param integer $pid
	 * @param array $record
	 */
	public function recordChange( int $pid, array $record ) {

		if ( $this->initListeners === null ) {
			$this->initListeners = \Hooks::run( 'SMW::Listener::ChangeListener::RegisterPropertyChangeListeners', [ $this ] );
		}

		// Don't record anything when there is no listener that can be triggered!
		if ( !isset( $this->propertyIdKeyMap[$pid] ) ) {
			return;
		}

		if ( !isset( $this->changes[$pid] ) ) {
			$this->changes[$pid] = [];
		}

		$this->changes[$pid][] = $record;
	}

	/**
	 * @since 3.2
	 */
	public function matchAndTriggerChangeListeners() {

		$keyIdMap = array_flip( $this->propertyIdKeyMap );

		foreach ( $this->changeListeners as $key => $changeListeners ) {

			$pid = $keyIdMap[$key] ?? null;

			if ( !isset( $this->changes[$pid] ) ) {
				continue;
			}

			$attrs = [];

			foreach ( $this->changes[$pid] as $change ) {
				$attrs[] = new ChangeRecord( $change );
			}

			$this->setAttrs( $attrs );
			$this->trigger( $key );
		}
	}

	/**
	 * @see CallableChangeListenerTrait::triggerByKey
	 */
	protected function triggerByKey( string $key, ChangeRecord $changeRecord ) {

		$property = new DIProperty( $key );

		foreach ( $this->changeListeners[$key] as $changeListener ) {
			$changeListener( $property, $changeRecord );
		}
	}

}
