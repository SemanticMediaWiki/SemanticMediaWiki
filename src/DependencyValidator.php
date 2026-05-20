<?php

namespace SMW;

use MediaWiki\Title\Title;
use SMW\DataItems\WikiPage;
use SMW\EventDispatcher\EventDispatcherAwareTrait;
use SMW\SQLStore\QueryDependency\DependencyLinksValidator;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class DependencyValidator {

	use EventDispatcherAwareTrait;

	private static array $titles = [];

	/**
	 * @since 3.1
	 */
	public function __construct(
		private readonly NamespaceExaminer $namespaceExaminer,
		private readonly DependencyLinksValidator $dependencyLinksValidator,
		private readonly EntityCache $entityCache,
		private readonly string $eTag,
		private readonly int $cacheTTL
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
		self::$titles[$title->getPrefixedText()] = true;
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 *
	 * @return bool
	 */
	public static function hasLikelyOutdatedDependencies( Title $title ): bool {
		return self::$titles[$title->getPrefixedText()] ?? false;
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

		// The parser cache is rejected, store for which key the request has
		// happened since the `smw_touched` is only updated once and given that
		// an anon/logged-in user create a different eTag (ParserCache) key
		// this will allows us to distinguish them later
		$key = $this->makeCacheKey( $title );

		// Genuine rejection based on `hasArchaicDependencies` therefore override
		// any previous sub keys
		$this->entityCache->overrideSub( $key, $this->eTag, 'hasArchaicDependencies', $this->cacheTTL );

		// Disable the parser cache even before `RejectParserCacheValue` comes into play
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

		$this->entityCache->saveSub( $key, $this->eTag, 'hasArchaicDependencies', $this->cacheTTL );

		return false;
	}

}
