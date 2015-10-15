<?php

namespace SMW;

/**
 * Class for the Special:SemanticStatistics page
 *
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author Daniel M. Herzig
 * @author Jeroen De Dauw
 */

/**
 * Class for the Special:SemanticStatistics page
 *
 * @ingroup SpecialPage
 */
class SpecialSemanticStatistics extends SpecialPage {

	/**
	 * @see SpecialPage::__construct
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		parent::__construct( 'SemanticStatistics' );
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $param ) {

		$semanticStatistics = $this->getStore()->getStatistics();
		$context = $this->getContext();
		$out = $this->getOutput();

		$out->setPageTitle( $context->msg( 'semanticstatistics' )->text() );
		$out->addHTML( $context->msg( 'smw_semstats_text'
			)->numParams(
				$semanticStatistics['PROPUSES'],
				$semanticStatistics['USEDPROPS'],
				$semanticStatistics['OWNPAGE'],
				$semanticStatistics['DECLPROPS']
			)->parseAsBlock()
		);
	}

	protected function getGroupName() {
		return 'wiki';
	}
}
