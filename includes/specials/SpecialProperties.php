<?php

namespace SMW;

use SMWOutputs;

/**
 * Special page (Special:Properties) for MediaWiki shows all
 * used properties
 *
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */

/**
 * This special page for MediaWiki shows all used properties.
 *
 * @ingroup SpecialPage
 */
class SpecialProperties extends SpecialPage {

	/**
	 * @see SpecialPage::__construct
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		parent::__construct( 'Properties' );
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $param ) {
		Profiler::In( __METHOD__ );

		$out = $this->getOutput();

		$out->setPageTitle( $this->msg( 'properties' )->text() );

		$page = new PropertiesQueryPage( $this->getStore(), $this->getSettings() );
		$page->setContext( $this->getContext() );

		list( $limit, $offset ) = $this->getLimitOffset();
		$page->doQuery( $offset, $limit, $this->getRequest()->getVal( 'property' ) );

		// Ensure locally collected output data is pushed to the output!
		SMWOutputs::commitToOutputPage( $out );

		Profiler::Out( __METHOD__ );
	}

	/**
	 * FIXME MW 1.24 wfCheckLimits was deprecated in MediaWiki 1.24
	 */
	private function getLimitOffset() {

		if ( method_exists( $this->getRequest(), 'getLimitOffset' ) ) {
			return $this->getRequest()->getLimitOffset();
		}

		return wfCheckLimits();
	}

}
