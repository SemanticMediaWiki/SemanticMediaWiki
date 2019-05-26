<?php

namespace SMW\MediaWiki\Specials;

use SMW\ApplicationFactory;
use SpecialPage;

/**
 * Convenience special page that just redirects to Special:Ask with a preset
 * of necessary parameters to query the constraint error list.
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SpecialConstraintErrorList extends SpecialPage {

	/**
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		parent::__construct( 'ConstraintErrorList' );
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $query ) {

		$settings = ApplicationFactory::getInstance()->getSettings();
		$limit = $settings->dotGet( 'smwgPagingLimit.errorlist' );

		$this->getOutput()->redirect(
			$this->findRedirectURL( $limit )
		);

		return true;
	}

	/**
	 * @since 3.1
	 *
	 * @param integer $limit
	 *
	 * @return string
	 */
	public function findRedirectURL( $limit = 20 ) {
		return SpecialPage::getTitleFor( 'Ask' )->getLocalUrl(
			[
				'q'      => '[[Has processing error text::+]] [[Processing error type::constraint]]',
				'po'     => '?Has improper value for|?Has processing error text',
				'p'      => 'class=sortable-20smwtable-2Dstriped-20smwtable-2Dclean/sep=ul',
				'eq'     => 'no',
				'limit'  =>  $limit,
				'bTitle' => 'constrainterrorlist',
				'bHelp'  => 'smw-constrainterrorlist-helplink',
				'bMsg'   => 'smw-constrainterrorlist-intro'
			]
		);
	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName() {

		if ( version_compare( MW_VERSION, '1.33', '<' ) ) {
			return 'smw_group';
		}

		// #3711, MW 1.33+
		return 'smw_group/maintenance';
	}

}
