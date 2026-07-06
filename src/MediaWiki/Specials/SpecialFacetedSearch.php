<?php

namespace SMW\MediaWiki\Specials;

use MediaWiki\Html\TemplateParser;
use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;
use SMW\MediaWiki\Hooks\GetPreferences;
use SMW\MediaWiki\Outputs;
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
use SMW\Schema\SchemaFactory;
use SMW\Store;
use SMW\Utils\UrlArgs;

/**
 * @license GPL-2.0-or-later
 *
 * @since 3.2
 * @author mwjames
 */
class SpecialFacetedSearch extends SpecialPage {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly Store $store,
		private readonly SchemaFactory $schemaFactory
	) {
		// MediaWiki 1.46 deprecated the SpecialPage constructor flags; the
		// page stays transcludable via the isIncludable() override below.
		parent::__construct( 'FacetedSearch' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function isIncludable(): bool {
		return true;
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $query ) {
		$this->setHeaders();
		$output = $this->getOutput();
		$request = $this->getRequest();

		$this->addHelpLink( $this->msg( 'smw-specials-facetedsearch-helplink' )->text(), true );

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

		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();

		/**
		 * Profile information
		 */
		$default_profile = $userOptionsLookup->getOption(
			$this->getUser(),
			GetPreferences::FACETEDSEARCH_PROFILE_PREFERENCE, ''
		);

		$profileName = $request->getVal( 'profile', $default_profile );
		if ( $profileName === '' ) {
			$profileName = $default_profile;
		}

		$profile = new Profile(
			$this->schemaFactory,
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
			$this->store
		);

		/**
		 * Facet/Filter card builder
		 */
		$treeBuilder = new TreeBuilder(
			$this->store
		);

		$filterFactory = new FilterFactory(
			$templateParser,
			$treeBuilder,
			$this->schemaFactory
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
		Outputs::commitToOutputPage( $output );

		$output->addHTML( $html );
	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName(): string {
		return 'smw_group/search';
	}

}
