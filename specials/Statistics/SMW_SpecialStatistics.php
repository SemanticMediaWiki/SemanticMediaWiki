<?php

/**
 * File holding the SMWSpecialSemanticStatistics class for the Special:SemanticStatistics page.
 *
 * @file SMW_SpecialStatistics.php
 *
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 *
 * @author Daniel M. Herzig
 * @author Jeroen De Dauw
 */
class SMWSpecialSemanticStatistics extends SpecialPage {

	public function __construct() {
		parent::__construct( 'SemanticStatistics' );
	}

	public function execute( $param ) {
		$semanticStatistics = smwfGetStore()->getStatistics();
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
}
