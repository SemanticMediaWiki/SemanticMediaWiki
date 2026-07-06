<?php

namespace SMW;

use MediaWiki\Title\Title;
use SMW\DataItems\WikiPage;
use SMW\EventDispatcher\EventDispatcher;
use SMW\SQLStore\QueryDependency\DependencyLinksValidator;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class DependencyValidator {

	private const DIRTY_MARKER = '_smw_dirty_';

	private static array $titles = [];

	/**
	 * @since 3.1
	 */
	public function __construct(
		private readonly NamespaceExaminer $namespaceExaminer,
		private readonly DependencyLinksValidator $dependencyLinksValidator,
		private readonly EntityCache $entityCache,
		private readonly string $eTag,
		private readonly int $cacheTTL,
		private readonly EventDispatcher $eventDispatcher
	) {
	}

	/**
	 * @since 2.2
	 */
	public static function makeCacheKey( Title $title ): string {
		return EntityCache::makeCacheKey( 'parsercacheinvalidator', $title->getPrefixedDBKey() );
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

		// Write the dirty marker under a fixed sub-key (not the current request's
		// eTag). canKeepParserCache will then find no eTag sub-key on the next
		// fetch and correctly reject the cache for each distinct eTag exactly
		// once, recording the eTag's handled state via its own saveSub call.
		$key = $this->makeCacheKey( $title );
		$this->entityCache->overrideSub( $key, self::DIRTY_MARKER, '1', $this->cacheTTL );

		return true;
	}

	/**
	 * @since 3.1
	 *
	 * @param WikiPage $subject
	 *
	 * @return bool
	 */
	public function canKeepParserCache( WikiPage $subject ): bool {
		$key = $this->makeCacheKey( $subject->getTitle() );

		// Test for a recent rejection, being unrelated etc.
		if (
			$this->entityCache->contains( $key ) === false ||
			$this->entityCache->fetchSub( $key, $this->eTag ) ) {
			return true;
		}

		// Value is unread; only the sub-key's presence matters.
		$this->entityCache->saveSub( $key, $this->eTag, '1', $this->cacheTTL );

		return false;
	}

}
