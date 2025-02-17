<?php

namespace SMW\MediaWiki;

/**
 * @license GPL-2.0-or-later
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
