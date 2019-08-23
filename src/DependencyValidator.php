<?php

namespace SMW;

use SMW\SQLStore\QueryDependency\DependencyLinksValidator;
use Onoi\EventDispatcher\EventDispatcherAwareTrait;
use SMW\NamespaceExaminer;
use SMW\DIWikiPage;
use SMW\EntityCache;
use Title;
use Page;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class DependencyValidator {

	use EventDispatcherAwareTrait;

	/**
	 * @var NamespaceExaminer
	 */
	private $namespaceExaminer;

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
	 * @var string
	 */
	private $eTag;

	/**
	 * @since 3.1
	 *
	 * @param NamespaceExaminer $namespaceExaminer
	 * @param DependencyLinksValidator $dependencyLinksValidator
	 * @param EntityCache $entityCache
	 */
	public function __construct( NamespaceExaminer $namespaceExaminer, DependencyLinksValidator $dependencyLinksValidator, EntityCache $entityCache ) {
		$this->namespaceExaminer = $namespaceExaminer;
		$this->dependencyLinksValidator = $dependencyLinksValidator;
		$this->entityCache = $entityCache;
	}

	/**
	 * @since 3.1
	 *
	 * @param integer $cacheTTL
	 */
	public function setCacheTTL( $cacheTTL ) {
		$this->cacheTTL = $cacheTTL;
	}

	/**
	 * @since 3.1
	 *
	 * @param string eTag
	 */
	public function setETag( $eTag ) {
		$this->eTag = $eTag;
	}

	/**
	 * @since 2.2
	 *
	 * @return integer
	 */
	public static function makeCacheKey( Title $title ) {
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
	public function markTitle( Title $title ) {
		$title->smwLikelyOutdatedDependencies = true;
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 *
	 * @return boolean
	 */
	public static function hasLikelyOutdatedDependencies( Title $title ) {
		return isset( $title->smwLikelyOutdatedDependencies) && $title->smwLikelyOutdatedDependencies;
	}

	/**
	 * @since 3.1
	 *
	 * @param Page $page
	 *
	 * @return boolean
	 */
	public function hasArchaicDependencies( DIWikiPage $subject ) {

		$title = $subject->getTitle();

		if ( $this->namespaceExaminer->isSemanticEnabled( $title->getNamespace() ) === false ) {
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
	 * @param DIWikiPage $subject
	 *
	 * @return boolean
	 */
	public function canKeepParserCache( DIWikiPage $subject ) {

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
