<?php

namespace SMW\MediaWiki;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
trait RevisionGuardAwareTrait {

	/**
	 * @var RevisionGuard
	 */
	private $revisionGuard;

	/**
	 * @since 3.2
	 *
	 * @param RevisionGuard $revisionGuard
	 */
	public function setRevisionGuard( RevisionGuard $revisionGuard ) {
		$this->revisionGuard = $revisionGuard;
	}

}
