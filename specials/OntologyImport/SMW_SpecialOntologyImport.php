<?php
/**
 * @author Denny Vrandecic
 * @author Markus Krötzsch
 *
 * This special page for MediaWiki provides an interface
 * that allows to import an ontology.
 * @file
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 * @todo The code in this file is still very very messy and undocumented. Cleanup needed!
 */

//ToDo: Please refactor into a real class we can autoload
wfLoadExtensionMessages('SemanticMediaWiki');
SpecialPage::addPage( new SpecialPage('OntologyImport','import',true,'doSpecialImportOntology',false) );

function doSpecialImportOntology($par = NULL) {
	global $smwgIP;
	require_once($smwgIP . '/includes/SMW_Storage.php');

	global $wgOut, $wgRequest, $wgUser;
	global $wgServer; // "http://www.yourserver.org"
						// (should be equal to 'http://'.$_SERVER['SERVER_NAME'])
	global $wgScript;   // "/subdirectory/of/wiki/index.php"

	if ( ! $wgUser->isAllowed('import') ) {
		$wgOut->permissionRequired('import');
		return;
	}

	/**** Execute actions if any ****/

	$action = $wgRequest->getText( 'action' );
	$message='';
	if ( $action=='displayontology' ) {
		$tempOut = $wgOut;
		$message = SMWOntologyImport::displayontology();
		$wgOut = $tempOut;
	}

	if ( $action=='importstatements' ) {
		$tempOut = $wgOut;
		$message = SMWOntologyImport::importstatements();
		$wgOut = $tempOut;
	}

	/**** Output ****/

	wfLoadExtensionMessages('SemanticMediaWiki');

	// only report success/failure after an action
	if ( $message!='' ) {
		$skin = $wgUser->getSkin();
		$spcurl = $wgServer . $skin->makeSpecialUrl('OntologyImport');

		$html .= '<p>' . $message . '</p>';
		$html .= '<p>' . wfMsg('smw_oi_return',$spcurl) . '</p>';
		$wgOut->addHTML($html);
		return true;
	}

	// standard output interface
	$html = '<p><strong>' . wfMsg('smw_devel_warning') . '</strong></p>' .
			'<p>' . wfMsg('smw_oi_docu') . '</p>';
	// input forms
	$html .= '<form name="ontologyimport" action="" method="post" enctype="multipart/form-data">' . "\n" .
			'<input type="hidden" name="action" value="displayontology" />' . "\n" .
			'<input name="ontologyfile" type="file" size="50" /><br /><br />' . "\n" .
			'<input type="submit" value="' . wfMsg(smw_oi_action) . '" />' . "\n" .
			'</form>';

	$wgOut->addHTML($html);
	return true;
}


/** Static class to encapsulate import functions.
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 */
class SMWOntologyImport {
	/**
	 * Creates the label -- and thus the page title to be used -- for an entity.
	 */
	function getLabel($entity, $model) {
		// TODO look for language hints in the labels
		$labelstatement = $model->findFirstMatchingStatement($entity, RDFS::LABEL(), NULL, 0);
		if ($labelstatement != NULL) {
			$label = $labelstatement->getLabelObject();
		} else {
			$label = $entity->getLocalName();
		}

// Ugly hack to fix encoding problems on some servers
// 		$label = str_replace('_ue_','ü',$label);
// 		$label = str_replace('_oe_','ö',$label);
// 		$label = str_replace('_ae_','ä',$label);
// 		$label = str_replace('_ss_','ß',$label);
// 		$label = str_replace('_Ue_','Ü',$label);
// 		$label = str_replace('_Oe_','Ö',$label);
// 		$label = str_replace('_Ae_','Ä',$label);

		return $label;
	}

