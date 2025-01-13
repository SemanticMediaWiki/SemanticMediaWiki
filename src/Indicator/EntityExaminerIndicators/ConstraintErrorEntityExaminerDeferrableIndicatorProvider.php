<?php

namespace SMW\Indicator\EntityExaminerIndicators;

use SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ConstraintErrorEntityExaminerDeferrableIndicatorProvider extends ConstraintErrorEntityExaminerIndicatorProvider implements DeferrableIndicatorProvider {

	/**
	 * @var bool
	 */
	private $isDeferredMode = false;

	/**
	 * @since 3.2
	 *
	 * @param bool $isDeferredMode
	 */
	public function setDeferredMode( bool $isDeferredMode ) {
		$this->isDeferredMode = $isDeferredMode;
	}

	/**
	 * @since 3.2
	 *
	 * @return bool
	 */
	public function isDeferredMode(): bool {
		return $this->isDeferredMode;
	}

	/**
	 * @see ConstraintErrorEntityExaminerIndicatorProvider::checkConstraintErrors
	 */
	protected function checkConstraintErrors( $subject, $options ) {
		if ( $this->isDeferredMode ) {
			return $this->runCheck( $subject, $options );
		}

		$this->indicators = [ 'id' => $this->getName() ];
	}

}
