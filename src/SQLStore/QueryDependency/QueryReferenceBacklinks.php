<?php

namespace SMW\SQLStore\QueryDependency;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Message;
use SMW\RequestOptions;
use SMW\SemanticData;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class QueryReferenceBacklinks {

	/**
	 * @var QueryDependencyLinksStore
	 */
	private $queryDependencyLinksStore = null;

	/**
	 * @since 2.5
	 *
	 * @param QueryDependencyLinksStore $queryDependencyLinksStore
	 */
	public function __construct( QueryDependencyLinksStore $queryDependencyLinksStore ) {
		$this->queryDependencyLinksStore = $queryDependencyLinksStore;
	}

	/**
	 * @since 2.5
	 *
	 * @param SemanticData $semanticData
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return boolean
	 */
	public function addReferenceLinksTo( SemanticData $semanticData, RequestOptions $requestOptions = null ) {

		if ( !$this->queryDependencyLinksStore->isEnabled() ) {
			return false;
		}

		// Don't display a reference where the requesting page is
		// part of the list that contains queries (suppress self-embedded queries)
		foreach ( $this->queryDependencyLinksStore->findEmbeddedQueryIdListBySubject( $semanticData->getSubject() ) as $key => $qid ) {
			$requestOptions->addExtraCondition( 's_id!=' . $qid );
		}

		$referenceLinks = $this->findReferenceLinks( $semanticData->getSubject(), $requestOptions );

		$property = new DIProperty(
			'_ASK'
		);

		foreach ( $referenceLinks as $subject ) {
			$semanticData->addPropertyObjectValue( $property, DIWikiPage::doUnserialize( $subject ) );
		}

		return true;
	}

	/**
	 * @since 2.5
	 *
	 * @param DIWikiPage $subject
	 * @param integer $limit
	 * @param integer $offset
	 *
	 * @return array
	 */
	public function findReferenceLinks( DIWikiPage $subject, RequestOptions $requestOptions = null ) {

		$queryTargetLinksHashList = $this->queryDependencyLinksStore->findDependencyTargetLinksForSubject(
			$subject,
			$requestOptions
		);

		return $queryTargetLinksHashList;
	}

	/**
	 * @since 2.5
	 *
	 * @param DIProperty $property
	 * @param DIWikiPage $subject
	 *
	 * @return boolean
	 */
	public function doesRequireFurtherLink( DIProperty $property, DIWikiPage $subject, &$html ) {

		if ( $property->getKey() !== '_ASK' ) {
			return true;
		}

		$localURL = \SpecialPage::getSafeTitleFor( 'SearchByProperty' )->getLocalURL(
			[
				 'property' => $property->getLabel(),
				 'value'    => $subject->getTitle()->getPrefixedText()
			]
		);

		$html .= \Html::element(
			'a',
			[ 'href' => $localURL ],
			Message::get( 'smw_browse_more' )
		);

		// Return false in order to stop the link creation process the replace the
		// generate link
		return false;
	}

}
