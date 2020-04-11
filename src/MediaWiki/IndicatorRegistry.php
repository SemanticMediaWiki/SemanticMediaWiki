<?php

namespace SMW\MediaWiki;

use Title;
use OutputPage;
use SMW\DIWikiPage;
use SMW\Indicator\IndicatorProvider;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\MediaWiki\Permission\PermissionAware;
use SMW\MediaWiki\Permission\PermissionExaminerAware;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class IndicatorRegistry {

	/**
	 * @var IndicatorProvider[]
	 */
	private $indicatorProviders = [];

	/**
	 * @var []
	 */
	private $indicators = [];

	/**
	 * @var []
	 */
	private $modules = [];

	/**
	 * @var []
	 */
	private $inlineStyles = [];

	/**
	 * @since 3.1
	 *
	 * @param IndicatorProvider|null $indicatorProvider
	 */
	public function addIndicatorProvider( IndicatorProvider $indicatorProvider = null ) {

		if ( $indicatorProvider === null ) {
			return;
		}

		$this->indicatorProviders[] = $indicatorProvider;
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 * @param PermissionExaminer $permissionExaminer
	 * @param array $options
	 *
	 * @return boolean
	 */
	public function hasIndicator( Title $title, PermissionExaminer $permissionExaminer, array $options ) {

		$subject = DIWikiPage::newFromTitle(
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
	 *
	 * @param OutputPage $outputPage
	 */
	public function attachIndicators( OutputPage $outputPage ) {
		$outputPage->addModules( $this->modules );
		$outputPage->setIndicators( $this->indicators );
		$outputPage->addInlineStyle( implode( '', $this->inlineStyles ) );
	}

}
