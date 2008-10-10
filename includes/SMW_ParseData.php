<?php
/**
 * The class in this file manages semantic data collected during parsing of
 * an article.
 * @file
 * @ingroup SMW
 * @author Markus Krötzsch
 */

/**
 * Static class for managing semantic data collected during parsing, including some hooks
 * that can be used for updating and storing the data for some article. All methods
 * in this class are stateless: data is stored persistently only in a given parser
 * output. There is one exception: to provide a minimal compatibility with MediaWiki
 * up to version 1.13, the class keeps track of the latest ParserOutput that was
 * accessed. In this way, the ParserOutput can be reproduced when storing, since it
 * is not available as part of the storing LinkUpdate object in MediaWiki before 1.14.
 * @ingroup SMW
 */
class SMWParseData {

	/// ParserOutput last used. See documentation to SMWParseData.
	static public $mPrevOutput = NULL;

	/**
	 * Remove relevant SMW magic words from the given text and return
	 * an array of the names of all discovered magic words. Moreover,
	 * store this array in the current parser output, using the variable
	 * mSMWMagicWords.
	 */
	static public function stripMagicWords(&$text, $parser) {
		$words = array();
		$mw = MagicWord::get('SMW_NOFACTBOX');
		if ($mw->matchAndRemove($text)) {
			$words[] = 'SMW_NOFACTBOX';
		}
		$mw = MagicWord::get('SMW_SHOWFACTBOX');
		if ($mw->matchAndRemove($text)) {
			$words[] = 'SMW_SHOWFACTBOX';
		}
		$output = SMWParseData::getOutput($parser);
		$output->mSMWMagicWords = $words;
		return $words;
	}

	/**
	 * This function retrieves the SMW data from a given parser, and creates
	 * a new empty container if it is not initiated yet.
	 */
	static public function getSMWdata($parser) {
		$output = SMWParseData::getOutput($parser);
		$title = $parser->getTitle();
		if (!isset($output) || !isset($title)) return NULL; // no parsing, create error
		if (!isset($output->mSMWData)) { // no data container yet
			$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
			$dv->setTitle($title);
			$output->mSMWData = new SMWSemanticData($dv);
		}
		return $output->mSMWData;
	}

	/**
	 * Clear all stored data for a given parser.
	 */
	static public function clearStorage($parser) {
		$output = SMWParseData::getOutput($parser);
		$title = $parser->getTitle();
		if (!isset($output) || !isset($title)) return;
		$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
		$dv->setTitle($title);
		$output->mSMWData = new SMWSemanticData($dv);
	}

	/**
	 * This method adds a new property with the given value to the storage. It is
	 * intended to be used on user input, and property and value are sepcified by
	 * strings as they might be found in a wiki. The function returns a datavalue
	 * object that contains the result of the operation.
	 */
	static public function addProperty($propertyname, $value, $caption, $parser, $storeannotation = true) {
		wfProfileIn("SMWParseData::addProperty (SMW)");
		global $smwgContLang;
		// See if this property is a special one, such as e.g. "has type"
		$propertyname = smwfNormalTitleText($propertyname); //slightly normalize label
		$special = $smwgContLang->findSpecialPropertyID($propertyname);

		switch ($special) {
			case false: // normal property
				$result = SMWDataValueFactory::newPropertyValue($propertyname,$value,$caption);
				if ($storeannotation && (SMWParseData::getSMWData($parser) !== NULL)) {
					SMWParseData::getSMWData($parser)->addPropertyValue($propertyname,$result);
				}
				wfProfileOut("SMWParseData::addProperty (SMW)");
				return $result;
			default: // generic special property
				$result = SMWDataValueFactory::newSpecialValue($special,$value,$caption);
				if ($storeannotation && (SMWParseData::getSMWData($parser) !== NULL)) {
					SMWParseData::getSMWData($parser)->addSpecialValue($special,$result);
				}
				wfProfileOut("SMWParseData::addProperty (SMW)");
				return $result;
		}
	}


