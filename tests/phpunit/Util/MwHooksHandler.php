<?php

namespace SMW\Tests\Util;

/**
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class MwHooksHandler {

	private $wgHooks = array();

	private $listOfHooks = array(
		'SMWStore::updateDataBefore',
		'smwInitProperties',
		'SMW::SQLStore::updatePropertyTableDefinitions'
	);

	/**
	 * @since  2.0
	 */
	public function deregisterListedHooks() {

		foreach ( $this->listOfHooks as $hook ) {

			if ( !isset( $GLOBALS['wgHooks'][ $hook ] ) ) {
				continue;
			}

			$this->wgHooks[ $hook ] = $GLOBALS['wgHooks'][ $hook ];
			$GLOBALS['wgHooks'][ $hook ] = array();
		}
	}

	/**
	 * @since  2.0
	 */
	public function restoreListedHooks() {

		foreach ( $this->listOfHooks as $hook ) {

			if ( !isset( $this->wgHooks[ $hook ] ) ) {
				continue;
			}

			$GLOBALS['wgHooks'][ $hook ] = $this->wgHooks[ $hook ];
			unset( $this->wgHooks[ $hook ] );
		}
	}

}
