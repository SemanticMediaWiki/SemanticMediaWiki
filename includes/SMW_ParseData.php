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
			$output->mSMWData = new SMWSemanticData(SMWWikiPageValue::makePageFromTitle($title));
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
		$output->mSMWData = new SMWSemanticData(SMWWikiPageValue::makePageFromTitle($title));
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
		$property = SMWPropertyValue::makeUserProperty($propertyname);
		$result = SMWDataValueFactory::newPropertyObjectValue($property,$value,$caption);
		if ($storeannotation && (SMWParseData::getSMWData($parser) !== NULL)) {
			SMWParseData::getSMWData($parser)->addPropertyObjectValue($property,$result);
			if (!$result->isValid()) { // take note of the error for storage (do this here and not in storage, thus avoiding duplicates)
				SMWParseData::getSMWData($parser)->addPropertyObjectValue(SMWPropertyValue::makeProperty('_ERRP'),$property->getWikiPageValue());
			}
		}
		wfProfileOut("SMWParseData::addProperty (SMW)");
		return $result;
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
	 *  @param $parseroutput ParserOutput object that contains the results of parsing which will
	 *  be stored.
	 *  @param $title Title object specifying the page that should be safed.
	 *  @param $makejobs Bool stating whether jobs should be created to trigger further updates if
	 *  this appears to be necessary after this update.
	 *
	 *  @bug Some job generations here might create too many jobs at once on a large wiki. Use incremental jobs instead.
	 */
	static public function storeData($parseroutput, Title $title, $makejobs = true) {
		global $smwgEnableUpdateJobs, $wgContLang, $smwgMW_1_14;
		$semdata = $parseroutput->mSMWData;
		$namespace = $title->getNamespace();
		$processSemantics = smwfIsSemanticsProcessed($namespace);
		if (!isset($semdata)) { // no data at all?
			$semdata = new SMWSemanticData(SMWWikiPageValue::makePageFromTitle($title));
		}
		if ($processSemantics) {
			$pmdat = SMWPropertyValue::makeProperty('_MDAT');
			if ( count($semdata->getPropertyValues($pmdat)) == 0  ) { // no article data present yet, add it here
				$timestamp =  $smwgMW_1_14?Revision::getTimeStampFromID($title, $title->getLatestRevID()):Revision::getTimeStampFromID($title->getLatestRevID());
				$dv = SMWDataValueFactory::newPropertyObjectValue($pmdat,  $wgContLang->sprintfDate('d M Y G:i:s',$timestamp));
				$semdata->addPropertyObjectValue($pmdat,$dv);
			}
		} else { // data found, but do all operations as if it was empty
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
			$ptype = SMWPropertyValue::makeProperty('_TYPE');
			$oldtype = smwfGetStore()->getPropertyValues($title, $ptype);
			$newtype = $semdata->getPropertyValues($ptype);

			if (!SMWParseData::equalDatavalues($oldtype, $newtype)) {
				$updatejobflag = true;
			} else {
				$ppval = SMWPropertyValue::makeProperty('_PVAL');
				$oldvalues = smwfGetStore()->getPropertyValues($semdata->getSubject(), $ppval);
				$newvalues = $semdata->getPropertyValues($ppval);
				$updatejobflag = !SMWParseData::equalDatavalues($oldvalues, $newvalues);
			}

			if ($updatejobflag) {
				$prop = SMWPropertyValue::makeProperty($title->getDBkey());
				$subjects = smwfGetStore()->getAllPropertySubjects($prop);
				foreach ($subjects as $subject) {
					$jobs[] = new SMWUpdateJob($subject->getTitle());
				}
				$subjects = smwfGetStore()->getPropertySubjects(SMWPropertyValue::makeProperty('_ERRP'), $prop->getWikiPageValue());
				foreach ($subjects as $subject) {
					$jobs[] = new SMWUpdateJob($subject->getTitle());
				}
			}
		} elseif ($makejobs && $smwgEnableUpdateJobs && ($namespace == SMW_NS_TYPE) ) {
			// if it is a type we need to check if the conversion factors have been changed
			$pconv = SMWPropertyValue::makeProperty('_CONV');
			$ptype = SMWPropertyValue::makeProperty('_TYPE');
			$oldfactors = smwfGetStore()->getPropertyValues($semdata->getSubject(), $pconv);
			$newfactors = $semdata->getPropertyValues($pconv);
			$updatejobflag = !SMWParseData::equalDatavalues($oldfactors, $newfactors);
			if ($updatejobflag) {
				$store = smwfGetStore();
				/// FIXME: this will kill large wikis! Use incremental updates!
				$dv = SMWDataValueFactory::newTypeIdValue('__typ',$title->getDBkey());
				$proppages = $store->getPropertySubjects($ptype, $dv);
				foreach ($proppages as $proppage) {
					$jobs[] = new SMWUpdateJob($proppage->getTitle());
					$prop = SMWPropertyValue::makeProperty($proppage->getDBkey());
					$subjects = $store->getAllPropertySubjects($prop);
					foreach ($subjects as $subject) {
						$jobs[] = new SMWUpdateJob($subject->getTitle());
					}
					$subjects = smwfGetStore()->getPropertySubjects(SMWPropertyValue::makeProperty('_ERRP'), $prop->getWikiPageValue());
					foreach ($subjects as $subject) {
						$jobs[] = new SMWUpdateJob($subject->getTitle());
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
	 * Hook function fetches category information and other final settings from parser output,
	 * so that they are also replicated in SMW for more efficient querying.
	 */
	static public function onParserAfterTidy(&$parser, &$text) {
		if (SMWParseData::getSMWData($parser) === NULL) return true;
		$categories = $parser->mOutput->getCategoryLinks();
		foreach ($categories as $name) {
			$pinst = SMWPropertyValue::makeProperty('_INST');
			$dv = SMWDataValueFactory::newPropertyObjectValue($pinst);
			$dv->setValues($name,NS_CATEGORY);
			SMWParseData::getSMWData($parser)->addPropertyObjectValue($pinst,$dv);
			if (SMWParseData::getSMWData($parser)->getSubject()->getNamespace() == NS_CATEGORY) {
				$psubc = SMWPropertyValue::makeProperty('_SUBC');
				$dv = SMWDataValueFactory::newPropertyObjectValue($psubc);
				$dv->setValues($name,NS_CATEGORY);
				SMWParseData::getSMWData($parser)->addPropertyObjectValue($psubc,$dv);
			}
		}
		$sortkey = ($parser->mDefaultSort?$parser->mDefaultSort:SMWParseData::getSMWData($parser)->getSubject()->getText());
		SMWParseData::getSMWData($parser)->getSubject()->setSortkey($sortkey);
		return true;
	}

	/**
	 * Fetch additional information that is related to the saving that has just happened,
	 * e.g. regarding the last edit date. In runs where this hook is not triggered, the
	 * last DB entry (of MW) will be used to fill such properties.
	 *
	 * @note This method directly accesses a member of Article that is informally declared to
	 * be private. However, there is no way to otherwise access an article's parseroutput for
	 * the purpose of adding information there. If the private access ever becomes a problem,
	 * a global/static variable appears to be the only way to get more article data to
	 * LinksUpdate.
	 */
	static public function onNewRevisionFromEditComplete($article, $rev, $baseID) {
		global $wgContLang;
		if ( ($article->mPreparedEdit) && ($article->mPreparedEdit->output instanceof ParserOutput)) {
			$output = $article->mPreparedEdit->output;
			$title = $article->getTitle();
			if (!isset($title)) return true; // nothing we can do
			if (!isset($output->mSMWData)) { // no data container yet, make one
				$output->mSMWData = new SMWSemanticData(SMWWikiPageValue::makePageFromTitle($title));
			}
			$semdata = $output->mSMWData;
		} else { // give up, just keep the old data
			return true;
		}
		$pmdat = SMWPropertyValue::makeProperty('_MDAT');
		$dv = SMWDataValueFactory::newPropertyObjectValue($pmdat,  $wgContLang->sprintfDate('d M Y G:i:s',$article->getTimestamp()));
		$semdata->addPropertyObjectValue($pmdat,$dv);
		return true;
	}

	/**
	 * Used to updates data after changes of templates, but also at each saving of an article.
	 */
	static public function onLinksUpdateConstructed($links_update) {
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
	static public function onArticleDelete(&$article, &$user, &$reason) {
		smwfGetStore()->deleteSubject($article->getTitle());
		return true; // always return true, in order not to stop MW's hook processing!
	}

	/**
	 *  This method will be called whenever an article is moved so that
	 *  semantic properties are moved accordingly.
	 */
	static public function onTitleMoveComplete(&$old_title, &$new_title, &$user, $pageid, $redirid) {
		smwfGetStore()->changeTitle($old_title, $new_title, $pageid, $redirid);
		return true; // always return true, in order not to stop MW's hook processing!
	}

}
