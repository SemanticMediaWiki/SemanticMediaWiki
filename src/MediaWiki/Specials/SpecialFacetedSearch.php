<?php

namespace SMW\MediaWiki\Specials;

use MediaWiki\Html\TemplateParser;
use MediaWiki\SpecialPage\SpecialPage;
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
use SMW\Utils\UrlArgs;

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
		 * @var TemplateParser
		 */
		$templateParser = new TemplateParser( __DIR__ . '/../../../templates/FacetedSearch' );

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
			$templateParser,
			$treeBuilder,
			$schemaFactory
		);

		$facetBuilder = new FacetBuilder(
			$profile,
			$templateParser,
			$filterFactory,
			$resultFetcher,
		);

		/**
		 * HTML builder
		 */
		$htmlBuilder = new HtmlBuilder(
			$profile,
			$templateParser,
			new OptionsBuilder( $profile ),
			new ExtraFieldBuilder( $profile, $templateParser ),
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
