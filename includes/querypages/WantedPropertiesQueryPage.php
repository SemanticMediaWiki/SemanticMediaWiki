<?php

namespace SMW;

use Html;

/**
 * Query class that provides content for the Special:WantedProperties page
 *
 * @ingroup QueryPage
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class WantedPropertiesQueryPage extends QueryPage {

	/** @var Store */
	protected $store;

	/** @var Settings */
	protected $settings;

	/**
	 * @var ListLookup
	 */
	private $listLookup;

	/**
	 * @since 1.9
	 *
	 * @param Store $store
	 * @param Settings $settings
	 */
	public function __construct( Store $store, Settings $settings ) {
		$this->store = $store;
		$this->settings = $settings;
	}

	/**
	 * @codeCoverageIgnore
	 * @return string
	 */
	function getName() {
		return "WantedProperties";
	}

	/**
	 * @codeCoverageIgnore
	 * @return boolean
	 */
	function isExpensive() {
		return false; /// disables caching for now
	}

	/**
	 * @codeCoverageIgnore
	 * @return boolean
	 */
	function isSyndicated() {
		return false; ///TODO: why not?
	}

	/**
	 * @codeCoverageIgnore
	 * @return string
	 */
	function getPageHeader() {
		return Html::element( 'p', array(), $this->msg( 'smw_wantedproperties_docu' )->text() );
	}

	/**
	 * @param $skin
	 * @param array $result First item is SMWDIProperty, second item is int
	 *
	 * @return string
	 */
	function formatResult( $skin, $result ) {
		// Only display user-defined properties because it can happen that
		// custom predefined (fixed) properties are mixed within the result
		// (did not use their own fixedProperty table and therefore were
		// selected as well e.g _SF_PDF etc.)
		if ( !$result[0] instanceof DIProperty || !$result[0]->isUserDefined() ) {
			return '';
		}

		$title = $result[0]->getDiWikiPage()->getTitle();

		if ( !$title instanceof \Title ) {
			return '';
		}

		$proplink = $this->getLinker()->link(
			$title,
			htmlspecialchars( $result[0]->getLabel() ),
			array( 'action' => 'view' )
		);

		return $this->msg( 'smw_wantedproperty_template' )
			->rawParams( $proplink )
			->params( $result[1] )
			->escaped();
	}

	/**
	 * Get the list of results.
	 *
	 * @param SMWRequestOptions $requestOptions
	 * @return array of SMWDIProperty|SMWDIError
	 */
	function getResults( $requestoptions ) {
		$this->listLookup = $this->store->getWantedPropertiesSpecial( $requestoptions );
		return $this->listLookup->fetchList();
	}
}
