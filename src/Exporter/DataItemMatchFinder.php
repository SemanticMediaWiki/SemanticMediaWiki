<?php

namespace SMW\Exporter;

use MediaWiki\Title\Title;
use SMW\DataItems\DataItem;
use SMW\DataItems\WikiPage;
use SMW\Exporter\Element\ExpElement;
use SMW\Exporter\Element\ExpResource;
use SMW\Localizer\Localizer;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class DataItemMatchFinder {

	/**
	 * @since 2.4
	 */
	public function __construct(
		private readonly Store $store,
		private $wikiNamespace = '',
	) {
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
	public function matchExpElement( ExpElement $expElement ): ?WikiPage {
		$dataItem = null;

		if ( !$expElement instanceof ExpResource ) {
			return $dataItem;
		}

		$dataItem = $expElement->getDataItem();
		if ( $dataItem !== null && $dataItem instanceof WikiPage ) {
			// We already have a valid item
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

	private function matchToWikiNamespaceUri( $uri ): ?WikiPage {
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
			return new WikiPage( $dbKey, NS_MAIN, '', $subobjectname );
		}

		$namespaceId = $this->matchToNamespaceName( $parts[0] );

		if ( $namespaceId != -1 && $namespaceId !== false ) {
			$dataItem = new WikiPage( $parts[1], $namespaceId, '', $subobjectname );
		} else {
			$title = Title::newFromDBkey( $dbKey );

			if ( $title !== null ) {
				$dataItem = new WikiPage( $title->getDBkey(), $title->getNamespace(), $title->getInterwiki(), $subobjectname );
			}
		}

		return $dataItem;
	}

	private function matchToNamespaceName( $name ) {
		// try the by far most common cases directly before using Title
		$namespaceName = str_replace( '_', ' ', $name );

		if ( ( $namespaceId = Localizer::getInstance()->getNsIndex( $name ) ) !== false ) {
			return $namespaceId;
		}

		foreach ( [ SMW_NS_PROPERTY, NS_CATEGORY, NS_USER, NS_HELP ] as $nsId ) {
			if ( $namespaceName == Localizer::getInstance()->getNsText( $nsId ) ) {
				$namespaceId = $nsId;
				break;
			}
		}

		return $namespaceId;
	}

	private function matchToUnknownWikiNamespaceUri( $uri ): ?WikiPage {
		$dataItem = null;

		// Sesame: Not a valid (absolute) URI: _node1abjt1k9bx17
		if ( filter_var( $uri, FILTER_VALIDATE_URL ) === false ) {
			return $dataItem;
		}

		/**
		 * note: lookup rdfs:label as DBKey will fail in case a display title is used
		 * ToDo: Use swivt:page (after removing / splitting at $wgArticlePath + namespace,
		 * e. g. Category-3A) or rdfs:isDefinedBy (after removing /
		 * splitting at Special:ExportRDF/ + namespace, e. g. Category-3A)
		 * to get the page title from the full iri.
		 * see: https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/5527
		 */
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

			$dataItem = new WikiPage(
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
