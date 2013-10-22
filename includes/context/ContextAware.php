<?php

namespace SMW;

/**
 * Interface that describes access to a ContextResource object
 *
 * @note It is expected that a context object is either injected using a constructor
 * or implements the ContextInjector interface
 *
 * @ingroup SMW
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
