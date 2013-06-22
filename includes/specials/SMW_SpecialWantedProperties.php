<?php

/**
 * Special page (Special:WantedProperties) for MediaWiki shows all
 * wanted properties
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.9
 *
 * @file
 *
 * @license GNU GPL v2+
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */

/**
 * This special page (Special:WantedProperties) for MediaWiki shows all wanted
 * properties (used but not having a page).
 *
 * @ingroup SpecialPage
 */
class SMWSpecialWantedProperties extends SpecialPage {

	/**
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		parent::__construct( 'WantedProperties' );
	}

	public function execute( $param ) {
		\SMW\Profiler::In( __METHOD__ );

		$out = $this->getOutput();

		$out->setPageTitle( $this->msg( 'wantedproperties' )->text() );

		$page = new SMWWantedPropertiesPage(
			\SMW\StoreFactory::getStore(),
			\SMW\Settings::newFromGlobals()
		);
		$page->setContext( $this->getContext() );

		list( $limit, $offset ) = wfCheckLimits();
		$page->doQuery( $offset, $limit );

		// Ensure locally collected output data is pushed to the output!
		// ?? still needed !!
		SMWOutputs::commitToOutputPage( $out );

		\SMW\Profiler::Out( __METHOD__ );
	}

}

/**
 * This query page shows all wanted properties.
 *
 * @ingroup SpecialPage
 *
 * @author Markus Krötzsch
 */
class SMWWantedPropertiesPage extends SMWQueryPage {

	/** @var Store */
	protected $store;

	/** @var Settings */
	protected $settings;

	/** @var Collector */
	protected $collector;

	/**
	 * @since 1.9
	 *
	 * @param Store $store
	 * @param Settings $settings
	 */
	public function __construct( \SMW\Store $store, \SMW\Settings $settings ) {
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

		$linker   = smwfGetLinker();
		$proplink = '';

		// Only display user-defined properties because it can happen that
		// custom predefined (fixed) properties are mixed within the result
		// (did not use their own fixedProperty table and therefore were
		// selected as well e.g _SF_PDF etc.)
		if ( $result[0]->isUserDefined() ) {
			$proplink = $linker->link(
				$result[0]->getDiWikiPage()->getTitle(),
				htmlspecialchars( $result[0]->getLabel() ),
				array( 'action' => 'view' )
			);
		}

		return $proplink ? $this->msg( 'smw_wantedproperty_template', $proplink, $result[1] )->text() : '';
	}

	/**
	 * Get the list of results.
	 *
	 * @param SMWRequestOptions $requestOptions
	 * @return array of SMWDIProperty|SMWDIError
	 */
	function getResults( $requestoptions ) {
		$this->collector = $this->store->getWantedPropertiesSpecial( $requestoptions );
		return $this->collector->getResults();
	}
}
