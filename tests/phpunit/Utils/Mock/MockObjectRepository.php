<?php

namespace SMW\Tests\Utils\Mock;

/**
 * Interface describing a MockObjectRepository object
 *
 *
 * @licence GNU GPL v2+
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
