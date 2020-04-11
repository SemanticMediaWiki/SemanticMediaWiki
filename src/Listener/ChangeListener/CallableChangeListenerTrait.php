<?php

namespace SMW\Listener\ChangeListener;

use Psr\Log\LoggerAwareTrait;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
trait CallableChangeListenerTrait {

	use LoggerAwareTrait;

	/**
	 * @var []
	 */
	private $changeListeners = [];

	/**
	 * @var []
	 */
	private $attrs = [];

	/**
	 * @since 3.2
	 */
	public function clearListeners() {
		$this->changeListeners = [];
		$this->attrs = [];
	}

	/**
	 * @since 3.2
	 *
	 * @param array $attrs
	 */
	public function setAttrs( array $attrs ) {
		$this->attrs = $attrs;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function canTrigger( string $key ) : bool {
		return isset( $this->changeListeners[$key] );
	}

	/**
	 * @since 3.2
	 *
	 * @param string $key
	 */
	public function trigger( string $key ) {

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

	protected function triggerByKey( string $key, ChangeRecord $changeRecord ) {
		foreach ( $this->changeListeners[$key] as $changeListener ) {

			if ( !is_callable( $changeListener ) ) {
				continue;
			}

			$changeListener( $key, $changeRecord );
		}
	}

}