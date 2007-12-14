<?php

/**
 * Usage:
 * php SMW_checkLanguageFile.php [options...]
 *
 * Checks if all fields that are defined in the English language file are
 * also available in the other language files.
 *
 * -l <language code>  which language to check
 *                     if omitted it checks all.
 * 
 * @author Denny Vrandečić
 */

$langloc = '../extensions/SemanticMediaWiki/languages/SMW_Language';
$lcs = array( 'De', 'Es', 'Fr', 'He', 'Ko', 'Nl', 'Pl', 'Ru', 'Sk', 'Zh_cn', 'Zh_tw');

$optionsWithArgs = array( 'l' ); // -l <language code>
require_once( 'commandLine.inc' );

if ( !empty( $options['l'] ) ) {
	$lc = $options['l'];
} else {
	$lc = "";
}

class SMW_LanguageChecker  {
	
	private $base;
	private $langloc;
	
	public function SMW_LanguageChecker($langloc, $baselc = 'En') {
		$this->langloc = $langloc;
		include_once( $langloc . $baselc . '.php' );
		$classname = 'SMW_Language' . $baselc;
		$this->base = new $classname();
	}
	
	private function checkarray( $field, $A , $B ) {
		$diff = false;
		$printed = false;
		foreach ( array_keys( $A ) as $i ) {
			if ( ! array_key_exists( $i , $B ) ) {
				if (!$printed) {
					print "Checking " . $field . "...\n";
					print "\tMissing:\n";
					$printed = true;
					$diff = true;
				}
				print "\t\t" . $i . "\n";
			}
		}
		$printed = false;
		foreach ( array_keys( $B ) as $i ) {
			if ( ! array_key_exists( $i , $A ) ) {
				if (!$printed) {
					if (!$diff) {
						print "Checking " . $field . "...\n";
						$diff = true;
					}
					print "\tSuperfluous:\n";
					$printed = true;
				}
				print "\t\t" . $i . "\n";
			}
		}
		return $diff;
	}
	
	public function check($lc) {
		include_once( $this->langloc . $lc . '.php' );
		$classname = 'SMW_Language' . $lc;
		$lang = new  $classname();
		
		$A = $this->base->getContentMsgArray();
		$B = $lang->getContentMsgArray();
		$this->checkarray( "contentmessages", $A , $B );

		$A = $this->base->getUserMsgArray();
		$B = $lang->getUserMsgArray();
		$this->checkarray( "usermessages", $A , $B );

		$A = $this->base->getSpecialPropertiesArray();
		$B = $lang->getSpecialPropertiesArray();
		$this->checkarray( "special properties", $A , $B );

		$A = $this->base->getDatatypeLabels();
		$B = $lang->getDatatypeLabels();
		$this->checkarray( "datatypes", $A , $B );

		$A = $this->base->getNamespaces();
		$B = $lang->getNamespaces();
		$this->checkarray( "namespaces", $A , $B );
	}

}

$checker = new SMW_LanguageChecker( $langloc );

if ( $lc == "") {
	foreach ( $lcs as $lc ) {
		print "== Checking language " . $lc . " ==\n";
		$checker->check( $lc );	
	}		
} else {
	$checker->check( $lc );
}

?>