<?php

namespace SMW\MediaWiki\Specials;

use MediaWiki\Html\Html;
use MediaWiki\Skin\SkinComponentUtils;
use MediaWiki\SpecialPage\SpecialPage;
use SMW\Localizer\Message;
use SMW\MediaWiki\Specials\PropertyLabelSimilarity\ContentsBuilder;
use SMW\QueryFactory;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Settings;
use SMW\SQLStore\Lookup\PropertyLabelSimilarityLookup;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class SpecialPropertyLabelSimilarity extends SpecialPage {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly Store $store,
		private readonly Settings $settings,
		private readonly QueryFactory $queryFactory
	) {
		parent::__construct( 'PropertyLabelSimilarity' );
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $query ): bool {
		$this->setHeaders();
		$output = $this->getOutput();
		$webRequest = $this->getRequest();

		$output->addModuleStyles( [ 'ext.smw.styles' ] );

		// PropertyLabelSimilarityLookup requires the SQLStore-typed surface.
		// When the injected default Store is not an SQLStore (e.g. SPARQLStore)
		// the lookup needs a separately-built SQL store; ApplicationFactory's
		// `getStore( SQLStore::class )` path mirrors the partial-DI pattern
		// used in the API Browse module.
		$store = $this->store instanceof SQLStore
			? $this->store
			: ApplicationFactory::getInstance()->getStore( SQLStore::class );

		$propertyLabelSimilarityLookup = new PropertyLabelSimilarityLookup(
			$store
		);

		$propertyLabelSimilarityLookup->setExemptionProperty(
			$this->settings->get( 'smwgSimilarityLookupExemptionProperty' )
		);

		// Partial DI: MwCollaboratorFactory is still resolved through
		// ApplicationFactory because it is not registered as a global SMW.X
		// service.
		$htmlFormRenderer = ApplicationFactory::getInstance()->newMwCollaboratorFactory()->newHtmlFormRenderer(
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

		$requestOptions = $this->queryFactory->newRequestOptions();
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
	protected function getGroupName(): string {
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
			) . Html::element(
				'a',
				[
					'href' => SkinComponentUtils::makeSpecialUrl( 'Specialpages', $query ) . '#Semantic_MediaWiki'
				],
				Message::get( 'specialpages', Message::TEXT, Message::USER_LANGUAGE )
		) );
	}
}
