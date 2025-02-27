<?php

namespace SMW\Utils;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class Flag {

	/**
	 * @var int
	 */
	private $flag = 0;

	/**
	 * @since 3.2
	 *
	 * @param int $flag
	 */
	public function __construct( int $flag ) {
		$this->flag = $flag;
	}

	/**
	 * @since 3.2
	 *
	 * @param int $flag
	 *
	 * @return bool
	 */
	public function is( $flag ): bool {
		return ( ( (int)$this->flag & $flag ) == $flag );
	}

	/**
	 * @since 3.2
	 *
	 * @param int $flag
	 *
	 * @return bool
	 */
	public function not( $flag ): bool {
		return !$this->is( $flag );
	}

}
