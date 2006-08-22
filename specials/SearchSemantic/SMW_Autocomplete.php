<?php
// $Id: SMW_Autocomplete.php,v 1.1 2006/02/01 15:13:18 mkroetzsch Exp $
/**
* This is a remote script to call from Javascript
*/
define ('JPSPAN_ERROR_DEBUG',TRUE);

require_once 'jpspan/JPSpan.php';
require_once JPSPAN . 'Server/PostOffice.php';

define('MEDIAWIKI', true);
require_once( '../includes/Defines.php' );
require_once( '../LocalSettings.php' );
require_once( '../includes/Setup.php' );

//-----------------------------------------------------------------------------------
class Autocomplete2
{
	function getSemanticRelations()
	{
		global $glTableName;
		
		$fname = 'Autocomplete::getSemanticRelations';
		$db =& wfGetDB( DB_MASTER );

   	$sql = 'SELECT DISTINCT(relation)
   				FROM ' . $glTableName . '
   				ORDER BY relation';
   	$res = $db->query( $sql, $fname );

   	$semanticRelations = array();
		if($db->numRows( $res ) > 0)
		{
			$row = $db->fetchObject($res);
			while($row)
			{
				$semanticRelations[] = $row->relation;
				$row = $db->fetchObject($res);
			}
			$db->freeResult($res);
		}
		return $semanticRelations;
	}
}

$S = & new JPSpan_Server_PostOffice();
$S->addHandler(new Autocomplete2());

//-----------------------------------------------------------------------------------
// Generates the Javascript client by adding ?client to the server URL
//-----------------------------------------------------------------------------------
if (isset($_SERVER['QUERY_STRING']) && strcasecmp($_SERVER['QUERY_STRING'], 'client')==0) {
    // Compress the Javascript
    // define('JPSPAN_INCLUDE_COMPRESS',TRUE);
    $S->displayClient();
    
//-----------------------------------------------------------------------------------
} else {
    
    // Include error handler - PHP errors, warnings and notices serialized to JS
    require_once JPSPAN . 'ErrorHandler.php';
    $S->serve();

}

#?>