	/**
	 * Turns the triples of an individual into both a human-readable description and wiki source
	 */
	function createArticleStatements($entity, $model) {
		$statements = array();

		$sLabel = SMWOntologyImport::getLabel($entity, $model);
		$st = Title::newFromText( $sLabel , NS_MAIN );
		if ($st == NULL) continue; // Could not create a title, next please
		if ($st->exists()) {
			$sclassnew = '';
		} else {
			$sclassnew = 'class="new" ';
		}

		// instantiated relations and attributes
		$it  = $model->findAsIterator($entity, NULL, NULL);
		while ($it->hasNext()) {
			$statement = $it->next();
			$property = $statement->getPredicate();
			$object = $statement->getObject();
			$propertyIsRelation = ($model->find($property, RDF::TYPE(), OWL::OBJECT_PROPERTY()));
			$propertyIsAttribute = ($model->find($property, RDF::TYPE(), OWL::DATATYPE_PROPERTY()));
			if (!$propertyIsRelation->isEmpty()) {
				$pLabel = SMWOntologyImport::getLabel($property, $model);
				$pt = Title::newFromText( $pLabel , SMW_NS_RELATION );
				if ($pt == NULL) continue; // Could not create a title, next please
				if ($pt->exists()) {
					$pclassnew = '';
				} else {
					$pclassnew = 'class="new" ';
				}
				$oLabel = SMWOntologyImport::getLabel($object, $model);
				$ot = Title::newFromText( $oLabel , NS_MAIN );
				if ($ot == NULL) continue; // Could not create a title, next please
				if ($ot->exists()) {
					$oclassnew = '';
				} else {
					$oclassnew = 'class="new" ';
				}
				if (count(smwfGetRelations($st,$pt,$ot))!=0) continue; 
				// TODO: I changed this from "==0" to "!=0". Not sure what this is supposed to do, and why no corresponding statement is found in the code for attributes. Denny, please have a look at this. -- mak
				$s = array();
				$s['HUMAN'] = '<a href="'. $st->getLocalURL() .'" '. $sclassnew .'title="'. $st->getPrefixedText() .'">'. $st->getPrefixedText() .'</a> <a href="'. $pt->getLocalURL() .'" '. $pclassnew .'title="'. $pt->getPrefixedText() .'">'. $pt->getText() .'</a> <a href="'. $ot->getLocalURL() .'" '. $oclassnew .'title="'. $ot->getPrefixedText() .'">'. $ot->getPrefixedText() .'</a>';
				$s['WIKI'] = $st->getText() . " [[:" . $pt->getText() . "::" . $ot->getPrefixedText() . "]].";
				$statements[] = $s;
			}
			if (!$propertyIsAttribute->isEmpty()) {
				$pLabel = SMWOntologyImport::getLabel($property, $model);
				$pt = Title::newFromText( $pLabel , SMW_NS_PROPERTY );
				if ($pt == NULL) continue; // Could not create a title, next please
				if ($pt->exists()) {
					$pclassnew = '';
				} else {
					$pclassnew = 'class="new" ';
				}
				$oLabel = $object->getLabel();
				// TODO check if already within wiki
				// TODO use datatype handler
				$s = array();
				$s['HUMAN'] = '<a href="'. $st->getLocalURL() .'" '. $sclassnew .'title="'. $st->getPrefixedText() .'">'. $st->getPrefixedText() .'</a> <a href="'. $pt->getLocalURL() .'" '. $pclassnew .'title="'. $pt->getPrefixedText() .'">'. $pt->getText() .'</a> '. $oLabel;
				$s['WIKI'] = $st->getText() . " [[:" . $pt->getText() . ":=" . $oLabel . "]].";
				$statements[] = $s;
			}
		}

		wfLoadExtensionMessages('SemanticMediaWiki');
		// categories
		$it  = $model->findAsIterator($entity, RDF::TYPE(), NULL);
		while ($it->hasNext()) {
			$statement = $it->next();
			$concept = $statement->getObject();
			$label = SMWOntologyImport::getLabel($concept, $model);
			$t = Title::newFromText( $label , NS_CATEGORY );
			if (smwfInCategory($st, $t)) continue;
			if ($t == NULL) continue; // Could not create a title, next please
			$s = array();
			if ($t->exists()) {
				$classnew = '';
			} else {
				$classnew = 'class="new" ';
			}
			$s['HUMAN'] = wfMsg( 'smw_oi_thishascategory' ) . ' <a href="'. $t->getLocalURL() .'" '. $classnew .'title="'. $t->getPrefixedText() .'">'. $t->getPrefixedText() .'</a>' . "\n";
			$s['WIKI'] = "[[" . $t->getPrefixedText() . "]]" . "\n";
			$statements[] = $s;
		}

		return $statements;
	}

