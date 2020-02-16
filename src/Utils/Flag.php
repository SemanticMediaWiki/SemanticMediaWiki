<?php

namespace SMW\Utils;

/**
 * @license GNU GPL v2+
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
	 * @param integer $flag
	 *
	 * @return boolean
	 */
	public function is( $flag ) : bool {
		return ( ( (int)$this->flag & $flag ) == $flag );
	}

	/**
	 * @since 3.2
	 *
	 * @param integer $flag
	 *
	 * @return boolean
	 */
	public function not( $flag ) : bool {
		return !$this->is( $flag );
	}

}
