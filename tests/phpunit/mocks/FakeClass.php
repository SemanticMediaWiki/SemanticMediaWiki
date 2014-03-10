<?php

namespace SMW\Test;

use RuntimeException;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9.1.1
 *
 * @author mwjames
 */
class FakeClass {

	public function __call( $method, $args ) {

		if ( isset( $this->$method ) && is_callable( $this->$method ) ) {
			$func = $this->$method;
			return call_user_func_array( $func, $args );
		}

		throw new RuntimeException( "Expected a callable '{$method}' method" );
	}

}