	/**
	 * Turns the triples of a class into both a human-readable description and wiki source
	 */
	function createCategoryStatements($entity, $model) {
		$statements = array();

		$it  = $model->findAsIterator($entity, RDFS::SUB_CLASS_OF(), NULL);

		$slabel = SMWOntologyImport::getLabel($entity, $model);
		$st = Title::newFromText( $slabel , NS_CATEGORY );
		if ($st == NULL) continue; // Could not create a title, next please

		wfLoadExtensionMessages('SemanticMediaWiki'); 
		while ($it->hasNext()) {
			$statement = $it->next();
			$superclass = $statement->getObject();
			$label = SMWOntologyImport::getLabel($superclass, $model);
			$t = Title::newFromText( $label , NS_CATEGORY );
			if ($t == NULL) continue; // Could not create a title, next please
			if (smwfInCategory($st, $t)) continue;
			$s = array();
			if ($t->exists()) {
				$classnew = '';
			} else {
				$classnew = 'class="new" ';
			}
			$s['HUMAN'] = wfMsg( 'smw_oi_thisissubcategoryof' ) . ' <a href="'. $t->getLocalURL() .'" '. $classnew .'title="'. $t->getPrefixedText() .'">'. $t->getPrefixedText() .'</a>' . "\n";
			$s['WIKI'] = "[[" . $t->getPrefixedText() . "]]" . "\n";
			$statements[] = $s;
		}

		return $statements;
	}

	// TODO switched off for the time being, because domain and range and subproperty are not yet part of SMW
	/**
	 * Turns the triples of an object property into both a human-readable description and wiki source
	 */
	function createRelationText($entity, $model) {
		global $wgContLang;
		
		$text = '';

		$it  = $model->findAsIterator($entity, RDFS::DOMAIN(), NULL);
		while ($it->hasNext()) {
			$statement = $it->next();
			$object = $statement->getObject();
			$label = SMWOntologyImport::getLabel($object, $model);
			$text .= "[[domain::" . $wgContLang->getNsText(NS_CATEGORY) . ":" . $label . "]]" . "\n";
		}

		$it  = $model->findAsIterator($entity, RDFS::RANGE(), NULL);
		while ($it->hasNext()) {
			$statement = $it->next();
			$object = $statement->getObject();
			$label = SMWOntologyImport::getLabel($object, $model);
			$text .= "[[range::" . $wgContLang->getNsText(NS_CATEGORY) . ":" . $label . "]]" . "\n";
		}

		// TODO subpropertyof

		return $text;
	}

	// TODO switched off for the time being, because domain and subproperty are not yet part of SMW
	// TODO implement type
	/**
	 * Turns the triples of a datatype property into both a human-readable description and wiki source
	 */
	function createAttributeText($entity, $model) {
		global $wgContLang;
		$text = '';

		$it  = $model->findAsIterator($entity, RDFS::DOMAIN(), NULL);
		while ($it->hasNext()) {
			$statement = $it->next();
			$object = $statement->getObject();
			$label = SMWOntologyImport::getLabel($object, $model);
			$text .= "[[domain::" . $wgContLang->getNsText(NS_CATEGORY) . ":" . $label . "]]" . "\n";
		}

		// TODO subpropertyof
		// TODO type

		return $text;
	}

	/**
	 * Common parsing of an entity
	 */
	function createStatements($entity, $ns, $model) {
		$statements = array();

		wfLoadExtensionMessages('SemanticMediaWiki');

		$it  = $model->findAsIterator($entity, RDFS::COMMENT(), NULL);
		while ($it->hasNext()) {
			$comment = $it->next();
			$text = $comment->getLabelObject();
			$statements[] = array('HUMAN' => wfMsg( 'smw_oi_comment' ) . " " . $text, 'WIKI' => $text);
		}

		// add semantic informations, based on the type
		$s = array();
		if (NS_MAIN == $ns)
			$s = SMWOntologyImport::createArticleStatements($entity, $model);
		if (NS_CATEGORY == $ns)
			$s = SMWOntologyImport::createCategoryStatements($entity, $model);
		//if (SMW_NS_RELATION == $ns)
			//$s = SMWOntologyImport::createRelationStatements($entity, $model);
		//if (SMW_NS_ATTRIBUTE == $ns)
			//$s = SMWOntologyImport::createAttributeStatements($entity, $model);

		foreach ($s as $stat) {
			$statements[] = $stat;
		}

		return $statements;
	}

