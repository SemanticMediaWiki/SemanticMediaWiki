<?php

namespace SMW\Tests;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
trait PHPUnitCompat {

	/**
	 * @see PHPUnit_Framework_TestCase::setExpectedException
	 */
	public function setExpectedException( $name, $message = '', $code = null ) {
		if ( is_callable( [ $this, 'expectException' ] ) ) {
			if ( $name !== null ) {
				$this->expectException( $name );
			}
			if ( $message !== '' ) {
				$this->expectExceptionMessage( $message );
			}
			if ( $code !== null ) {
				$this->expectExceptionCode( $code );
			}
		} else {
			parent::setExpectedException( $name, $message, $code );
		}
	}
}