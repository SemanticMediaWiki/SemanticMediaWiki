<?php

namespace SMW;

use MediaWiki\Title\Title;
use SMW\DataItems\WikiPage;
use SMW\EventDispatcher\EventDispatcher;
use SMW\SQLStore\QueryDependency\DependencyLinksValidator;
use Wikimedia\ObjectCache\BagOStuff;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class DependencyValidator {

	private static array $titles = [];

	/**
	 * @since 3.1
	 */
	public function __construct(
		private readonly NamespaceExaminer $namespaceExaminer,
		private readonly DependencyLinksValidator $dependencyLinksValidator,
		private readonly BagOStuff $cache,
		private readonly int $cacheTTL,
		private readonly EventDispatcher $eventDispatcher
	) {
	}

	/**
	 * @since 2.2
	 */
	public function makeCacheKey( Title $title ): string {
		return $this->cache->makeKey( 'smw', 'parsercache', 'dirty', md5( $title->getPrefixedDBKey() ) );
	}

	/**
	 * Signal to the `OutputPageParserOutput` hook to we want a possible purge
	 * action.
	 *
	 * @since 3.1
	 *
	 * @param Title $title
	 */
	public function markTitle( Title $title ): void {
		self::$titles[$title->getPrefixedText() ?? ''] = true;
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 *
	 * @return bool
	 */
	public static function hasLikelyOutdatedDependencies( Title $title ): bool {
		return self::$titles[$title->getPrefixedText() ?? ''] ?? false;
	}

	/**
	 * @since 3.1
	 *
	 * @param WikiPage $subject
	 *
	 * @return bool
	 */
	public function hasArchaicDependencies( WikiPage $subject ): bool {
		$title = $subject->getTitle();

		if ( !$this->namespaceExaminer->isSemanticEnabled( $title->getNamespace() ) ) {
			return false;
		}

		if (
			!$this->dependencyLinksValidator->canCheckDependencies() ||
			!$this->dependencyLinksValidator->hasArchaicDependencies( $subject ) ) {
			return false;
		}

		$context = [
			'context' => 'ParserCacheInvalidator',
			'subject' => $subject->getHash(),
			'dependency_list' => $this->dependencyLinksValidator->getCheckedDependencies()
		];

		$this->eventDispatcher->dispatch( 'InvalidateResultCache', $context );

		// Called before the request's parser cache lookup, so the re-parse that
		// lookup triggers is cached after this time and kept.
		$key = $this->makeCacheKey( $title );
		$this->cache->set( $key, wfTimestampNow(), $this->cacheTTL );

		return true;
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 * @param string $cacheTime TS_MW cache time of the cached output
	 */
	public function canKeepParserCache( Title $title, string $cacheTime ): bool {
		$markedAt = $this->cache->get( $this->makeCacheKey( $title ) );

		// As CacheTime::expired() does for page_touched, a cache time in the
		// same second as the marker counts as current.
		return $markedAt === false || $cacheTime >= $markedAt;
	}

}
