<?php

namespace SMW\Indicator\EntityExaminerIndicators;

use SMW\DataItems\WikiPage;
use SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider;
use SMW\Indicator\IndicatorProviders\TypableSeverityIndicatorProvider;
use SMW\Localizer\MessageLocalizerTrait;

/**
 * Demonstrates how to create a deferrable examiner to produce a possible violation
 * message for a specific aspect of an entity.
 *
 * Deferrable means that the examiner is only called after a wikipage has been
 * rendered and the check is called from within the `run-entity-examiner` API.
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class BlankEntityExaminerDeferrableIndicatorProvider implements TypableSeverityIndicatorProvider, DeferrableIndicatorProvider {

	use MessageLocalizerTrait;

	private array $indicators = [];

	private bool $isDeferredMode = false;

	private string $severityType = TypableSeverityIndicatorProvider::SEVERITY_WARNING;

	/**
	 * @since 3.2
	 */
	public function setDeferredMode( bool $isDeferredMode ): void {
		$this->isDeferredMode = $isDeferredMode;
	}

	/**
	 * @since 3.2
	 */
	public function isDeferredMode(): bool {
		return $this->isDeferredMode;
	}

	/**
	 * @since 3.2
	 */
	public function isSeverityType( string $severityType ): bool {
		return $this->severityType === $severityType;
	}

	/**
	 * @since 3.2
	 */
	public function getName(): string {
		return 'smw-entity-examiner-deferred-void';
	}

	/**
	 * @since 3.2
	 */
	public function hasIndicator( WikiPage $subject, array $options ): bool {
		if ( $this->isDeferredMode ) {
			$this->runCheck( $options );
		} else {
			$this->indicators = [ 'id' => $this->getName() ];
		}

		return $this->indicators !== [];
	}

	/**
	 * @since 3.2
	 */
	public function getIndicators(): array {
		return $this->indicators;
	}

	/**
	 * @since 3.2
	 */
	public function getModules(): array {
		return [];
	}

	/**
	 * @since 3.2
	 */
	public function getInlineStyle(): string {
		return '';
	}

	private function runCheck( array $options ): void {
		$options['dir'] = isset( $options['isRTL'] ) && $options['isRTL'] ? 'rtl' : 'ltr';

		// Doing some checks here ...
		$content = '';

		$this->indicators = [
			'id'      => $this->getName(),
			'title'   => 'foo',
			'content' => $content
		];
	}

}
