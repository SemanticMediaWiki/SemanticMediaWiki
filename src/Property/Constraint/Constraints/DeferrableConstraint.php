<?php

namespace SMW\Property\Constraint\Constraints;

use SMW\Property\Constraint\Constraint;

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
	public function isType( $type ) {

		if ( $this->isCommandLineMode ) {
			return $type === Constraint::TYPE_INSTANT;
		}

		return $type === Constraint::TYPE_DEFERRED;
	}

}
