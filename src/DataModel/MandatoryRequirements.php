<?php

namespace SMW\DataModel;

use SMW\SemanticData;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMWDIContainer as DIContainer;
use SMW\Store;
use SMW\RequestOptions;
use Onoi\Cache\Cache;
use SMW\ChangePropListener;
use InvalidArgumentException;

/**
 * Class to represent and process mandatory requirements.
 *
 * Global requirements are cached to avoid excessive DB requests with the cache
 * being evicted as soon as some changes that involves the `Is mandatory` property
 * is detected in the attached ChangePropListener.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class MandatoryRequirements {

	const CACHE_NAMESPACE = 'smw:mreq';

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var integer
	 */
	private $cacheTTL;

	/**
	 * @var DIProperty[]|[]
	 */
	private $mandatoryProperties = [];

	/**
	 * @var []
	 */
	private $namespaceList = [];

	/**
	 * @since 3.0
	 *
	 * @param Cache|null $cache
	 */
	public function __construct( Cache $cache = null ) {

		if ( $cache === null ) {
			$cache = ApplicationFactory::getInstance()->getCache();
		}

		$this->cache = $cache;
		$this->cacheTTL = 60 * 60 * 24;
	}

	/**
	 * @since 3.0
	 *
	 * @param array|false $namespaceList
	 */
	public function setNamespaceList( $namespaceList ) {

		if ( !is_array( $namespaceList ) ) {
			$namespaceList = [];
		}

		$this->namespaceList = $namespaceList;
	}

	/**
	 * Any change that involves the Is mandatory (_MREI) property will trigger
	 * a cache eviction.
	 *
	 * @since 3.0
	 */
	public function setChangePropListener( ChangePropListener $changePropListener ) {

		$callback = function( $record ) {
			$this->cache->delete(
				smwfCacheKey( self::CACHE_NAMESPACE, 'GlobalRequirements' )
			);

			wfDebugLog( 'smw-mreq', 'MandatoryRequirements: GlobalRequirements cache invalidated due to a ChangePropListener event' );
		};

		$changePropListener->addListenerCallback( '_MREI', $callback );
	}

	/**
	 * @since 3.0
	 *
	 * @param SemanticData $semanticData
	 *
	 * @return boolean
	 */
	public function canCheckRequirements( SemanticData $semanticData ) {

		$subject = $semanticData->getSubject();

		// Skip on any non-user subobject (_QUERY..., _ERR... etc.)
		if ( $subject->getSubobjectName() !== '' && $semanticData->getOption( SemanticData::USER_ANNOTATION ) !== true ) {
			return false;
		}

		$ns = $subject->getNamespace();

		return isset( $this->namespaceList[$ns] ) && $this->namespaceList[$ns];
	}

	/**
	 * @since 3.0
	 *
	 * @return DIProperty[]|[]
	 */
	public function getMandatoryProperties() {
		return array_values( $this->mandatoryProperties );
	}

	/**
	 * @since 3.0
	 *
	 * @param SemanticData $semanticData
	 * @param DIProperty[] $propertyList
	 */
	public function copyRequirements( SemanticData $semanticData, array $propertyList ) {

		if ( $propertyList === [] ) {
			return;
		}

		ksort( $propertyList );

		$subject = $semanticData->getSubject();
		$identifier = '_MREQ' . md5( json_encode( array_keys( $propertyList ) ) );

		$subWikiPage = new DIWikiPage(
			$subject->getDBkey(),
			$subject->getNamespace(),
			$subject->getInterwiki(),
			$identifier
		);

		$containerSemanticData = new ContainerSemanticData(
			$subWikiPage
		);

		foreach ( $propertyList as $key => $prop ) {
			$containerSemanticData->addPropertyObjectValue(
				new DIProperty( '_MREP' ),
				$prop->getDiWikiPage()
			);
		}

		$semanticData->addPropertyObjectValue(
			new DIProperty( '_MREQ' ),
			new DIContainer( $containerSemanticData )
		);

		wfDebugLog( 'smw-mreq', 'MandatoryRequirements: Adding LocalRequirements on ' . $subject->getHash() . ' with ' . count( $propertyList ) . ' member(s)' );
	}

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 * @param SemanticData $semanticData
	 */
	public function findRequirements( Store $store, SemanticData $semanticData ) {

		if ( !$this->canCheckRequirements( $semanticData ) ) {
			return;
		}

		$this->mandatoryProperties = $this->findLocalRequirements(
			$semanticData
		);

		if ( $this->mandatoryProperties !== [] ) {
			return;
		}

		$this->mandatoryProperties = $this->findGlobalRequirements(
			$store
		);
	}

	private function findLocalRequirements( SemanticData $semanticData ) {

		$property = new DIProperty( '_MREQ' );

		if ( !$semanticData->hasProperty( $property ) ) {
			return [];
		}

		$pv = $semanticData->getPropertyValues( $property );
		$requirements = [];

		foreach ( $pv as $dataItem ) {

			$subSemanticData = $semanticData->findSubSemanticData(
				$dataItem->getSubobjectName()
			);

			$spv = $subSemanticData->getPropertyValues(
				new DIProperty( '_MREP' )
			);

			foreach ( $spv as $v ) {
				$requirements[$v->getDBKey()] = new DIProperty( $v->getDBkey() );
			}
		}

		return $requirements;
	}

	private function findGlobalRequirements( $store ) {

		$hash = smwfCacheKey( self::CACHE_NAMESPACE, 'GlobalRequirements' );

		if ( ( $requirements = $this->cache->fetch( $hash ) ) !== false ) {
			wfDebugLog( 'smw-mreq', 'MandatoryRequirements: Using GlobalRequirements cache' );
			return $requirements;
		}

		$requirements = [];
		$connection = $store->getConnection( 'mw.db' );

		// Only match property subjects
		$requestOptions = new RequestOptions();

		$requestOptions->addExtraCondition(
			'smw_namespace=' . $connection->addQuotes( SMW_NS_PROPERTY )
		);

		$property = new DIProperty( '_MREI' );

		$dataItems = $store->getAllPropertySubjects(
			$property,
			$requestOptions
		);

		if ( $dataItems === null ) {
			return $requirements;
		}

		foreach ( $dataItems as $dataItem ) {

			$pv = $store->getPropertyValues( $dataItem, $property );

			// Is mandatory === true?
			foreach ( $pv as $di ) {
				if ( $di->getBoolean() ) {
					$requirements[$dataItem->getDBkey()] = new DIProperty( $dataItem->getDBkey() );
				}
			}
		}

		wfDebugLog( 'smw-mreq', 'MandatoryRequirements: Created new GlobalRequirements list with ' . count( $requirements ) . ' member(s)' );

		$this->cache->save( $hash, $requirements, $this->cacheTTL );

		return $requirements;
	}

}
