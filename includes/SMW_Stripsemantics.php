<?php
/**
 * This file contains methods that can be registered on
 * appropriate hooks in order to strip out all semantic
 * annotations before storing wikitext in the database.
 * It is then inserted whenever the sources are fetched
 * for editing. Using these mehtods is optional.
 * 
 * TODO: This code needs reworking to adjust to changes
 * in the storage interface.
 *
 * @author Markus Krötzsch
 * @author Klaus Lassleben
 */
 
	require_once('SMW_storage.php');
	
	global $wgDBprefix;
	global $glSemanticRelations;
	global $glTableName;
	$glTableName = $wgDBprefix . 'semantic_relations';

	/*******************************************************************************
	H   H  OOO   OOO  K   K  SSS  
	H   H O   O O   O K  K  S     
	HHHHH O   O O   O KKK    SSS  
	H   H O   O O   O K  K      S 
	H   H  OOO   OOO  K   K  SSS  
	*******************************************************************************/ 
	/*
	*  This method will be called after a specific revision (version)
	*  of an article was read from the databases. It is switched off
	*  by default, and is only needed when semantic annotations are 
	*  stripped from the wiki text before being stored in the database.
	*  In the latter case, this method recombines plain wiki texts with
	*  the stored semantic data to obtain annotated texts for editing.
	*  
	*  WARNING: This feature is broken, since it is not needed at the 
	*  moment and many changes have been made in the storage architecture
	*  since its last use.
	*  
	*/
	function OnRevisionAfterGetRevisionText(&$text)
	{
		checkSemanticRelationsTable();
		$semanticLinkPattern = '(\[\[([^\|\]]*)(\|([^\]]*))?\]\])';
		$text = preg_replace_callback($semanticLinkPattern, 'OnRevisionAfterGetRevisionTextCallback', $text);
		return true;
	}
 
	/*
	 *  This method will be called before an article is saved, but
	 *  is not used in the current implementation. It is the counter
	 *  part of the method OnRevisionAfterGetRevisionText, i.e. it
	 *  strips the semantic relations from the article and return the 
	 *  standard wiki links, so that there will will be no confusion 
	 *  in the original database tables.
	 *  
	 *  WARNING: This feature is broken, since it is not needed at the 
	 *  moment and many changes have been made in the storage architecture
	 *  since its last use.
	 */
	function OnArticleSave(&$article, &$user, &$text)
	{
		checkSemanticRelationsTable();
		$title = $article->getTitle();
		SMWDeleteTriples($title->getText());
		$semanticLinkPattern = '(\[\[(([^]]*)::)+([^\|\]]*)(\|([^]]*))?\]\])';
		$text = preg_replace_callback($semanticLinkPattern, 'OnArticleSaveCallback', $text);
		return true;
	}
	
	/*******************************************************************************
	 CCC   AAA  L     L     BBBB   AAA   CCC  K   K  SSS  
	C     A   A L     L     B   B A   A C     K  K  S     
	C     AAAAA L     L     BBBB  AAAAA C     KKK    SSS  
	C     A   A L     L     B   B A   A C     K  K      S 
	 CCC  A   A LLLLL LLLLL BBBB  A   A  CCC  K   K  SSS  
	*******************************************************************************/
	
	/*
	* This callback function will lookup semantic relations for a specific wiki-link
	* in the separate table for semantic relations. If there are semantic relations
	* they will be 'injected' into the link.
	*/
	function OnRevisionAfterGetRevisionTextCallback($link)
	{
		global $wgTitle;
		global $glTableName;

		$linkTarget = $link[1];
		$linkCaption = $link[2];

		$fname = 'HookSemanticParser::OnParserBeforeStripCallback';
		$db =& wfGetDB( DB_MASTER );

	$sql = 'SELECT relation
				FROM ' . $glTableName .'
				WHERE subject = \'' . $wgTitle->getText() . '\'
				AND object = \'' . $linkTarget . '\'';
	$res = $db->query( $sql, $fname );

	$semanticRelation = '';
		if($db->numRows( $res ) > 0)
		{
			$row = $db->fetchObject($res);
			while($row)
			{
				$semanticRelation .= $row->relation . '::';
				$row = $db->fetchObject($res);
			}
			$db->freeResult($res);
		}

		if($linkCaption == null)
		{
		   $linkCaption = '';
		}
		$resultLink = '[[' . $semanticRelation . $linkTarget . $linkCaption . ']]';
		return $resultLink;
	}
	
	/*
	* This callback function strips out semantic relations from wiki links.
	* The stripped relations are be stored in the separate relations table.
	*/
	function OnArticleSaveCallback($semanticLink)
	{
		global $wgTitle;
		global $glTableName;
		
		$semanticRelations = $semanticLink[2];
		$linkTarget = $semanticLink[3];
		$linkCaption = $semanticLink[4];

		$fname = 'HookSemanticParser::OnArticleSaveCallback';
		$db =& wfGetDB( DB_MASTER );

		// Look if a semantic relation like this already exists,
		// and insert it if not
		$semanticRelationsArray = explode('::', $semanticRelations);
		foreach($semanticRelationsArray as $semanticRelation)
		{
			$sql = 'SELECT * FROM ' . $glTableName . '
						WHERE subject = \'' . $wgTitle->getText() . '\'
						AND relation = \'' . $semanticRelation . '\'
						AND object = \'' . $linkTarget . '\'';
			$res = $db->query( $sql, $fname );
			if($db->numRows( $res ) > 0)
			{
				$db->freeResult($res);
			}
			else
			{
				$sql = 'INSERT INTO ' . $glTableName . '
							(subject, relation, object)
							VALUES (
							\'' . $wgTitle->getText() . '\',
							\'' . $semanticRelation . '\',
							\'' . $linkTarget . '\'
							)';
				$res = $db->query( $sql, $fname );
			}
		}
	   
	   $resultLink = '[[' . $linkTarget . $linkCaption . ']]';
	   return $resultLink;
	}

 
?>