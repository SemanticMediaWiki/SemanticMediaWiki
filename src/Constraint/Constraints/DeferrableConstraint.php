<?php

namespace SMW\Constraint\Constraints;

use SMW\Constraint\Constraint;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
abstract class DeferrableConstraint implements Constraint {

	/**
	 * @var boolean
	 */
	private $isCommandLineMode = false;

	/**
	 * @since 3.1
	 *
	 * @param boolean $isCommandLineMode
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
