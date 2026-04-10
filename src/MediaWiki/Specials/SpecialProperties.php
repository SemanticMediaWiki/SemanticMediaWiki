<?php

namespace SMW\MediaWiki\Specials;

use SMW\MediaWiki\Outputs;
use SMW\QueryPages\PropertiesQueryPage;

/**
 * Special page (Special:Properties) for MediaWiki shows all
 * used properties
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
 * This special page for MediaWiki shows all used properties.
 *
 * @ingroup SpecialPage
 */
class SpecialProperties extends SpecialPage {

	/**
	 * @see SpecialPage::__construct
	 */
	public function __construct() {
		parent::__construct( 'Properties' );
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $param ): void {
		$this->setHeaders();
		$out = $this->getOutput();

		$out->setPageTitle( $this->msg( 'properties' )->text() );

		$page = new PropertiesQueryPage( $this->getStore(), $this->getSettings() );
		$page->setContext( $this->getContext() );

		$request = $this->getRequest();
		$hasCursor = $request->getInt( 'after' ) > 0
			|| $request->getInt( 'before' ) > 0;

		if ( $hasCursor ) {
			$limit = $request->getLimitOffsetForUser( $this->getUser() )[0];
			$page->doQuery( 0, $limit, $request->getVal( 'property' ) );
		} else {
			[ $limit, $offset ] = $request->getLimitOffsetForUser( $this->getUser() );
			$page->doQuery( $offset, $limit, $request->getVal( 'property' ) );
		}

		// Ensure locally collected output data is pushed to the output!
		Outputs::commitToOutputPage( $out );
	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName(): string {
		return 'smw_group/properties-concepts-types';
	}

}

/**
 * @deprecated since 7.0.0
 */
class_alias( SpecialProperties::class, 'SMW\SpecialProperties' );
