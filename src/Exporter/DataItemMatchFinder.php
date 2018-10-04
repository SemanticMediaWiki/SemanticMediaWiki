<?php

namespace SMW\Exporter;

use SMW\DIWikiPage;
use SMW\Exporter\Element\ExpElement;
use SMW\Exporter\Element\ExpResource;
use SMW\Localizer;
use SMW\Store;
use SMWDataItem as DataItem;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class DataItemMatchFinder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var string
	 */
	private $wikiNamespace;

	/**
	 * @since 2.4
	 *
	 * @param Store $store
	 * @param string $wikiNamespace
	 */
	public function __construct( Store $store, $wikiNamespace = '' ) {
		$this->store = $store;
		$this->wikiNamespace = $wikiNamespace;
	}

	/**
	 * Try to map an ExpElement to a representative DataItem which may return null
	 * if the attempt fails.
	 *
	 * @since 2.4
	 *
	 * @param ExpElement $expElement
	 *
	 * @return DataItem|null
	 */
	public function matchExpElement( ExpElement $expElement ) {

		$dataItem = null;

		if ( !$expElement instanceof ExpResource ) {
			return $dataItem;
		}

		$uri = $expElement->getUri();

		if ( strpos( $uri, $this->wikiNamespace ) !== false ) {
			$dataItem = $this->matchToWikiNamespaceUri( $uri );
		} else {
			 // Not in wikiNamespace therefore most likely an imported URI
			$dataItem = $this->matchToUnknownWikiNamespaceUri( $uri );
		}

		return $dataItem;
	}

	private function matchToWikiNamespaceUri( $uri ) {

		$dataItem = null;
		$localName = substr( $uri, strlen( $this->wikiNamespace ) );

		$dbKey = rawurldecode( Escaper::decodeUri( $localName ) );
		$parts = explode( '#', $dbKey, 2 );

		if ( count( $parts ) == 2 ) {
			$dbKey = $parts[0];
			$subobjectname = $parts[1];
		} else {
			$subobjectname = '';
		}

		$parts = explode( ':', $dbKey, 2 );

		// No extra NS
		if ( count( $parts ) == 1 ) {
			return new DIWikiPage( $dbKey, NS_MAIN, '', $subobjectname );
		}

		$namespaceId = $this->matchToNamespaceName( $parts[0] );

		if ( $namespaceId != -1 && $namespaceId !== false ) {
			$dataItem = new DIWikiPage( $parts[1], $namespaceId, '', $subobjectname );
		} else {
			$title = Title::newFromDBkey( $dbKey );

			if ( $title !== null ) {
				$dataItem = new DIWikiPage( $title->getDBkey(), $title->getNamespace(), $title->getInterwiki(), $subobjectname );
			}
		}

		return $dataItem;
	}

	private function matchToNamespaceName( $name ) {
		// try the by far most common cases directly before using Title
		$namespaceName = str_replace( '_', ' ', $name );

		if ( ( $namespaceId = Localizer::getInstance()->getNamespaceIndexByName( $name ) ) !== false ) {
			return $namespaceId;
		}

		foreach ( [ SMW_NS_PROPERTY, NS_CATEGORY, NS_USER, NS_HELP ] as $nsId ) {
			if ( $namespaceName == Localizer::getInstance()->getNamespaceTextById( $nsId ) ) {
				$namespaceId = $nsId;
				break;
			}
		}

		return $namespaceId;
	}

	private function matchToUnknownWikiNamespaceUri( $uri ) {

		$dataItem = null;

		// Sesame: Not a valid (absolute) URI: _node1abjt1k9bx17
		if ( filter_var( $uri, FILTER_VALIDATE_URL ) === false ) {
			return $dataItem;
		}

		$respositoryResult = $this->store->getConnection( 'sparql' )->select(
			'?v1 ?v2',
			"<$uri> rdfs:label ?v1 . <$uri> swivt:wikiNamespace ?v2",
			[ 'LIMIT' => 1 ]
		);

		$expElements = $respositoryResult->current();

		if ( $expElements !== false ) {

			// ?v1
			if ( isset( $expElements[0] ) ) {
				$dbKey = $expElements[0]->getLexicalForm();
			} else {
				$dbKey = 'UNKNOWN';
			}

			// ?v2
			if ( isset( $expElements[1] ) ) {
				$namespace = strval( $expElements[1]->getLexicalForm() );
			} else {
				$namespace = NS_MAIN;
			}

			$dataItem = new DIWikiPage(
				$this->getFittingDBKey( $dbKey, $namespace ),
				$namespace
			);
		}

		return $dataItem;
	}

	private function getFittingDBKey( $dbKey, $namespace ) {

		// https://www.mediawiki.org/wiki/Manual:$wgCapitalLinks
		// https://www.mediawiki.org/wiki/Manual:$wgCapitalLinkOverrides
		if ( $GLOBALS['wgCapitalLinks'] || ( isset( $GLOBALS['wgCapitalLinkOverrides'][$namespace] ) && $GLOBALS['wgCapitalLinkOverrides'][$namespace] ) ) {
			return mb_strtoupper( mb_substr( $dbKey, 0, 1 ) ) . mb_substr( $dbKey, 1 );
		}

		return $dbKey;
	}

}
