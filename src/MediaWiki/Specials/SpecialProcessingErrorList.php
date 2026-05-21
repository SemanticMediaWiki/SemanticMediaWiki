<?php

namespace SMW\MediaWiki\Specials;

use MediaWiki\SpecialPage\SpecialPage;
use SMW\Settings;

/**
 * Convenience special page that just redirects to Special:Ask with a preset
 * of necessary parameters to query the processing error list.
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class SpecialProcessingErrorList extends SpecialPage {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly Settings $settings
	) {
		parent::__construct( 'ProcessingErrorList' );
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $query ): bool {
		$limit = $this->settings->dotGet( 'smwgPagingLimit.errorlist' );

		$this->getOutput()->redirect(
			$this->getLocalAskRedirectUrl( $limit )
		);

		return true;
	}

	/**
	 * @since 2.5
	 *
	 * @param int $limit
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
				'limit'  => $limit,
				'bTitle' => 'processingerrorlist',
				'bHelp'  => 'smw-processingerrorlist-helplink',
				'bMsg'   => 'smw-processingerrorlist-intro'
			]
		);
	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName(): string {
		return 'smw_group/maintenance';
	}

}