	/**
	 * This function takes care of storing the collected semantic data and takes
	 * care of clearing out any outdated entries for the processed page. It assume that
	 * parsing has happened and that all relevant data is contained in the provided parser
	 * output.
	 *
	 * Optionally, this function also takes care of triggering indirect updates that might be
	 * needed for overall database consistency. If the saved page describes a property or data type,
	 * the method checks whether the property type, the data type, the allowed values, or the 
	 * conversion factors have changed. If so, it triggers SMWUpdateJobs for the relevant articles,
	 * which then asynchronously update the semantic data in the database.
	 *
	 *  @todo Known bug/limitation:  Updatejobs are triggered when a property or type
	 *  definition has  changed, so that all affected pages get updated. However, if a
	 *  page uses a property but the given value caused an error, then there is no record
	 *  of that page using the property, so that it will not be updated. To fix this, one 
	 *  would need to store errors as well.
	 *
	 *  @param $parseroutput ParserOutput object that contains the results of parsing which will
	 *  be stored.
	 *  @param $title Title object specifying the page that should be safed.
	 *  @param $makejobs Bool stating whether jobs should be created to trigger further updates if
	 *  this appears to be necessary after this update.
	 *
	 *  @bug Some job generations here might create too many jobs at once on a large wiki. Use incremental jobs instead.
	 */
	static public function storeData($parseroutput, $title, $makejobs = true) {
		global $smwgEnableUpdateJobs;
		$semdata = $parseroutput->mSMWData;
		$namespace = $title->getNamespace();
		$processSemantics = smwfIsSemanticsProcessed($namespace);
		if (!isset($semdata)) { // no data at all?
			$dv = SMWDataValueFactory::newTypeIDValue('_wpg');
			$dv->setTitle($title);
			$semdata = new SMWSemanticData($dv);
		} elseif (!$processSemantics) { // data found, but do all operations as if it was empty
			$semdata = new SMWSemanticData($semdata->getSubject());
		}
	
		// Check if the semantic data has been changed.
		// Sets the updateflag to true if so.
		// Careful: storage access must happen *before* the storage update;
		// even finding uses of a property fails after its type was changed.
		$updatejobflag = false;
		$jobs = array();
		if ($makejobs && $smwgEnableUpdateJobs && ($namespace == SMW_NS_PROPERTY) ) {
			// if it is a property, then we need to check if the type or
			// the allowed values have been changed
			$oldtype = smwfGetStore()->getSpecialValues($title, SMW_SP_HAS_TYPE);
			$newtype = $semdata->getPropertyValues(SMW_SP_HAS_TYPE);
	
			if (!SMWParseData::equalDatavalues($oldtype, $newtype)) {
				$updatejobflag = true;
			} else {
				$oldvalues = smwfGetStore()->getSpecialValues($title, SMW_SP_POSSIBLE_VALUE);
				$newvalues = $semdata->getPropertyValues(SMW_SP_POSSIBLE_VALUE);
				$updatejobflag = !SMWParseData::equalDatavalues($oldvalues, $newvalues);
			}
	
			if ($updatejobflag) {
				$subjects = smwfGetStore()->getAllPropertySubjects($title);
				foreach ($subjects as $subject) {
					$jobs[] = new SMWUpdateJob($subject);
				}
			}
		} elseif ($makejobs && $smwgEnableUpdateJobs && ($namespace == SMW_NS_TYPE) ) {
			// if it is a type we need to check if the conversion factors have been changed
			$oldfactors = smwfGetStore()->getSpecialValues($title, SMW_SP_CONVERSION_FACTOR);
			$newfactors = $semdata->getPropertyValues(SMW_SP_CONVERSION_FACTOR);
			$updatejobflag = !SMWParseData::equalDatavalues($oldfactors, $newfactors);
			if ($updatejobflag) {
				$store = smwfGetStore();
				/// FIXME: this would kill large wikis! Use incremental updates!
				$dv = SMWDataValueFactory::newSpecialValue(SMW_SP_HAS_TYPE,$title->getDBkey());
				$subjects = $store->getSpecialSubjects(SMW_SP_HAS_TYPE, $dv);
				foreach ($subjects as $valueofpropertypagestoupdate) {
					$subjectsPropertyPages = $store->getAllPropertySubjects($valueofpropertypagestoupdate->getTitle());
					$jobs[] = new SMWUpdateJob($valueofpropertypagestoupdate->getTitle());
					foreach ($subjectsPropertyPages as $titleOfPageToUpdate) {
						$jobs[] = new SMWUpdateJob($titleOfPageToUpdate);
					}
				}
			}
		}
		// Actually store semantic data, or at least clear it if needed
		if ($processSemantics) {
			smwfGetStore()->updateData($semdata);
 		} else {
			smwfGetStore()->clearData($semdata->getSubject()->getTitle());
		}

		// Finally trigger relevant Updatejobs if necessary
		if ($updatejobflag) {
			Job::batchInsert($jobs); ///NOTE: this only happens if $smwgEnableUpdateJobs was true above
		}
		return true;
	}

	/**
	 * Helper function that compares two arrays of data values to check whether
	 * they contain the same content. Returns true if the two arrays contain the
	 * same data values (irrespective of their order), false otherwise.
	 */
	static public function equalDatavalues($dv1, $dv2) {
		// The hashes of all values of both arrays are taken, then sorted
		// and finally concatenated, thus creating one long hash out of each
		// of the data value arrays. These are compared.
		$values = array();
		foreach($dv1 as $v) $values[] = $v->getHash();
		sort($values);
		$dv1hash = implode("___", $values);
		$values = array();
		foreach($dv2 as $v) $values[] = $v->getHash();
		sort($values);
		$dv2hash = implode("___", $values);

		return ($dv1hash == $dv2hash);
	}

	/**
	 * Get the parser output from a parser object. The result is also stored
	 * in SMWParseData::$mPrevOutput for further reference.
	 */
	static protected function getOutput($parser) {
		if (method_exists($parser,'getOutput')) {
			SMWParseData::$mPrevOutput = $parser->getOutput();
		} else {
			SMWParseData::$mPrevOutput = $parser->mOutput;
		}
		return SMWParseData::$mPrevOutput;
	}

	/**
	 * Used to updates data after changes of templates, but also at each saving of an article.
	 */
	public static function onLinksUpdateConstructed($links_update) {
		if (isset($links_update->mParserOutput)) {
			$output = $links_update->mParserOutput;
		} else { // MediaWiki <= 1.13 compatibility
			$output = SMWParseData::$mPrevOutput;
			if (!isset($output)) {
				smwfGetStore()->clearData($links_update->mTitle, SMWFactbox::isNewArticle());
				return true;
			}
		}
		SMWParseData::storeData($output, $links_update->mTitle, true);
		return true;
	}
	
	/**
	 *  This method will be called whenever an article is deleted so that
	 *  semantic properties are cleared appropriately.
	 */
	public static function onArticleDelete(&$article, &$user, &$reason) {
		smwfGetStore()->deleteSubject($article->getTitle());
		return true; // always return true, in order not to stop MW's hook processing!
	}
	
	/**
	 *  This method will be called whenever an article is moved so that
	 *  semantic properties are moved accordingly.
	 */
	public static function onTitleMoveComplete(&$old_title, &$new_title, &$user, $pageid, $redirid) {
		smwfGetStore()->changeTitle($old_title, $new_title, $pageid, $redirid);
		return true; // always return true, in order not to stop MW's hook processing!
	}

}