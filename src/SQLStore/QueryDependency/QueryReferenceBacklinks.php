<?php

namespace SMW\SQLStore\QueryDependency;

use MediaWiki\Html\Html;
use MediaWiki\Skin\SkinComponentUtils;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Localizer\Message;
use SMW\RequestOptions;

/**
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class QueryReferenceBacklinks {

	/**
	 * @since 2.5
	 */
	public function __construct( private readonly QueryDependencyLinksStore $queryDependencyLinksStore ) {
	}

	/**
	 * @since 2.5
	 *
	 * @param SemanticData $semanticData
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return bool
	 */
	public function addReferenceLinksTo( SemanticData $semanticData, ?RequestOptions $requestOptions = null ): bool {
		if ( !$this->queryDependencyLinksStore->isEnabled() ) {
			return false;
		}

		// Don't display a reference where the requesting page is
		// part of the list that contains queries (suppress self-embedded queries)
		foreach ( $this->queryDependencyLinksStore->findEmbeddedQueryIdListBySubject( $semanticData->getSubject() ) as $key => $qid ) {
			$requestOptions->addExtraCondition( 's_id!=' . $qid );
		}

		$referenceLinks = $this->findReferenceLinks( $semanticData->getSubject(), $requestOptions );

		$property = new Property(
			'_ASK'
		);

		foreach ( $referenceLinks as $subject ) {
			$semanticData->addPropertyObjectValue( $property, WikiPage::doUnserialize( $subject ) );
		}

		return true;
	}

	/**
	 * @since 2.5
	 *
	 * @param WikiPage $subject
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return array
	 */
	public function findReferenceLinks( WikiPage $subject, ?RequestOptions $requestOptions = null ) {
		$queryTargetLinksHashList = $this->queryDependencyLinksStore->findDependencyTargetLinksForSubject(
			$subject,
			$requestOptions
		);

		return $queryTargetLinksHashList;
	}

	/**
	 * @since 2.5
	 *
	 * @param Property $property
	 * @param WikiPage $subject
	 *
	 * @return bool
	 */
	public function doesRequireFurtherLink( Property $property, WikiPage $subject, &$html ): bool {
		if ( $property->getKey() !== '_ASK' ) {
			return true;
		}

		$localURL = SkinComponentUtils::makeSpecialUrl( 'SearchByProperty', [
			'property' => $property->getLabel(),
			'value' => $subject->getTitle()->getPrefixedText()
		] );

		$html .= Html::element(
			'a',
			[ 'href' => $localURL ],
			Message::get( 'smw_browse_more' )
		);

		// Return false in order to stop the link creation process the replace the
		// generate link
		return false;
	}

}
