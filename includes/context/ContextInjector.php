<?php

namespace SMW;

/**
 * Interface that describes a method to inject a ContextResource object
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
interface ContextInjector {

	/**
	 * Invokes a ContextResource object
	 *
	 * @since 1.9
	 */
	public function invokeContext( ContextResource $context );

}
