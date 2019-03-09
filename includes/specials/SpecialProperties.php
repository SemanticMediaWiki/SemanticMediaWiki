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
		$this->setHeaders();
		$out = $this->getOutput();

		$out->setPageTitle( $this->msg( 'properties' )->text() );

		$page = new PropertiesQueryPage( $this->getStore(), $this->getSettings() );
		$page->setContext( $this->getContext() );

		list( $limit, $offset ) = $this->getLimitOffset();
		$page->doQuery( $offset, $limit, $this->getRequest()->getVal( 'property' ) );

		// Ensure locally collected output data is pushed to the output!
		SMWOutputs::commitToOutputPage( $out );

	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName() {

		if ( version_compare( MW_VERSION, '1.33', '<' ) ) {
			return 'smw_group';
		}

		// #3711, MW 1.33+
		return 'smw_group/properties-concepts-types';
	}

	private function getLimitOffset() {
		return $this->getRequest()->getLimitOffset();
	}

}
