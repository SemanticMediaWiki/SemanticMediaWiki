<?php

namespace SMW;

use SMW\EventDispatcher\EventDispatcher;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;
use Wikimedia\ObjectCache\BagOStuff;

/**
 * Produces a fully-configured {@link DependencyValidator} for the
 * `RejectParserCacheValue` and `ArticleViewHeader` hooks.
 *
 * `QueryDependencyLinksStoreFactory` (not a pre-resolved
 * `DependencyLinksValidator`) is held so each `newDependencyValidator()` call
 * builds a fresh validator. `DependencyLinksValidator` transitively captures
 * `Store`, and `HookContainer` caches handler instances across service-container
 * resets, so a captured validator could otherwise outlive the `Store` it was
 * built with.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class DependencyValidatorFactory {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly NamespaceExaminer $namespaceExaminer,
		private readonly QueryDependencyLinksStoreFactory $queryDependencyLinksStoreFactory,
		private readonly BagOStuff $cache,
		private readonly EventDispatcher $eventDispatcher,
	) {
	}

	/**
	 * @since 7.4.0
	 */
	public function newDependencyValidator(): DependencyValidator {
		return new DependencyValidator(
			$this->namespaceExaminer,
			$this->queryDependencyLinksStoreFactory->newDependencyLinksValidator(),
			$this->cache,
			Site::getCacheExpireTime( 'parser' ),
			$this->eventDispatcher
		);
	}

}
