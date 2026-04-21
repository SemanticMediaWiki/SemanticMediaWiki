<?php

namespace SMW\Listener\ChangeListener\ChangeListeners;

use RuntimeException;
use SMW\DataItems\Property;
use SMW\Listener\ChangeListener\CallableChangeListenerTrait;
use SMW\Listener\ChangeListener\ChangeListener;
use SMW\Listener\ChangeListener\ChangeRecord;
use SMW\MediaWiki\HookDispatcherAwareTrait;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class PropertyChangeListener implements ChangeListener {

	use CallableChangeListenerTrait;
	use HookDispatcherAwareTrait;

	private array $propertyIdKeyMap = [];

	private array $changes = [];

	private bool $initListeners = false;

	/**
	 * @since 3.2
	 */
	public function __construct( private Store $store ) {
	}

	/**
	 * @since 3.2
	 */
	public function loadListeners(): void {
		if ( $this->initListeners ) {
			return;
		}

		$this->initListeners = true;
		$this->hookDispatcher->onRegisterPropertyChangeListeners( $this );
	}

	/**
	 * @since 3.2
	 */
	public function addListenerCallback( Property $property, callable $callback ): void {
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
	 * @throws RuntimeException
	 */
	public function recordChange( int $pid, array $record ): void {
		if ( !$this->initListeners ) {
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
	public function triggerChangeListeners(): void {
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
	public function runChangeListeners(): void {
		$this->store->getConnection( 'mw.db' )->onTransactionCommitOrIdle( [ $this, 'triggerChangeListeners' ] );
	}

	/**
	 * @see CallableChangeListenerTrait::triggerByKey
	 */
	protected function triggerByKey( string $key, ChangeRecord $changeRecord ): void {
		$property = new Property( $key );

		foreach ( $this->changeListeners[$key] as $changeListener ) {
			$changeListener( $property, $changeRecord );
		}
	}

}
