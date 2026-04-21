<?php

namespace SMW\Listener\ChangeListener;

use Psr\Log\LoggerAwareTrait;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
trait CallableChangeListenerTrait {

	use LoggerAwareTrait;

	private array $changeListeners = [];

	private array $attrs = [];

	/**
	 * @since 3.2
	 */
	public function clearListeners(): void {
		$this->changeListeners = [];
		$this->attrs = [];
	}

	/**
	 * @since 3.2
	 */
	public function setAttrs( array $attrs ): void {
		$this->attrs = $attrs;
	}

	/**
	 * @since 3.2
	 */
	public function canTrigger( string $key ): bool {
		return isset( $this->changeListeners[$key] );
	}

	/**
	 * @since 3.2
	 */
	public function trigger( string $key ): void {
		if ( !isset( $this->changeListeners[$key] ) ) {
			return;
		}

		$this->logger->info(
			[ 'Listener', 'ChangeListener', "{key}" ],
			[ 'role' => 'developer', 'key' => $key ]
		);

		$changeRecord = new ChangeRecord( $this->attrs );
		$this->triggerByKey( $key, $changeRecord );
	}

	protected function triggerByKey( string $key, ChangeRecord $changeRecord ): void {
		foreach ( $this->changeListeners[$key] as $changeListener ) {

			if ( !is_callable( $changeListener ) ) {
				continue;
			}

			$changeListener( $key, $changeRecord );
		}
	}

}
