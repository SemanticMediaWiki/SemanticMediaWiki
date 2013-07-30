<?php

namespace SMW;

use SMWOutputs;

/**
 * Special page (Special:WantedProperties) for MediaWiki shows all
 * wanted properties
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
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
class SpecialWantedProperties extends SpecialPage {

	/**
	 * @see SpecialPage::__construct
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		parent::__construct( 'WantedProperties' );
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $param ) {
		Profiler::In( __METHOD__ );

		$out = $this->getOutput();

		$out->setPageTitle( $this->msg( 'wantedproperties' )->text() );

		$page = new WantedPropertiesQueryPage( $this->getStore(), $this->getSettings() );
		$page->setContext( $this->getContext() );

		list( $limit, $offset ) = wfCheckLimits();
		$page->doQuery( $offset, $limit );

		// Ensure locally collected output data is pushed to the output!
		// ?? still needed !!
		SMWOutputs::commitToOutputPage( $out );

		Profiler::Out( __METHOD__ );
	}

}
