<?php

namespace SMW\MediaWiki\Specials\FacetedSearch;

use SMW\Localizer\Message;
use SMW\Localizer\MessageLocalizerTrait;
use SMW\Utils\TemplateEngine;
use SMW\Utils\UrlArgs;
use Title;

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
	 * @var TemplateEngine
	 */
	private $templateEngine;

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
	 * @param TemplateEngine $templateEngine
	 * @param OptionsBuilder $optionsBuilder
	 * @param ExtraFieldBuilder $extraFieldBuilder
	 * @param FacetBuilder $facetBuilder
	 * @param ResultFetcher $resultFetcher
	 * @param ExploreListBuilder $exploreListBuilder
	 */
	public function __construct( Profile $profile, TemplateEngine $templateEngine, OptionsBuilder $optionsBuilder, ExtraFieldBuilder $extraFieldBuilder, FacetBuilder $facetBuilder, ResultFetcher $resultFetcher, ExploreListBuilder $exploreListBuilder ) {
		$this->profile = $profile;
		$this->templateEngine = $templateEngine;
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

		$this->templateEngine->compile(
			'search-form',
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

		$this->templateEngine->compile(
			'intro',
			[
				'text' => $this->msg( 'smw-facetedsearch-intro-text', Message::PARSE ),
				'tips' => $this->msg( 'smw-facetedsearch-intro-tips', Message::PARSE )
			]
		);

		if ( ( $html = $this->exploreListBuilder->buildHTML( $title ) ) === '' ) {
			$html = $this->templateEngine->publish( 'intro' );
		}

		$this->templateEngine->compile(
			'facetedsearch-container-empty',
			[
				'search' => $this->templateEngine->publish( 'search-form' ),
				'search-extra-fields' => $this->extraFieldBuilder->buildHTML( $urlArgs ),
				'intro' => $html,
				'theme' => $this->profile->get( 'theme' )
			]
		);

		return $this->templateEngine->publish( 'facetedsearch-container-empty' );
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

		$this->templateEngine->compile(
			'filter-cards',
			[
				'property-filter-card' => $this->facetBuilder->getPropertyFilterFacet( $title, $urlArgs ),
				'category-filter-card' => $this->facetBuilder->getCategoryFilterFacet( $title, $urlArgs ),
				'value-filter-cards' => $this->facetBuilder->getValueFilterFacets( $title, $urlArgs )
			]
		);

		$this->templateEngine->compile(
			'search-options',
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

		$this->templateEngine->compile(
			'search-form',
			[
				'action' => $title->getLocalUrl(),
				'method' => 'get',
				'q' => $urlArgs->get( 'q', '' ),
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

		$this->templateEngine->compile(
			'facetedsearch-content',
			[
				'debug' => $debug,
				'options' => $this->templateEngine->publish( 'search-options', TemplateEngine::HTML_TIDY ),
				'results' => $result
			]
		);

		$this->templateEngine->compile(
			'facetedsearch-sidebar',
			[
				'cards' => $this->templateEngine->publish( 'filter-cards', TemplateEngine::HTML_TIDY )
			]
		);

		$this->templateEngine->compile(
			'facetedsearch-container',
			[
				'search'  => $this->templateEngine->publish( 'search-form', TemplateEngine::HTML_TIDY ),
				'search-extra-fields' => $this->extraFieldBuilder->buildHTML( $urlArgs ),
				'sidebar' => $this->templateEngine->publish( 'facetedsearch-sidebar' ),
				'content' => $this->templateEngine->publish( 'facetedsearch-content' ),
				'theme' => $this->profile->get( 'theme' )
			]
		);

		return $this->templateEngine->publish( 'facetedsearch-container' );
	}

}
