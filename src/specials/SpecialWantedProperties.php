<?php

namespace SMW;

use SMWOutputs;

/**
 * Special page (Special:WantedProperties) for MediaWiki shows all
 * wanted properties
 *
 *
 * @license GPL-2.0-or-later
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
		$this->setHeaders();

		$out = $this->getOutput();

		$out->addModuleStyles( [
			'ext.smw.special.styles'
		] );

		$out->setPageTitle( $this->msg( 'wantedproperties' )->text() );

		$page = new WantedPropertiesQueryPage( $this->getStore(), $this->getSettings() );
		$page->setContext( $this->getContext() );
		$page->setTitle( $this->getPageTitle() );

		[ $limit, $offset ] = $this->getLimitOffset();
		$page->doQuery( $offset, $limit );

		// Ensure locally collected output data is pushed to the output!
		// ?? still needed !!
		SMWOutputs::commitToOutputPage( $out );
	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName() {
		return 'smw_group/properties-concepts-types';
	}

	private function getLimitOffset() {
		$request = $this->getRequest();
		return $request->getLimitOffsetForUser( $this->getUser() );
	}

}
