<?php

namespace SMW\MediaWiki\Specials;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Specials\PropertyLabelSimilarity\ContentsBuilder;
use SMW\SQLStore\Lookup\PropertyLabelSimilarityLookup;
use SMW\Message;
use SpecialPage;
use Html;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SpecialPropertyLabelSimilarity extends SpecialPage {

	/**
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		parent::__construct( 'PropertyLabelSimilarity' );
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $query ) {

		$this->setHeaders();
		$output = $this->getOutput();
		$webRequest = $this->getRequest();

		$applicationFactory = ApplicationFactory::getInstance();
		$store = $applicationFactory->getStore( '\SMW\SQLStore\SQLStore' );

		$propertyLabelSimilarityLookup = new PropertyLabelSimilarityLookup(
			$store
		);

		$propertyLabelSimilarityLookup->setExemptionProperty(
			$applicationFactory->getSettings()->get( 'smwgSimilarityLookupExemptionProperty' )
		);

		$htmlFormRenderer = $applicationFactory->newMwCollaboratorFactory()->newHtmlFormRenderer(
			$this->getContext()->getTitle(),
			$this->getLanguage()
		);

		$contentsBuilder = new ContentsBuilder(
			$propertyLabelSimilarityLookup,
			$htmlFormRenderer
		);

		$threshold = (int)$webRequest->getText( 'threshold', 90 );
		$type = $webRequest->getText( 'type', false );

		$offset = (int)$webRequest->getText( 'offset', 0 );
		$limit = (int)$webRequest->getText( 'limit', 50 );

		$requestOptions = $applicationFactory->getQueryFactory()->newRequestOptions();
		$requestOptions->setLimit( $limit );
		$requestOptions->setOffset( $offset );

		$requestOptions->addExtraCondition(
			[
				'type' => $type,
				'threshold' => $threshold
			]
		);

		$output->addHtml(
			$this->makeSpecialPageBreadcrumbLink()
		);

		$output->addHtml(
			$contentsBuilder->getHtml( $requestOptions )
		);

		return true;
	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName() {

		if ( version_compare( MW_VERSION, '1.33', '<' ) ) {
			return 'smw_group';
		}

		// #3711, MW 1.33+
		return 'smw_group/properties-concepts-types';
	}

	private static function makeSpecialPageBreadcrumbLink( $query = [] ) {

		return Html::rawElement(
			'div',
			[
				'class' => 'smw-breadcrumb-link'
			],
			Html::rawElement(
				'span',
				[
					'class' => 'smw-breadcrumb-arrow-right'
				]
			) . Html::rawElement(
				'a',
				[
					'href' => \SpecialPage::getTitleFor( 'Specialpages' )->getFullURL( $query ) . '#Semantic_MediaWiki'
				],
				Message::get( 'specialpages', Message::TEXT, Message::USER_LANGUAGE )
		) );
	}
}
