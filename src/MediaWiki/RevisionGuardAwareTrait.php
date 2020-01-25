<?php

namespace SMW\MediaWiki;

/**
 * @license GNU GPL v2+
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
