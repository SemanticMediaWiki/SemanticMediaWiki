<?php

namespace SMW\MediaWiki;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
trait HookDispatcherAwareTrait {

	/**
	 * @var HookDispatcher
	 */
	private $hookDispatcher;

	/**
	 * @since 3.2
	 *
	 * @param HookDispatcher $hookDispatcher
	 */
	public function setHookDispatcher( HookDispatcher $hookDispatcher ) {
		$this->hookDispatcher = $hookDispatcher;
	}

}
