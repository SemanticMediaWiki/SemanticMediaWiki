<?php

namespace SMW\MediaWiki\Api\Tasks;

use SMW\Store;
use SMW\DIWikiPage;
use SMW\Services\ServicesFactory;
use SMW\Indicator\EntityExaminerIndicatorsFactory;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class EntityExaminerTask extends Task {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var EntityExaminerIndicatorsFactory
	 */
	private $entityExaminerIndicatorsFactory;

	/**
	 * @since 3.2
	 *
	 * @param Store $store
	 * @param EntityExaminerIndicatorsFactory $entityExaminerIndicatorsFactory
	 */
	public function __construct( Store $store, EntityExaminerIndicatorsFactory $entityExaminerIndicatorsFactory ) {
		$this->store = $store;
		$this->entityExaminerIndicatorsFactory = $entityExaminerIndicatorsFactory;
	}

	/**
	 * @since 3.2
	 *
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function process( array $parameters ) : array {

		if ( $parameters['subject'] === '' ) {
			return [ 'done' => false ];
		}

		$subject = DIWikiPage::doUnserialize(
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

		return $entityExaminerCompositeIndicatorProvider;
	}

}
