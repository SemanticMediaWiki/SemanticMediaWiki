<?php

namespace SMW\MediaWiki;

use SMW\Indicator\EntityExaminerIndicatorsFactory;
use SMW\Store;

/**
 * Produces a fully-configured {@link IndicatorRegistry} for the
 * `OutputPageParserOutput` hook. The hook needs a fresh registry per request
 * because whether the user has the entity-issue panel enabled is a per-user
 * preference, and the registry exposes that behaviour by conditionally
 * attaching the `EntityExaminerCompositeIndicatorProvider`.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class IndicatorRegistryFactory {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly Store $store,
		private readonly EntityExaminerIndicatorsFactory $entityExaminerIndicatorsFactory,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function newFor( bool $withEntityExaminer ): IndicatorRegistry {
		$indicatorRegistry = new IndicatorRegistry();

		if ( !$withEntityExaminer ) {
			return $indicatorRegistry;
		}

		$indicatorRegistry->addIndicatorProvider(
			$this->entityExaminerIndicatorsFactory->newEntityExaminerIndicatorProvider( $this->store )
		);

		return $indicatorRegistry;
	}

}
