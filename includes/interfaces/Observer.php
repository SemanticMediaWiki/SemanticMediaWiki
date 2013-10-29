<?php

namespace SMW;

/**
 * Interface describing a Observer
 *
 * @ingroup Observer
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
interface Observer {

	/**
	 * Receives update task from a publishable source
	 *
	 * @since  1.9
	 *
	 * @param Observable $observable
	 */
	public function update( Observable $observable );

}