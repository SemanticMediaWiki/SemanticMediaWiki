<?php

namespace SMW\Tests\Utils\Mock;

/**
 * Interface describing a MockObjectRepository object
 *
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
interface MockObjectRepository {

	/**
	 * Returns a DIProperty object
	 *
	 * @since  1.9
	 */
	public function registerBuilder( MockObjectBuilder $builder );

}
