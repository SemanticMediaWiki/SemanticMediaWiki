<?php

namespace SMW\Tests\Util;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 */
class StringBuilder {

	private $string = '';

	/**
	 * @since  2.0
	 */
	public function addnewLine() {
		$this->string = $this->string . "\n";
		return $this;
	}

	/**
	 * @since  2.0
	 */
	public function addString( $string ) {
		$this->string = $this->string . $string;
		return $this;
	}

	/**
	 * @since  2.0
	 */
	public function getString() {
		return $this->string;
	}

}
