<?php

namespace SMW;

/**
 * Interface describing access to a Title object
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
interface TitleAccess {

	/**
	 * Returns a Title object
	 *
	 * @since  1.9
	 *
	 * @return Title
	 */
	public function getTitle();

}