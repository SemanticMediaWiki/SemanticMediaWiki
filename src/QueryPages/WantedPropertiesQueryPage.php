<?php

namespace SMW\QueryPages;

use MediaWiki\Html\Html;
use MediaWiki\Title\Title;
use SMW\DataItems\Property;
use SMW\RequestOptions;
use SMW\SQLStore\Lookup\ListLookup;
use SMW\Store;

/**
 * Query class that provides content for the Special:WantedProperties page
 *
 * @ingroup QueryPage
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class WantedPropertiesQueryPage extends QueryPage {

	protected Store $store;

	/**
	 * @var ListLookup
	 */
	private $listLookup;

	private Title $title;

	/**
	 * @since 1.9
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @param Title $title
	 *
	 * @return void
	 */
	public function setTitle( Title $title ): void {
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return "WantedProperties";
	}

	/**
	 * @return bool
	 */
	public function isExpensive(): bool {
		// disables caching for now
		return false;
	}

	/**
	 * @return bool
	 */
	public function isSyndicated(): bool {
		// TODO: why not?
		return false;
	}

	/**
	 * Returns available cache information (takes into account user preferences)
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getCacheInfo() {
		if ( $this->listLookup->isFromCache() ) {
			return $this->msg( 'smw-sp-properties-cache-info', $this->getLanguage()->userTimeAndDate( $this->listLookup->getTimestamp(), $this->getUser() ) )->parse();
		}

		return '';
	}

	/**
	 * @return string
	 */
	public function getPageHeader(): string {
		$filer = $this->getRequest()->getVal( 'filter', '' );

		if ( $filer !== 'unapprove' ) {
			$label = $this->msg( 'smw-special-wantedproperties-filter-unapproved' )->text();
			$title = $this->msg( 'smw-special-wantedproperties-filter-unapproved-desc' )->text();
		} else {
			$label = $this->msg( 'smw-special-wantedproperties-filter-none' )->text();
			$title = '';
		}

		$filter = Html::rawElement(
			'div',
			[
				'class' => 'smw-special-filter'
			],
			$this->msg( 'smw-special-wantedproperties-filter-label' )->text() .
			'&nbsp;' .
			Html::rawElement(
				'span',
				[
					'class' => 'smw-special-filter-button',
					'title' => $title
				],
				Html::element(
					'a',
					[
						'href'  => $this->title->getLocalURL( [ 'filter' => $filer !== '' ? '' : 'unapprove' ] ),
						'rel'   => 'nofollow'
					],
					$label
				)
			)
		);

		return Html::rawElement(
			'p',
			[ 'class' => 'smw-wantedproperties-docu plainlinks' ],
			$this->msg( 'smw-special-wantedproperties-docu' )->parse()
		) . $this->getSearchForm( $this->getRequest()->getVal( 'property', '' ), $this->getCacheInfo(), false, $filter ) .
		Html::element(
			'h2',
			[],
			$this->msg( 'smw-sp-properties-header-label' )->text()
		);
	}

	/**
	 * @param $skin
	 * @param array $result First item is Property, second item is int
	 *
	 * @return string
	 */
	public function formatResult( $skin, $result ) {
		// Only display user-defined properties because it can happen that
		// custom predefined (fixed) properties are mixed within the result
		// (did not use their own fixedProperty table and therefore were
		// selected as well e.g _SF_PDF etc.)
		if ( !$result[0] instanceof Property || !$result[0]->isUserDefined() ) {
			return '';
		}

		$title = $result[0]->getDiWikiPage()->getTitle();

		if ( !$title instanceof Title ) {
			return '';
		}

		$proplink = $this->getLinker()->link(
			$title,
			htmlspecialchars( $result[0]->getLabel() ),
			[],
			[ 'action' => 'view' ]
		);

		return $this->msg( 'smw-special-wantedproperties-template' )
			->rawParams( $proplink )
			->params( $result[1] )
			->escaped();
	}

	/**
	 * Get the list of results.
	 *
	 * @param RequestOptions $requestOptions
	 * @return array of Property|Error
	 */
	public function getResults( $requestOptions ) {
		$this->listLookup = $this->store->getWantedPropertiesSpecial( $requestOptions );
		return $this->listLookup->fetchList();
	}
}

/**
 * @deprecated since 7.0.0
 */
class_alias( WantedPropertiesQueryPage::class, 'SMW\WantedPropertiesQueryPage' );
