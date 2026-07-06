<?php

namespace SMW\MediaWiki;

use MediaWiki\Output\OutputPage;
use MediaWiki\Title\Title;
use SMW\DataItems\WikiPage;
use SMW\Indicator\IndicatorProvider;
use SMW\MediaWiki\Permission\PermissionAware;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\MediaWiki\Permission\PermissionExaminerAware;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class IndicatorRegistry {

	private array $indicatorProviders = [];

	private array $indicators = [];

	private array $modules = [];

	private array $inlineStyles = [];

	/**
	 * @since 3.1
	 */
	public function addIndicatorProvider( ?IndicatorProvider $indicatorProvider = null ): void {
		if ( $indicatorProvider === null ) {
			return;
		}

		$this->indicatorProviders[] = $indicatorProvider;
	}

	/**
	 * @since 3.1
	 */
	public function hasIndicator( Title $title, PermissionExaminer $permissionExaminer, array $options ): bool {
		$subject = WikiPage::newFromTitle(
			$title
		);

		foreach ( $this->indicatorProviders as $indicatorProvider ) {

			if ( $indicatorProvider instanceof PermissionExaminerAware ) {
				$indicatorProvider->setPermissionExaminer( $permissionExaminer );
			}

			if (
				$indicatorProvider instanceof PermissionAware &&
				!$indicatorProvider->hasPermission( $permissionExaminer ) ) {
				continue;
			}

			if ( !$indicatorProvider->hasIndicator( $subject, $options ) ) {
				continue;
			}

			$this->indicators = array_merge( $this->indicators, $indicatorProvider->getIndicators() );
			$this->modules = array_merge( $this->modules, $indicatorProvider->getModules() );
			$this->inlineStyles[] = $indicatorProvider->getInlineStyle();
		}

		return $this->indicators !== [];
	}

	/**
	 * @since 3.1
	 */
	public function attachIndicators( OutputPage $outputPage ): void {
		$outputPage->addModules( $this->modules );
		$outputPage->setIndicators( $this->indicators );
		$outputPage->addInlineStyle( implode( '', $this->inlineStyles ) );
	}

}
