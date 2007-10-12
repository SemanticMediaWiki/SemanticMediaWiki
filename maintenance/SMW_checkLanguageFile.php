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
 */

$langloc = '../extensions/SemanticMediaWiki/languages/SMW_Language';
$lcs = array( 'De', 'Es', 'Fr', 'He', 'Nl', 'Pl', 'Ru', 'Sk');

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
	
	private function checkarray( $A , $B ) {
		$printed = false;
		foreach ( array_keys( $A ) as $i ) {
			if ( ! array_key_exists( $i , $B ) ) {
				if (!$printed) {
					print "\tMissing:\n";
					$printed = true;
				}
				print "\t\t" . $i . "\n";
			}
		}
		$printed = false;
		foreach ( array_keys( $B ) as $i ) {
			if ( ! array_key_exists( $i , $A ) ) {
				if (!$printed) {
					print "\tSuperfluous:\n";
					$printed = true;
				}
				print "\t\t" . $i . "\n";
			}
		}
	}
	
	public function check($lc) {
		include_once( $this->langloc . $lc . '.php' );
		$classname = 'SMW_Language' . $lc;
		$lang = new  $classname();
		
		$A = $this->base->getContentMsgArray();
		$B = $lang->getContentMsgArray();
		print "Checking contentmessages...\n";
		$this->checkarray( $A , $B );

		$A = $this->base->getUserMsgArray();
		$B = $lang->getUserMsgArray();
		print "Checking usermessages...\n";
		$this->checkarray( $A , $B );

		$A = $this->base->getSpecialPropertiesArray();
		$B = $lang->getSpecialPropertiesArray();
		print "Checking special properties...\n";
		$this->checkarray( $A , $B );

		$A = $this->base->getDatatypeLabels();
		$B = $lang->getDatatypeLabels();
		print "Checking datatypes...\n";
		$this->checkarray( $A , $B );

		$A = $this->base->getNamespaces();
		$B = $lang->getNamespaces();
		print "Checking namespaces...\n";
		$this->checkarray( $A , $B );
	}

}

$checker = new SMW_LanguageChecker( $langloc );

if ( $lc == "") {
	foreach ( $lcs as $lc ) {
		print "\nChecking language " . $lc . "\n";
		$checker->check( $lc );	
	}		
} else {
	$checker->check( $lc );
}

?>