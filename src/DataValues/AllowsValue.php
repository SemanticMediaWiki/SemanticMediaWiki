<?php

namespace SMW\DataValues;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class AllowsValue extends StringValue {

	/**
	 * DV identifier
	 */
	const TYPE_ID = '__pval';

	/**
	 * @param string $typeid
	 */
	public function __construct( $typeid = '' ) {
		parent::__construct( self::TYPE_ID );
	}

}
