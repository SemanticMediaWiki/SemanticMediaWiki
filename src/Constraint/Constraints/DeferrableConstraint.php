<?php

namespace SMW\Constraint\Constraints;

use SMW\Constraint\Constraint;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
abstract class DeferrableConstraint implements Constraint {

	/**
	 * @var bool
	 */
	private $isCommandLineMode = false;

	/**
	 * @since 3.1
	 *
	 * @param bool $isCommandLineMode
	 */
	public function isCommandLineMode( $isCommandLineMode ) {
		$this->isCommandLineMode = $isCommandLineMode;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function getType() {
		if ( $this->isCommandLineMode ) {
			return Constraint::TYPE_INSTANT;
		}

		return Constraint::TYPE_DEFERRED;
	}

}
