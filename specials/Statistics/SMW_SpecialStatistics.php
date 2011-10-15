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
		
		$wgOut->setPageTitle( wfMsg( 'semanticstatistics' ) );
		
		$semanticStatistics = smwfGetStore()->getStatistics();
	
		$dbr = wfGetDB( DB_SLAVE );
		
		$propertyPageAmount = $dbr->estimateRowCount(
			'page',
			'*',
			array(
				'page_namespace' => SMW_NS_PROPERTY
			)
		);
	
		$out = wfMsgExt( 'smw_semstats_text', array( 'parse' ),
			$wgLang->formatNum( $semanticStatistics['PROPUSES'] ), $wgLang->formatNum( $semanticStatistics['USEDPROPS'] ),
			$wgLang->formatNum( $propertyPageAmount ), $wgLang->formatNum( $semanticStatistics['DECLPROPS'] )
		);
	
		$wgOut->addHTML( $out );
	}
	
}
