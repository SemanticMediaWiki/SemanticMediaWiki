<?php

namespace SMW;

use MediaWiki\Parser\ParserCache;
use MediaWiki\Parser\ParserOptions;
use SMW\EventDispatcher\EventDispatcher;
use SMW\SQLStore\QueryDependency\DependencyLinksValidator;
use WikiPage;

/**
 * Produces a fully-configured {@link DependencyValidator} for the
 * `RejectParserCacheValue` and `ArticleViewHeader` hooks, which both need a
 * per-request validator keyed on the current parser-output eTag.
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
		private readonly DependencyLinksValidator $dependencyLinksValidator,
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

		$dependencyValidator = new DependencyValidator(
			$this->namespaceExaminer,
			$this->dependencyLinksValidator,
			$this->entityCache,
			$eTag,
			Site::getCacheExpireTime( 'parser' )
		);

		$dependencyValidator->setEventDispatcher( $this->eventDispatcher );

		return $dependencyValidator;
	}

}
