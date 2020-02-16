<?php

namespace SMW\Indicator\EntityExaminerIndicators;

use SMW\Indicator\IndicatorProviders\TypableSeverityIndicatorProvider;
use SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ConstraintErrorEntityExaminerDeferrableIndicatorProvider extends ConstraintErrorEntityExaminerIndicatorProvider implements DeferrableIndicatorProvider {

	/**
	 * @var boolean
	 */
	private $isDeferredMode = false;

	/**
	 * @since 3.2
	 *
	 * @param boolean $type
	 */
	public function setDeferredMode( bool $isDeferredMode ) {
		$this->isDeferredMode = $isDeferredMode;
	}

	/**
	 * @since 3.2
	 *
	 * @return boolean
	 */
	public function isDeferredMode() : bool {
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
