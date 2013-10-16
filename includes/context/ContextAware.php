<?php

namespace SMW;

/**
 * Interface that describes access to a context object
 *
 * @note It is expected that a context object is either injected using a constructor
 * or the implements a Contextinjector interface
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
interface ContextAware {

	/**
	 * Returns a ContextResource object
	 *
	 * @since 1.9
	 *
	 * @return ContextResource
	 */
	public function withContext();

}
