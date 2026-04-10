<?php

namespace SMW\MediaWiki\Api\Tasks;

use SMW\DataItems\WikiPage;
use SMW\Indicator\EntityExaminerIndicatorsFactory;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\MediaWiki\Permission\PermissionExaminerAware;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class EntityExaminerTask extends Task implements PermissionExaminerAware {

	private ?PermissionExaminer $permissionExaminer = null;

	/**
	 * @since 3.2
	 */
	public function __construct(
		private readonly Store $store,
		private readonly EntityExaminerIndicatorsFactory $entityExaminerIndicatorsFactory,
	) {
	}

	/**
	 * @see PermissionExaminerAware::setPermissionExaminer
	 * @since 3.2
	 *
	 * @param PermissionExaminer $permissionExaminer
	 */
	public function setPermissionExaminer( PermissionExaminer $permissionExaminer ): void {
		$this->permissionExaminer = $permissionExaminer;
	}

	/**
	 * @since 3.2
	 *
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function process( array $parameters ): array {
		if ( $parameters['subject'] === '' ) {
			return [ 'done' => false ];
		}

		$subject = WikiPage::doUnserialize(
			$parameters['subject']
		);

		$indicators = [];
		$html = '';

		// We only have a placeholder and no other body which require to access
		// the entire instance including HTML body for the highlighter
		if ( isset( $parameters['is_placeholder'] ) && $parameters['is_placeholder'] ) {
			$entityExaminerIndicatorProvider = $this->newEntityExaminerIndicatorProvider();

			$entityExaminerIndicatorProvider->hasIndicator(
				$subject,
				$parameters
			);

			$html = $entityExaminerIndicatorProvider->getIndicators();
		} else {
			$entityExaminerDeferrableCompositeIndicatorProvider = $this->newEntityExaminerDeferrableCompositeIndicatorProvider();

			$entityExaminerDeferrableCompositeIndicatorProvider->hasIndicator(
				$subject,
				$parameters
			);

			$indicators = $entityExaminerDeferrableCompositeIndicatorProvider->getIndicators();
		}

		return [ 'done' => true, 'indicators' => $indicators, 'html' => $html ];
	}

	private function newEntityExaminerDeferrableCompositeIndicatorProvider() {
		$entityExaminerDeferrableCompositeIndicatorProvider = $this->entityExaminerIndicatorsFactory->newEntityExaminerDeferrableCompositeIndicatorProvider(
			$this->store
		);

		if ( $this->permissionExaminer instanceof PermissionExaminer ) {
			$entityExaminerDeferrableCompositeIndicatorProvider->setPermissionExaminer(
				$this->permissionExaminer
			);
		}

		$entityExaminerDeferrableCompositeIndicatorProvider->setDeferredMode(
			true
		);

		return $entityExaminerDeferrableCompositeIndicatorProvider;
	}

	private function newEntityExaminerIndicatorProvider() {
		$entityExaminerDeferrableCompositeIndicatorProvider = $this->newEntityExaminerDeferrableCompositeIndicatorProvider();

		$entityExaminerCompositeIndicatorProvider = $this->entityExaminerIndicatorsFactory->newEntityExaminerCompositeIndicatorProvider(
			[
				$entityExaminerDeferrableCompositeIndicatorProvider
			]
		);

		if ( $this->permissionExaminer instanceof PermissionExaminer ) {
			$entityExaminerCompositeIndicatorProvider->setPermissionExaminer(
				$this->permissionExaminer
			);
		}

		return $entityExaminerCompositeIndicatorProvider;
	}

}
