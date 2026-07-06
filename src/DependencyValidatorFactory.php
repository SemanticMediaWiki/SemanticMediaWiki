<?php

namespace SMW;

use MediaWiki\Parser\ParserCache;
use MediaWiki\Parser\ParserOptions;
use SMW\EventDispatcher\EventDispatcher;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;
use WikiPage;

/**
 * Produces a fully-configured {@link DependencyValidator} for the
 * `RejectParserCacheValue` and `ArticleViewHeader` hooks, which both need a
 * per-request validator keyed on the current parser-output eTag.
 *
 * `QueryDependencyLinksStoreFactory` (not a pre-resolved
 * `DependencyLinksValidator`) is held so each `newFor()` call builds a fresh
 * validator. `DependencyLinksValidator` transitively captures `Store`, and
 * `HookContainer` caches handler instances across service-container resets, so
 * a captured validator could otherwise outlive the `Store` it was built with.
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
		private readonly EntityCache $entityCache,
		private readonly EventDispatcher $eventDispatcher,
		private readonly ParserCache $parserCache,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function newFor( WikiPage $page, ParserOptions $options ): DependencyValidator {
		$eTag = 'W/"' . $this->parserCache->makeParserOutputKey( $page, $options ) .
			'--' . $page->getTouched() . '"';

		return new DependencyValidator(
			$this->namespaceExaminer,
			$this->queryDependencyLinksStoreFactory->newDependencyLinksValidator(),
			$this->entityCache,
			$eTag,
			Site::getCacheExpireTime( 'parser' ),
			$this->eventDispatcher
		);
	}

}