	/**
	 * Is responsible for displaying the statements of an entity in the selection screen
	 */
	function displayEntity($entity, $ns, $model, $enr) {
		$message = '';

		$label = SMWOntologyImport::getLabel($entity, $model);

		$t = Title::newFromText( $label , $ns );
		if ($t == NULL) return $message; // Could not create a title, whimper and return

		$exists = $t->exists();
		if ($exists) {
			$classnew = '';
		} else {
			$classnew = 'class="new" ';
		}

		$statements = SMWOntologyImport::createStatements($entity, $ns, $model);
		$need_to_map = (count(smwfGetSpecialProperties($t, SMW_SP_HAS_URI, $entity->getURI()))==0);
		if ((count($statements) == 0) && !$need_to_map) return ''; // nothing to add

		// TODO $message .= '<input type="checkbox" />'; one click to click all statements about an entity
		wfLoadExtensionMessages('SemanticMediaWiki');
		$message .= wfMsg( 'smw_oi_statementsabout' ) . ' <a href="'. $t->getLocalURL() .'" '. $classnew .'title="'. $t->getPrefixedText() .'">'. $t->getPrefixedText() .'</a> <br />' . "\n";

		$snr = 0;
		if ($need_to_map) {
			$value = $ns . ':' . $t->getDBkey() . '::[[equivalent URI:=' . $entity->getURI() . '| ]]'; // TODO internationalize equivalent URI
			$message .= '&nbsp; <input type="checkbox" name="s' . $enr . '_' . $snr++ . '" value="' . $value . '" />' . wfMsg( 'smw_oi_mapto' ) . ' <em><a href="' . $entity->getURI() . '" title="' . $entity->getURI() . '">' . $entity->getURI() . '</a></em> <br />' . "\n";
		}

		foreach ($statements as $statement) {
			$message .= '&nbsp; <input type="checkbox" name="s' . $enr . '_' . $snr++ . '" value="' . $ns . ':' . $t->getDBkey() . '::' . $statement['WIKI'] . '" />' . $statement['HUMAN'] . "<br />" . "\n";
		}

		return $message . '<br />' . "\n";
	}

	/**
	 * Imports statements into the wiki code of the appropriate pages. This is called by a POST on this special,
	 * and could theoretically also be abused from the outside... (proper rights being given)
	 */
	function importstatements() {
		$message = "";
		$prolog = $_POST['textforall'];
		if ('' != $prolog) $prolog .= "\n";
		$keys = array_keys($_POST);
		$oldsubject = '';
		$changesns = array();
		foreach ($keys as $key) {
			if ('action'==$key) continue;
			if ('textforall'==$key) continue;
			list( $temp , $text ) = explode("::", $_POST[$key], 2);
			list( $ns , $subject ) = explode(":", $temp, 2);
			if (!array_key_exists( $ns , $changesns )) {
				$changesns[$ns] = array();
			}
			if (!array_key_exists( $subject , $changesns[$ns] )) {
				$changesns[$ns][$subject] = $prolog;
			}
			$changesns[$ns][$subject] .= $text . "\n";
		}

		wfLoadExtensionMessages('SemanticMediaWiki');

		$nskeys = array_keys($changesns);
		foreach ($nskeys as $ns) {
			$changes = $changesns[$ns];
			$subkeys = array_keys($changes);
			foreach ($subkeys as $subject) {
				$text = $changes[$subject];
				$title = Title::makeTitle( $ns , $subject );
				if (NULL == $title) continue;
				if ($title->exists()) {
					$article = new Article($title);
					$oldtext = $article->getContent();
					$article->updateArticle( $oldtext . "\n" . "\n" . $text, wfMsg( 'smw_oi_importedfromontology' ), FALSE, FALSE );
				} else {
					$newArticle = new Article($title);
					$newArticle->insertNewArticle( $text, wfMsg( 'smw_oi_importedfromontology' ), FALSE, FALSE, FALSE, FALSE );
					smwfSaveHook($newArticle, $newArticle, $newArticle);
				}
			}
		}

		global $wgDeferredUpdateList;
		foreach ($wgDeferredUpdateList as $u) $u->doUpdate();
		$wgDeferredUpdateList = array();

		return $message;
	}

