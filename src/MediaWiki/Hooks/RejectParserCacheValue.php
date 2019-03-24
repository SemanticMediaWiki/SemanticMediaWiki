<?php

namespace SMW\MediaWiki\Hooks;

use SMW\SQLStore\QueryDependency\DependencyLinksValidator;
use Onoi\EventDispatcher\EventDispatcherAwareTrait;
use SMW\DIWikiPage;
use SMW\EntityCache;
use Title;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/RejectParserCacheValue
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RejectParserCacheValue extends HookHandler {

	use EventDispatcherAwareTrait;

	/**
	 * @var DependencyLinksValidator
	 */
	private $dependencyLinksValidator;

	/**
	 * @var EntityCache
	 */
	private $entityCache;

	/**
	 * @var integer
	 */
	private $cacheTTL = 3600;

	/**
	 * @since 3.0
	 *
	 * @param DependencyLinksValidator $dependencyLinksValidator
	 * @param EntityCache $entityCache
	 */
	public function __construct( DependencyLinksValidator $dependencyLinksValidator, EntityCache $entityCache ) {
		$this->dependencyLinksValidator = $dependencyLinksValidator;
		$this->entityCache = $entityCache;
	}

	/**
	 * @since 2.2
	 *
	 * @param integer $cacheTTL
	 */
	public function setCacheTTL( $cacheTTL ) {
		$this->cacheTTL = $cacheTTL;
	}

	/**
	 * @since 2.2
	 *
	 * @return integer
	 */
	public static function makeCacheKey( Title $title ) {
		return EntityCache::makeCacheKey( 'rejectparsercachevalue', $title->getPrefixedDBKey() );
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 * @param string $eTag
	 *
	 * @return boolean
	 */
	public function process( Title $title, $eTag ) {

		if ( $this->dependencyLinksValidator->canCheckDependencies() === false ) {
			return true;
		}

		$subject = DIWikiPage::newFromTitle( $title );
		$cacheKey = $this->makeCacheKey( $title );

		if ( $this->dependencyLinksValidator->hasArchaicDependencies( $subject ) === false ) {
			return $this->canKeepParserCache( $cacheKey, $eTag );
		}

		$context = [
			'context' => 'RejectParserCacheValue',
			'subject' => $subject->getHash(),
			'dependency_list' => $this->dependencyLinksValidator->getCheckedDependencies()
		];

		$this->eventDispatcher->dispatch( 'InvalidateResultCache', $context );

		// The parser cache is rejected, store for which key the request has
		// happened since the `smw_touched` is only updated once and given that
		// an anon/logged-in user create a different eTag (ParserCache) key
		// hereby allows us to distinguish them later

		// Genuine rejection based on `hasArchaicDependencies` therefore override
		// any previous sub keys
		$this->entityCache->overrideSub( $cacheKey, $eTag, 'hasArchaicDependencies', $this->cacheTTL );

		$this->logger->info(
			[ 'RejectParserCacheValue', 'Rejected, found archaic query dependencies', '{etag}' ],
			[ 'role' => 'user', 'etag' => $eTag ]
		);

		// Return false to reject an otherwise usable cached value from the
		// parser cache
		return false;
	}

	private function canKeepParserCache( $cacheKey, $eTag ) {

		// Test for a recent rejection, being unrelated etc.
		if (
			$this->entityCache->contains( $cacheKey ) === false ||
			$this->entityCache->fetchSub( $cacheKey, $eTag ) ) {
			return true;
		}

		$this->logger->info(
			[ 'RejectParserCacheValue', 'Rejected, found different key: {etag}' ],
			['role' => 'user', 'etag' => $eTag ]
		);

		$this->entityCache->saveSub( $cacheKey, $eTag, 'hasArchaicDependencies', $this->cacheTTL );

		return false;
	}

}
