<?php

namespace SMW\Listener\ChangeListener\ChangeListeners;

use SMW\Listener\ChangeListener\ChangeListener;
use SMW\Listener\ChangeListener\CallableChangeListenerTrait;
use SMW\Listener\ChangeListener\ChangeRecord;
use SMW\Store;
use SMW\DIProperty;
use SMW\Exception\PropertyLabelNotResolvedException;
use SMW\MediaWiki\HookDispatcherAwareTrait;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class PropertyChangeListener implements ChangeListener {

	use CallableChangeListenerTrait;
	use HookDispatcherAwareTrait;

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
	 * @var bool
	 */
	private $initListeners = false;

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
	 */
	public function loadListeners() {

		if ( $this->initListeners === true ) {
			return;
		}

		$this->initListeners = true;
		$this->hookDispatcher->onRegisterPropertyChangeListeners( $this );
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
	 *
	 * @throws RuntimeException
	 */
	public function recordChange( int $pid, array $record ) {

		if ( $this->initListeners === false ) {
			throw new RuntimeException(
				"Hook wasn't run, possible listeners weren't registered from the available hook!"
			);
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
	public function triggerChangeListeners() {
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
	 * @since 3.2
	 */
	public function runChangeListeners() {
		$this->store->getConnection( 'mw.db' )->onTransactionIdle( [ $this, 'triggerChangeListeners' ] );
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