	/**
	 * Displays a whole ontology and lets the user choose which statements to import
	 */
	function displayontology() {
		global $smwgRAPPath;
		$Rdfapi_includes= $smwgRAPPath . '/api/';
		define("RDFAPI_INCLUDE_DIR", $Rdfapi_includes); // not sure if the constant is needed within RAP
		include(RDFAPI_INCLUDE_DIR . "RdfAPI.php");

		global $wgRequest;
		$file = $_FILES['ontologyfile']['tmp_name'];

		wfLoadExtensionMessages('SemanticMediaWiki');

		if ($file=='') {
			$message = '<strong> ' . wfMsg( 'smw_oi_noontology' ) . '</strong> <br /></p>' . "\n";
		} else {
			$model = ModelFactory::getDefaultModel();
			$model->load($file);
			$message = '';

			// this is just for debugging
			#$model->writeAsHtmlTable();

			include( RDFAPI_INCLUDE_DIR . 'vocabulary/RDF_C.php');
			include( RDFAPI_INCLUDE_DIR . 'vocabulary/OWL_C.php');
			include( RDFAPI_INCLUDE_DIR . 'vocabulary/RDFS_C.php');

			$message .= '<strong>' . wfMsg( 'smw_oi_select' ) . '</strong> <br /><br />' . "\n";

			$message .= '<form name="selectstatements" action="" method="post">' . "\n" .
						'<input type="hidden" name="action" value="importstatements" />' . "\n" .
						wfMsg( 'smw_oi_textforall' ) . ' <input type="text" name="textforall" size="50" /> <br /><br />' . "\n";
						//.'<input type="checkbox" name="checkall" />' . wfMsg( 'smw_oi_selectall' ) . '<br /><br />' . "\n";
						// TODO make it possible to select all checkboxes with one click


			$enr = 0;
			// this imports rdfs classes. But it is hard to import instances later,
			// so it is commented out. You should rather change your rdfs ontology
			// to an owl ontology, really.
			//$it  = $model->findAsIterator(NULL, RDF::TYPE(), RDFS::RDFS_CLASS());
			//while ($it->hasNext()) {
			//	$statement = $it->next();
			//	$subject = $statement->getSubject();
			//	$message .= SMWOntologyImport::displayEntity($subject, NS_CATEGORY, $model, $enr++);
			//}

			$it  = $model->findAsIterator(NULL, RDF::TYPE(), OWL::OWL_CLASS());
			while ($it->hasNext()) {
				$statement = $it->next();
				$subject = $statement->getSubject();
				$message .= SMWOntologyImport::displayEntity($subject, NS_CATEGORY, $model, $enr++);
			}

			$it  = $model->findAsIterator(NULL, RDF::TYPE(), OWL::OBJECT_PROPERTY());
			while ($it->hasNext()) {
				$statement = $it->next();
				$subject = $statement->getSubject();
				$message .= SMWOntologyImport::displayEntity($subject, SMW_NS_RELATION, $model, $enr++);
			}

			$it  = $model->findAsIterator(NULL, RDF::TYPE(), OWL::DATATYPE_PROPERTY());
			while ($it->hasNext()) {
				$statement = $it->next();
				$subject = $statement->getSubject();
				$message .= SMWOntologyImport::displayEntity($subject, SMW_NS_PROPERTY, $model, $enr++);
			}

			$it  = $model->findAsIterator(NULL, RDF::TYPE(), NULL);
			while ($it->hasNext()) {
				$statement = $it->next();
				$subject = $statement->getSubject();
				$object = $statement->getObject();
				$objOWLClass = ($model->find($object, RDF::TYPE(), OWL::OWL_CLASS()));
				if (!$objOWLClass->isEmpty())
					$message .= SMWOntologyImport::displayEntity($subject, NS_MAIN, $model, $enr++);
			}

		}
			$message .= '<input type="submit" value="' . wfMsg(smw_oi_action) . '" />' . "\n" .
						'</form><p>&nbsp;';

		return $message;
	}

}

/**
 * Checks if an article is in a category. Returns true if yes, and false else.
 * Works also to check if a category is a subcategory of the second.
 */
function smwfInCategory($article, $category) {
	if (!$article->exists()) return FALSE; // this was the easy part :)

	$categories = $article->getParentCategories();
	if ('' == $categories) return FALSE;
	
	$catkeys = array_keys($categories);
	
	foreach($catkeys as $cat) {
		if ($category->getPrefixedDBKey() == $cat) {
			return TRUE;
		}
	}

	return FALSE;
}

