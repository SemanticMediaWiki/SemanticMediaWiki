<?php

namespace SMW\MediaWiki\Specials;

use SMW\ApplicationFactory;
use SpecialPage;

/**
 * Convenience special page that just redirects to Special:Ask with a preset
 * of necessary parameters to query the processing error list.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SpecialProcessingErrorList extends SpecialPage {

	/**
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		parent::__construct( 'ProcessingErrorList' );
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $query ) {

		$limit = ApplicationFactory::getInstance()->getSettings()->dotGet( 'smwgPagingLimit.errorlist' );

		$this->getOutput()->redirect(
			$this->getLocalAskRedirectUrl( $limit )
		);

		return true;
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $limit
	 *
	 * @return string
	 */
	public function getLocalAskRedirectUrl( $limit = 20 ) {
		return SpecialPage::getTitleFor( 'Ask' )->getLocalUrl(
			[
				'q'      => '[[Has processing error text::+]]',
				'po'     => '?Has improper value for|?Has processing error text',
				'p'      => 'class=sortable-20smwtable-2Dstriped-20smwtable-2Dclean/sep=ul',
				'eq'     => 'no',
				'limit'  =>  $limit,
				'bTitle' => 'processingerrorlist',
				'bHelp'  => 'smw-processingerrorlist-helplink',
				'bMsg'   => 'smw-processingerrorlist-intro'
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
