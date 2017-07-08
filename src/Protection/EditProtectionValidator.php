<?php

namespace SMW\Protection;

use Onoi\Cache\Cache;
use SMW\CachedPropertyValuesPrefetcher;
use SMW\DIWikiPage;
use SMW\RequestOptions;
use SMW\DIProperty;
use Title;

/**
 * Handles edit protection validation on the basis of an annotated `Is edit protected`
 * property value assignment.
 *
 * The lookup is cached using the `CachedPropertyValuesPrefetcher` to avoid a
 * continued access to the Store or DB layer.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class EditProtectionValidator {

	/**
	 * Reference used in InMemoryPoolCache
	 */
	const POOLCACHE_ID = 'edit.protection.validator';

	/**
	 * @var CachedPropertyValuesPrefetcher
	 */
	private $cachedPropertyValuesPrefetcher;

	/**
	 * @var Cache
	 */
	private $intermediaryMemoryCache;

	/**
	 * @var boolean|string
	 */
	private $editProtectionRight = false;

	/**
	 * @var boolean|string
	 */
	private $changePropagationProtection = true;

	/**
	 * @since 2.5
	 *
	 * @param CachedPropertyValuesPrefetcher $cachedPropertyValuesPrefetcher
	 * @param Cache $intermediaryMemoryCache
	 */
	public function __construct( CachedPropertyValuesPrefetcher $cachedPropertyValuesPrefetcher, Cache $intermediaryMemoryCache ) {
		$this->cachedPropertyValuesPrefetcher = $cachedPropertyValuesPrefetcher;
		$this->intermediaryMemoryCache = $intermediaryMemoryCache;
	}

	/**
	 * @since 2.5
	 *
	 * @param string|boolean $editProtectionRight
	 */
	public function setEditProtectionRight( $editProtectionRight ) {
		$this->editProtectionRight = $editProtectionRight;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $changePropagationProtection
	 */
	public function setChangePropagationProtection( $changePropagationProtection ) {
		$this->changePropagationProtection = (bool)$changePropagationProtection;
	}

	/**
	 * @since 2.5
	 *
	 * @param DIWikiPage $subject
	 */
	public function resetCacheBy( DIWikiPage $subject ) {
		$this->cachedPropertyValuesPrefetcher->resetCacheBy( $subject );
	}

	/**
	 * @since 2.5
	 *
	 * @param Title $title
	 *
	 * @return boolean
	 */
	public function hasProtectionOnNamespace( Title $title ) {
		return $this->checkProtection( DIWikiPage::newFromTitle( $title )->asBase() );
	}

	/**
	 * @since 2.5
	 *
	 * @param Title $title
	 *
	 * @return boolean
	 */
	public function hasChangePropagationProtection( Title $title ) {

		$subject = DIWikiPage::newFromTitle( $title )->asBase();

		if ( $subject->getNamespace() !== SMW_NS_PROPERTY || $this->changePropagationProtection === false ) {
			return false;
		}

		return $this->checkProtection( $subject, new DIProperty( '_CHGPRO' ) );
	}

	/**
	 * @since 2.5
	 *
	 * @param Title $title
	 *
	 * @return boolean
	 */
	public function hasProtection( Title $title ) {
		return $this->checkProtection( DIWikiPage::newFromTitle( $title )->asBase() );
	}

	/**
	 * @note There is not direct validation of the permission in this methods,
	 * it is done by the Title::userCan when probing against the User and hooks
	 * that carry our the permission check including the validation provided by
	 * SMW's `PermissionPthValidator`.
	 *
	 * @since 2.5
	 *
	 * @param Title $title
	 *
	 * @return boolean
	 */
	public function hasEditProtection( Title $title ) {
		return !$title->userCan( 'edit' ) && $this->checkProtection( DIWikiPage::newFromTitle( $title )->asBase() );
	}

	private function checkProtection( $subject, $property = null ) {

		if ( $property === null ) {
			$property = new DIProperty( '_EDIP' );
		}

		$key = $subject->getHash() . $property->getKey();
		$hasProtection = false;

		if ( $this->intermediaryMemoryCache->contains( $key ) ) {
			return $this->intermediaryMemoryCache->fetch( $key );
		}

		// Set editProtectionRight to influence the key to detect changes
		// before the cache is evicted
		$requestOptions = new RequestOptions();
		$requestOptions->addExtraCondition( $this->editProtectionRight );

		$dataItems = $this->cachedPropertyValuesPrefetcher->getPropertyValues(
			$subject,
			$property,
			$requestOptions
		);

		if ( $dataItems !== null && $dataItems !== array() ) {
			$hasProtection = $property->getKey() === '_EDIP' ? end( $dataItems )->getBoolean() : true;
		}

		$this->intermediaryMemoryCache->save( $key, $hasProtection );

		return $hasProtection;
	}

}
