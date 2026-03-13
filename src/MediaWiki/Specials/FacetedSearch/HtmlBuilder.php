<?php

namespace SMW\MediaWiki\Specials\FacetedSearch;

use MediaWiki\Html\TemplateParser;
use MediaWiki\Title\Title;
use SMW\Localizer\Message;
use SMW\Localizer\MessageLocalizerTrait;
use SMW\Utils\UrlArgs;

/**
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class HtmlBuilder {

	use MessageLocalizerTrait;

	/**
	 * @var Profile
	 */
	private $profile;

	/**
	 * @var TemplateParser
	 */
	private $templateParser;

	/**
	 * @var OptionsBuilder
	 */
	private $optionsBuilder;

	/**
	 * @var ExtraFieldBuilder
	 */
	private $extraFieldBuilder;

	/**
	 * @var FacetBuilder
	 */
	private $facetBuilder;

	/**
	 * @var ResultFetcher
	 */
	private $resultFetcher;

	/**
	 * @var ExploreListBuilder
	 */
	private $exploreListBuilder;

	/**
	 * @since 3.2
	 *
	 * @param Profile $profile
	 * @param TemplateParser $templateParser
	 * @param OptionsBuilder $optionsBuilder
	 * @param ExtraFieldBuilder $extraFieldBuilder
	 * @param FacetBuilder $facetBuilder
	 * @param ResultFetcher $resultFetcher
	 * @param ExploreListBuilder $exploreListBuilder
	 */
	public function __construct( Profile $profile, TemplateParser $templateParser, OptionsBuilder $optionsBuilder, ExtraFieldBuilder $extraFieldBuilder, FacetBuilder $facetBuilder, ResultFetcher $resultFetcher, ExploreListBuilder $exploreListBuilder ) {
		$this->profile = $profile;
		$this->templateParser = $templateParser;
		$this->optionsBuilder = $optionsBuilder;
		$this->extraFieldBuilder = $extraFieldBuilder;
		$this->facetBuilder = $facetBuilder;
		$this->resultFetcher = $resultFetcher;
		$this->exploreListBuilder = $exploreListBuilder;
	}

	/**
	 * @since 3.2
	 *
	 * @param Title $title
	 * @param UrlArgs $urlArgs
	 */
	public function buildEmptyHTML( Title $title, UrlArgs $urlArgs ): string {
		$profileName = $this->profile->getProfileName();

		$searchForm = $this->templateParser->processTemplate(
			'search',
			[
				'action' => $title->getLocalUrl(),
				'method' => 'get',
				'q' => '',
				'csum' => '',
				'limit' => 0,
				'offset' => 0,
				'size' => 0,
				'fields' => '',
				'search-label' => $this->msg( 'smw-ask-search' ),
				'search-placeholder-label' => $this->msg( 'smw-search-placeholder' ),
				'profile-title' => $this->msg( 'smw-facetedsearch-profile-options' ),
				'profile-select-disabled' => $this->profile->getProfileCount() > 1 ? '' : 'disabled',
				'profile-options' => $this->optionsBuilder->profiles( $profileName ),
				'hidden' => ''
			]
		);

		$intro = $this->templateParser->processTemplate(
			'intro',
			[
				'text' => $this->msg( 'smw-facetedsearch-intro-text', Message::PARSE ),
				'tips' => $this->msg( 'smw-facetedsearch-intro-tips', Message::PARSE )
			]
		);

		if ( ( $html = $this->exploreListBuilder->buildHTML( $title ) ) === '' ) {
			$html = $intro;
		}

		return $this->templateParser->processTemplate(
			'container.empty',
			[
				'search' => $searchForm,
				'search-extra-fields' => $this->extraFieldBuilder->buildHTML( $urlArgs ),
				'intro' => $html,
				'theme' => $this->profile->get( 'theme' )
			]
		);
	}

	/**
	 * @since 3.2
	 *
	 * @param Title $title
	 * @param UrlArgs $urlArgs
	 */
	public function buildHTML( Title $title, UrlArgs $urlArgs ): string {
		$result = $this->resultFetcher->getHtml();
		$profileName = $urlArgs->get( 'profile', 'default' );

		$params = [
			'count' => $this->resultFetcher->getTotalCount(),
			'offset' => $this->resultFetcher->getOffset(),
			'limit' => $this->resultFetcher->getLimit(),
			'hasFurtherResults' => $this->resultFetcher->hasFurtherResults()
		];

		$urlArgs->delete( 'title' );

		$filterCards = $this->templateParser->processTemplate(
			'cards',
			[
				'property-filter-card' => $this->facetBuilder->getPropertyFilterFacet( $title, $urlArgs ),
				'category-filter-card' => $this->facetBuilder->getCategoryFilterFacet( $title, $urlArgs ),
				'value-filter-cards' => $this->facetBuilder->getValueFilterFacets( $title, $urlArgs )
			]
		);

		$searchOptions = $this->templateParser->processTemplate(
			'options',
			[
				'count' => $params['count'],
				'size-title' => $this->msg( 'smw-facetedsearch-size-options' ),
				'order-title' => $this->msg( 'smw-facetedsearch-order-options' ),
				'format-title' => $this->msg( 'smw-facetedsearch-format-options' ),
				'format-options' => $this->optionsBuilder->format( $urlArgs->get( 'format', '' ) ),
				'size-options' => $this->optionsBuilder->size( $urlArgs->getInt( 'size', 0 ) ),
				'order-options' => $this->optionsBuilder->order( $urlArgs->get( 'order', 'asc' ) ),
				'limit' => $params['limit'] + $params['offset'],
				'offset' => max( $params['offset'], 1 ),
				'previous' => $this->optionsBuilder->previous( $urlArgs->getInt( 'size', 0 ), $params['offset'] ),
				'next' => $this->optionsBuilder->next( $urlArgs->getInt( 'size', 0 ), $params['offset'], $params['hasFurtherResults'] ),
			]
		);

		$hidden = '';

		// Remember the "cstate" (aka card state) over the period of one
		// request by adding hidden elements to the form
		foreach ( $urlArgs->getArray( 'cstate', [] ) as $key => $value ) {
			$hidden .= '<input name="' . "cstate[$key]" . '" type="hidden" value="' . $value . '">';
		}

		$searchForm = $this->templateParser->processTemplate(
			'search',
			[
				'action' => $title->getLocalUrl(),
				'method' => 'get',
				'q' => htmlspecialchars( $urlArgs->get( 'q', '' ) ),
				'csum' => crc32( $urlArgs->get( 'q', '' ) ),
				'limit' => $params['limit'],
				'offset' => $params['offset'],
				'search-label' => $this->msg( 'smw-ask-search' ),
				'search-placeholder-label' => $this->msg( 'smw-search-placeholder' ),
				'profile-title' => $this->msg( 'smw-facetedsearch-profile-options' ),
				'profile-select-disabled' => $this->profile->getProfileCount() > 1 ? '' : 'disabled',
				'profile-options' => $this->optionsBuilder->profiles( $urlArgs->get( 'profile', '' ) ),
				'hidden' => $hidden
			]
		);

		$debug = '';

		if ( $this->profile->get( 'debug_output' ) ) {

			$queryString = str_replace(
				[ '<', '>', '=' ],
				[ '&lt;', '&gt;', '0x003D' ],
				$this->resultFetcher->getQueryString()
			);

			$debug = '<pre>' . $queryString . '</pre>';
		}

		$content = $this->templateParser->processTemplate(
			'content',
			[
				'debug' => $debug,
				'options' => $searchOptions,
				'results' => $result
			]
		);

		$sidebar = $this->templateParser->processTemplate(
			'sidebar',
			[
				'cards' => $filterCards
			]
		);

		return $this->templateParser->processTemplate(
			'container',
			[
				'search'  => $searchForm,
				'search-extra-fields' => $this->extraFieldBuilder->buildHTML( $urlArgs ),
				'sidebar' => $sidebar,
				'content' => $content,
				'theme' => $this->profile->get( 'theme' )
			]
		);
	}

}
