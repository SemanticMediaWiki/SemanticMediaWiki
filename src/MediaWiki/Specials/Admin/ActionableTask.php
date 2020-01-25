<?php

namespace SMW\MediaWiki\Specials\Admin;

use WebRequest;

/**
 * @license GNU GPL v2+
 * @since   3.2
 *
 * @author mwjames
 */
interface ActionableTask {

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getTask() : string;

	/**
	 * @since 3.2
	 *
	 * @param string $action
	 *
	 * @return boolean
	 */
	public function isTaskFor( string $action ) : bool;

	/**
	 * @since 3.2
	 *
	 * @param WebRequest $webRequest
	 */
	public function handleRequest( WebRequest $webRequest );

}
