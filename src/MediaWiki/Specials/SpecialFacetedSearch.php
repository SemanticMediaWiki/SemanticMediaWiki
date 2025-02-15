<?php

namespace SMW\MediaWiki\Specials;

use SMW\MediaWiki\Hooks\GetPreferences;
use SMW\MediaWiki\Specials\FacetedSearch\ExploreListBuilder;
use SMW\MediaWiki\Specials\FacetedSearch\ExtraFieldBuilder;
use SMW\MediaWiki\Specials\FacetedSearch\FacetBuilder;
use SMW\MediaWiki\Specials\FacetedSearch\FilterFactory;
use SMW\MediaWiki\Specials\FacetedSearch\HtmlBuilder;
use SMW\MediaWiki\Specials\FacetedSearch\OptionsBuilder;
use SMW\MediaWiki\Specials\FacetedSearch\ParametersProcessor;
use SMW\MediaWiki\Specials\FacetedSearch\Profile;
use SMW\MediaWiki\Specials\FacetedSearch\ResultFetcher;
use SMW\MediaWiki\Specials\FacetedSearch\TreeBuilder;
use SMW\Services\ServicesFactory;
use SMW\Utils\TemplateEngine;
use SMW\Utils\UrlArgs;
use SpecialPage;

/**
 * @license GPL-2.0-or-later
 *
 * @since 3.2
 * @author mwjames
 */
class SpecialFacetedSearch extends SpecialPage {

	/**
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		parent::__construct( 'FacetedSearch', '', true, false, 'default', true );
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $query ) {
		$this->setHeaders();
		$output = $this->getOutput();
		$request = $this->getRequest();

		$this->addHelpLink( $this->msg( 'smw-specials-facetedsearch-helplink' )->escaped(), true );

		$output->addModuleStyles(
			[
				'smw.special.facetedsearch.styles',
				'ext.smw.special.styles',
				'jquery.makeCollapsible.styles',
				'smw.ui.styles',
				'smw.special.search.styles',
				'ext.smw.styles',
				'ext.smw.tooltip.styles'
			]
		);

		$output->addModules(
			[
				'smw.special.facetedsearch',
				'jquery.makeCollapsible',
				'ext.smw.suggester.textInput',
				'ext.smw.suggester',
				'ext.smw.tooltip',
				'smw.tableprinter.datatable',
				'ext.smw.autocomplete.property'
			]
		);

		$title = $this->getPageTitle();

		$servicesFactory = ServicesFactory::getInstance();
		$store = $servicesFactory->getStore();
		$userOptionsLookup = $servicesFactory->singleton( 'UserOptionsLookup' );

		/**
		 * Profile information
		 */
		$schemaFactory = $servicesFactory->singleton( 'SchemaFactory' );
		$default_profile = $userOptionsLookup->getOption(
			$this->getUser(),
			GetPreferences::FACETEDSEARCH_PROFILE_PREFERENCE, ''
		);

		if ( ( $profileName = $request->getVal( 'profile', $default_profile ) ) === '' ) {
			$profileName = $default_profile;
		}

		$profile = new Profile(
			$schemaFactory,
			$profileName
		);

		/**
		 * @var TemplateEngine
		 */
		$templateEngine = new TemplateEngine();
		$templateEngine->bulkLoad(
			[
				'/facetedsearch/container.ms' => 'facetedsearch-container',
				'/facetedsearch/container.empty.ms' => 'facetedsearch-container-empty',
				'/facetedsearch/intro.ms' => 'intro',

				// Search
				'/facetedsearch/search.ms' => 'search-form',
				'/facetedsearch/search.extrafields.ms' => 'search-extra-fields',
				'/facetedsearch/search.extrafield.input.ms' => 'search-extra-field-input',

				// Content, result
				'/facetedsearch/options.ms' => 'search-options',
				'/facetedsearch/content.ms' => 'facetedsearch-content',

				// Sidebar, facets, filters
				'/facetedsearch/sidebar.ms' => 'facetedsearch-sidebar',
				'/facetedsearch/filter/facet.ms' => 'filter-facet',
				'/facetedsearch/filter/cards.ms' => 'filter-cards',
				'/facetedsearch/filter/item.linked.ms' => 'filter-item-linked',
				'/facetedsearch/filter/item.linked.button.ms' => 'filter-item-linked-button',
				'/facetedsearch/filter/item.unlink.ms' => 'filter-item-unlink',
				'/facetedsearch/filter/item.unlink.button.ms' => 'filter-item-unlink-button',
				'/facetedsearch/filter/item.checkbox.ms' => 'filter-item-checkbox',
				'/facetedsearch/filter/items.input.ms' => 'filter-items-input',
				'/facetedsearch/filter/items.clear.ms' => 'filter-items-clear',
				'/facetedsearch/filter/items.clear.button.ms' => 'filter-items-clear-button',
				'/facetedsearch/filter/items.condition.ms' => 'filter-items-condition',
				'/facetedsearch/filter/items.option.ms' => 'filter-items-option',
				'/facetedsearch/filter/items.ms' => 'filter-items'
			]
		);

		/**
		 * Result fetcher
		 */
		$resultFetcher = new ResultFetcher(
			$store
		);

		/**
		 * Facet/Filter card builder
		 */
		$treeBuilder = new TreeBuilder(
			$store
		);

		$filterFactory = new FilterFactory(
			$templateEngine,
			$treeBuilder,
			$schemaFactory
		);

		$facetBuilder = new FacetBuilder(
			$profile,
			$templateEngine,
			$filterFactory,
			$resultFetcher,
		);

		/**
		 * HTML builder
		 */
		$htmlBuilder = new HtmlBuilder(
			$profile,
			$templateEngine,
			new OptionsBuilder( $profile ),
			new ExtraFieldBuilder( $profile, $templateEngine ),
			$facetBuilder,
			$resultFetcher,
			new ExploreListBuilder( $profile )
		);

		$urlArgs = new UrlArgs(
			$request->getValues()
		);

		if (
			$query === null &&
			( $request->getVal( 'q', '' ) === '' && array_filter( $request->getArray( 'fields', [] ) ) === [] ) ) {
			return $output->addHTML( $htmlBuilder->buildEmptyHTML( $title, $urlArgs ) );
		}

		$parametersProcessor = new ParametersProcessor(
			$profile
		);

		$parametersProcessor->checkRequest(
			$request
		);

		$parametersProcessor->process( $request, $query );

		$resultFetcher->fetchQueryResult(
			$parametersProcessor
		);

		$urlArgs = new UrlArgs(
			$request->getValues()
		);

		$html = $htmlBuilder->buildHTML( $title, $urlArgs );

		// Add any resources that were registered by a specific result
		// printer
		\SMWOutputs::commitToOutputPage( $output );

		$output->addHTML( $html );
	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName() {
		return 'smw_group/search';
	}

}
