<?php

namespace SMW\Tests\Util;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 */
class StringBuilder {

	private $string = '';

	/**
	 * @since  1.9.3
	 */
	public function addnewLine() {
		$this->string = $this->string . "\n";
		return $this;
	}

	/**
	 * @since  1.9.3
	 */
	public function addString( $string ) {
		$this->string = $this->string . $string;
		return $this;
	}

	/**
	 * @since  1.9.3
	 */
	public function getString() {
		return $this->string;
	}

}
