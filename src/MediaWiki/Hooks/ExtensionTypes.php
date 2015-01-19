<?php

namespace SMW\MediaWiki\Hooks;

/**
 * Called when generating the extensions credits, use this to change the tables headers
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ExtensionTypes
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ExtensionTypes {

	/**
	 * @var array
	 */
	private $extensionTypes = array();

	/**
	 * @since  2.0
	 *
	 * @param array $extensionTypes
	 */
	public function __construct( array &$extensionTypes ) {
		$this->extensionTypes =& $extensionTypes;
	}

	/**
	 * @since 2.0
	 *
	 * @return boolean
	 */
	public function process() {

		if ( !is_array( $this->extensionTypes ) ) {
			$this->extensionTypes = array();
		}

		$this->extensionTypes = array_merge(
			array( 'semantic' => wfMessage( 'version-semantic' )->text() ),
			$this->extensionTypes
		);

		return true;
	}

}
