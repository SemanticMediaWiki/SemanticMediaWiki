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
		global $wgOut, $wgLang;

		$wgOut->setPageTitle( wfMessage( 'semanticstatistics' )->text() );

		$semanticStatistics = smwfGetStore()->getStatistics();
	
		$dbr = wfGetDB( DB_SLAVE );
		
		$propertyPageAmount = $dbr->estimateRowCount(
			'page',
			'*',
			array(
				'page_namespace' => SMW_NS_PROPERTY
			)
		);
	
		$out = wfMessage( 'smw_semstats_text' )->numParams(	$semanticStatistics['PROPUSES'],
			$semanticStatistics['USEDPROPS'], $propertyPageAmount, $semanticStatistics['DECLPROPS']
		)->parseAsBlock();

		$wgOut->addHTML( $out );
	}
}
