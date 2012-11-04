<?php
/**
 * The class in this file manages semantic data collected during parsing of an
 * article.
 *
 * @author Markus Krötzsch
 *
 * @file
 * @ingroup SMW
 */

/**
 * Static class for managing semantic data collected during parsing, including
 * some hooks that can be used for updating and storing the data for some
 * article. All methods in this class are stateless: data is stored persistently
 * only in a given parser output.
 *
 * @ingroup SMW
 * @author Markus Krötzsch
 */
class SMWParseData {

	/**
	 * Remove relevant SMW magic words from the given text and return
	 * an array of the names of all discovered magic words. Moreover,
	 * store this array in the current parser output, using the variable
	 * mSMWMagicWords.
	 */
	static public function stripMagicWords( &$text, Parser $parser ) {
		$words = array();
		$mw = MagicWord::get( 'SMW_NOFACTBOX' );

		if ( $mw->matchAndRemove( $text ) ) {
			$words[] = 'SMW_NOFACTBOX';
		}

		$mw = MagicWord::get( 'SMW_SHOWFACTBOX' );

		if ( $mw->matchAndRemove( $text ) ) {
			$words[] = 'SMW_SHOWFACTBOX';
		}

		$output = $parser->getOutput();
		$output->mSMWMagicWords = $words;

		return $words;
	}

	/**
	 * This function retrieves the SMW data from a given parser, and creates
	 * a new empty container if it is not initiated yet.
	 *
	 * @param Parser $parser
	 *
	 * @return SMWSemanticData
	 */
	static public function getSMWdata( Parser $parser ) {
		$output = $parser->getOutput();
		$title = $parser->getTitle();

		// No parsing, create error.
		if ( !isset( $output ) || !isset( $title ) ) {
			return null;
		}

		$smwData = self::getSMWDataFromParserOutput( $output, $title );

		return $smwData;
	}

	/**
	 * @since 1.8
	 *
	 * @param ParserOutput $output
	 * @param Title|null $title
	 *
	 * @return SMWSemanticData|null
	 */
	public static function getSMWDataFromParserOutput( ParserOutput $output, Title $title = null ) {
		if ( method_exists( $output, 'getAdditionalData' ) ) {
			$smwData = $output->getAdditionalData( 'smwdata' );
		} elseif ( isset( $output->mSMWData ) ) {
			$smwData = $output->mSMWData;
		}

		// No data container yet:
		if ( !isset( $smwData ) ) {
			if ( $title === null ) {
				return null;
			}

			$smwData = new SMWSemanticData( SMWDIWikiPage::newFromTitle( $title ) );

			self::setSMWData( $output, $smwData );
		}

		return $smwData;
	}

	/**
	 * @since 1.8
	 *
	 * @param ParserOutput $output
	 * @param SMWSemanticData $smwData
	 */
	public static function setSMWData( ParserOutput $output, SMWSemanticData $smwData ) {
		if ( method_exists( $output, 'getAdditionalData' ) ) {
			$output->setAdditionalData( 'smwdata', $smwData );
		}
		else {
			$output->mSMWData = $smwData;
		}
	}

	/**
	 * Clear all stored data for a given parser.
	 *
	 * @param Parser $parser
	 */
	static public function clearStorage( Parser $parser ) {
		$title = $parser->getTitle();

		if ( !isset( $title ) ) {
			return;
		}

		self::setSMWData(
			$parser->getOutput(),
			new SMWSemanticData( SMWDIWikiPage::newFromTitle( $title ) )
		);
	}

	/**
	 * This method adds a new property with the given value to the storage. It is
	 * intended to be used on user input, and property and value are sepcified by
	 * strings as they might be found in a wiki. The function returns a datavalue
	 * object that contains the result of the operation.
	 *
	 * @param string $propertyName
	 * @param string $value
	 * @param mixed $caption string or false
	 * @param Parser $parser
	 * @param boolean $storeAnnotation
	 *
	 * @return SMWDataValue
	 */
	static public function addProperty( $propertyName, $value, $caption, Parser $parser, $storeAnnotation = true ) {
		wfProfileIn( 'SMWParseData::addProperty (SMW)' );

		// See if this property is a special one, such as e.g. "has type".
		$propertyDv = SMWPropertyValue::makeUserProperty( $propertyName );

		if ( !$propertyDv->isValid() ) {
			return $propertyDv;
		}

		$propertyDi = $propertyDv->getDataItem();

		// FIXME: this solves the issue of bug 29438, but is probably not what we want to do.
		if ( $propertyDi instanceof SMWDIError ) {
			return $propertyDv;
		}

		$semandticData = self::getSMWData( $parser );

		$result = SMWDataValueFactory::newPropertyObjectValue(
			$propertyDi,
			$value,
			$caption,
			$semandticData->getSubject()
		);

		if ( $propertyDi->isInverse() ) {
			$result->addError( wfMessage( 'smw_noinvannot' )->inContentLanguage()->text() );
		} elseif ( $storeAnnotation && !is_null( self::getSMWData( $parser ) ) ) {
			$semandticData->addPropertyObjectValue( $propertyDi, $result->getDataItem() );

			// Take note of the error for storage (do this here and not in storage, thus avoiding duplicates).
			if ( !$result->isValid() ) {
				$semandticData->addPropertyObjectValue(
					new SMWDIProperty( '_ERRP' ),
					$propertyDi->getDiWikiPage()
				);
			}
		}

		wfProfileOut( 'SMWParseData::addProperty (SMW)' );

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
	 * @param $parseroutput ParserOutput object that contains the results of parsing which will
	 * be stored.
	 * @param $title Title object specifying the page that should be saved.
	 * @param $makejobs Bool stating whether jobs should be created to trigger further updates if
	 * this appears to be necessary after this update.
	 *
	 * @todo FIXME: Some job generations here might create too many jobs at once on a large wiki. Use incremental jobs instead.
	 */
	static public function storeData( $parseroutput, Title $title, $makejobs = true ) {
		global $smwgEnableUpdateJobs, $smwgDeclarationProperties, $smwgPageSpecialProperties;

		$semdata = self::getSMWDataFromParserOutput( $parseroutput, $title );
		$namespace = $title->getNamespace();
		$processSemantics = smwfIsSemanticsProcessed( $namespace );

		if ( !isset( $semdata ) ) { // no data at all?
			$semdata = new SMWSemanticData( SMWDIWikiPage::newFromTitle( $title ) );
		}

		if ( $processSemantics ) {
			$props = array();

			foreach ( $smwgPageSpecialProperties as $propId ) {
				// Do not calculate the same property again.
				if ( array_key_exists( $propId, $props ) ) {
					continue;
				}

				// Remember the property is processed.
				$props[ $propId ] = true;
				$prop = new SMWDIProperty( $propId );

				if ( count( $semdata->getPropertyValues( $prop ) ) > 0  ) {
					continue;
				}

				// Calculate property value.
				$value = null;

				switch ( $propId ) {
					case '_MDAT' :
						$timestamp =  Revision::getTimeStampFromID( $title, $title->getLatestRevID() );
						$value = self::getDataItemFromMWTimestamp( $timestamp );
						break;
					case '_CDAT' :
						$timestamp = $title->getFirstRevision()->getTimestamp();
						$value = self::getDataItemFromMWTimestamp( $timestamp );
						break;
					case '_NEWP' :
						$value = new SMWDIBoolean( $title->isNewPage() );
						break;
					case '_LEDT' :
						// Do *not* use
						// $revision = Revision::newFromId( $title->getLatestRevID() );
						// When run from maintenance/runJobs.php it causes exceptions since
						// `$title->getLatestRevID()' returns zero for *existing* page.
						// See https://bugzilla.wikimedia.org/show_bug.cgi?id=35962 for discussion.
						$revision = Revision::newFromTitle( $title );
						$user = User::newFromId( $revision->getUser() );
						$value = SMWDIWikiPage::newFromTitle( $user->getUserPage() );
						break;
				}

				if ( !is_null( $value ) ) {
					$semdata->addPropertyObjectValue( $prop, $value );
				} // Issue error or warning?

			} // foreach
		} else { // data found, but do all operations as if it was empty
			$semdata = new SMWSemanticData( $semdata->getSubject() );
		}

		// Check if the semantic data has been changed.
		// Sets the updateflag to true if so.
		// Careful: storage access must happen *before* the storage update;
		// even finding uses of a property fails after its type was changed.
		$updatejobflag = false;
		$jobs = array();

		if ( $makejobs && $smwgEnableUpdateJobs && ( $namespace == SMW_NS_PROPERTY ) ) {
			// If it is a property, then we need to check if the type or the allowed values have been changed.
			$ptype = new SMWDIProperty( '_TYPE' );
			$oldtype = smwfGetStore()->getPropertyValues( $semdata->getSubject(), $ptype );
			$newtype = $semdata->getPropertyValues( $ptype );

			if ( !self::equalDatavalues( $oldtype, $newtype ) ) {
				$updatejobflag = true;
			} else {
				foreach ( $smwgDeclarationProperties as $prop ) {
					$pv = new SMWDIProperty( $prop );
					$oldvalues = smwfGetStore()->getPropertyValues( $semdata->getSubject(), $pv );
					$newvalues = $semdata->getPropertyValues( $pv );
					$updatejobflag = !self::equalDatavalues( $oldvalues, $newvalues );
				}
			}

			if ( $updatejobflag ) {
				$prop = new SMWDIProperty( $title->getDBkey() );
				$subjects = smwfGetStore()->getAllPropertySubjects( $prop );

				foreach ( $subjects as $subject ) {
					$subjectTitle = $subject->getTitle();
					if ( !is_null( $subjectTitle ) ) {
						$jobs[] = new SMWUpdateJob( $subjectTitle );
					}
				}
				wfRunHooks( 'smwUpdatePropertySubjects', array( &$jobs ) );

				$subjects = smwfGetStore()->getPropertySubjects( new SMWDIProperty( '_ERRP' ), $semdata->getSubject() );

				foreach ( $subjects as $subject ) {
					$subjectTitle = $subject->getTitle();

					if ( !is_null( $subjectTitle ) ) {
						$jobs[] = new SMWUpdateJob( $subjectTitle );
					}
				}
			}
		} elseif ( $makejobs && $smwgEnableUpdateJobs && ( $namespace == SMW_NS_TYPE ) ) {
			// if it is a type we need to check if the conversion factors have been changed
			$pconv = new SMWDIProperty( '_CONV' );
			$ptype = new SMWDIProperty( '_TYPE' );

			$oldfactors = smwfGetStore()->getPropertyValues( $semdata->getSubject(), $pconv );
			$newfactors = $semdata->getPropertyValues( $pconv );
			$updatejobflag = !self::equalDatavalues( $oldfactors, $newfactors );

			if ( $updatejobflag ) {
				$store = smwfGetStore();

				/// FIXME: this will kill large wikis! Use incremental updates!
				$dv = SMWDataValueFactory::newTypeIdValue( '__typ', $title->getDBkey() );
				$proppages = $store->getPropertySubjects( $ptype, $dv );

				foreach ( $proppages as $proppage ) {
					$propertyTitle = $proppage->getTitle();

					if ( !is_null( $propertyTitle ) ) {
						$jobs[] = new SMWUpdateJob( $propertyTitle );
					}

					$prop = new SMWDIProperty( $proppage->getDBkey() );
					$subjects = $store->getAllPropertySubjects( $prop );

					foreach ( $subjects as $subject ) {
						$subjectTitle = $subject->getTitle();

						if ( !is_null( $subjectTitle ) ) {
							$jobs[] = new SMWUpdateJob( $subjectTitle );
						}
					}

					$subjects = smwfGetStore()->getPropertySubjects(
						new SMWDIProperty( '_ERRP' ),
						$prop->getWikiPageValue()
					);

					foreach ( $subjects as $subject ) {
						$subjectTitle = $subject->getTitle();

						if ( !is_null( $subjectTitle ) ) {
							$jobs[] = new SMWUpdateJob( $subject->getTitle() );
						}
					}
				}
			}
		}

		// Actually store semantic data, or at least clear it if needed
		if ( $processSemantics ) {
			smwfGetStore()->updateData( $semdata );
 		} else {
			smwfGetStore()->clearData( $semdata->getSubject() );
		}

		// Finally trigger relevant Updatejobs if necessary
		if ( $updatejobflag ) {
			Job::batchInsert( $jobs ); ///NOTE: this only happens if $smwgEnableUpdateJobs was true above
		}

		return true;
	}

	/**
	 * Helper function that compares two arrays of data values to check whether
	 * they contain the same content. Returns true if the two arrays contain the
	 * same data values (irrespective of their order), false otherwise.
	 */
	static public function equalDatavalues( $dv1, $dv2 ) {
		// The hashes of all values of both arrays are taken, then sorted
		// and finally concatenated, thus creating one long hash out of each
		// of the data value arrays. These are compared.
		$values = array();
		foreach ( $dv1 as $v ) {
			$values[] = $v->getHash();
		}

		sort( $values );
		$dv1hash = implode( '___', $values );

		$values = array();
		foreach ( $dv2 as $v ) {
			$values[] = $v->getHash();
		}

		sort( $values );
		$dv2hash = implode( '___', $values );

		return ( $dv1hash == $dv2hash );
	}

	/**
	 * Hook function fetches category information and other final settings
	 * from parser output, so that they are also replicated in SMW for more
	 * efficient querying.
	 */
	static public function onParserAfterTidy( &$parser, &$text ) {
		global $smwgUseCategoryHierarchy, $smwgCategoriesAsInstances;

		if ( is_null( self::getSMWData( $parser ) ) ) {
			return true;
		}

		$categories = $parser->mOutput->getCategoryLinks();
		foreach ( $categories as $catname ) {
			if ( $smwgCategoriesAsInstances && ( self::getSMWData( $parser )->getSubject()->getNamespace() != NS_CATEGORY ) ) {
				$pinst = new SMWDIProperty( '_INST' );
				$categoryDi = new SMWDIWikiPage( $catname, NS_CATEGORY, '' );
				self::getSMWData( $parser )->addPropertyObjectValue( $pinst, $categoryDi );
			}

			if ( $smwgUseCategoryHierarchy && ( self::getSMWData( $parser )->getSubject()->getNamespace() == NS_CATEGORY ) ) {
				$psubc = new SMWDIProperty( '_SUBC' );
				$categoryDi = new SMWDIWikiPage( $catname, NS_CATEGORY, '' );
				self::getSMWData( $parser )->addPropertyObjectValue( $psubc, $categoryDi );
			}
		}

		$sortkey = $parser->mDefaultSort ? $parser->mDefaultSort :
		            str_replace( '_', ' ', self::getSMWData( $parser )->getSubject()->getDBkey() );
		$pskey = new SMWDIProperty( '_SKEY' );
		try {
			$sortkeyDi = new SMWDIString( $sortkey );
		} catch (SMWStringLengthException $e) { // cut it down to a reasonable length; no further bytes should be needed for sorting
			$sortkey = substr( $sortkey, 0, $e->getMaxLength() );
			$sortkeyDi = new SMWDIString( $sortkey );
		}
		self::getSMWData( $parser )->addPropertyObjectValue( $pskey, $sortkeyDi );

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
	 *
	 * @param WikiPage|Article $article WikiPage on 1.19 and later
	 * @param Revision $rev
	 * @param integer $baseID
	 * @param User $user
	 *
	 * @return true
	 */
	static public function onNewRevisionFromEditComplete( /* WikiPage */ $article, Revision $rev, $baseID, User $user ) {
		global $smwgPageSpecialProperties;

		if ( ( $article->mPreparedEdit ) && ( $article->mPreparedEdit->output instanceof ParserOutput ) ) {
			$semdata = self::getSMWDataFromParserOutput(
				$article->mPreparedEdit->output,
				$article->getTitle()
			);
		} else { // give up, just keep the old data
			return true;
		}

		if ( in_array( '_MDAT', $smwgPageSpecialProperties ) ) {
			$timestamp = $article->getTimestamp();
			$di = self::getDataItemFromMWTimestamp( $timestamp );

			if ( !is_null( $di ) ) {
				$semdata->addPropertyObjectValue( new SMWDIProperty( '_MDAT' ), $di );
			}
		}

		if ( in_array( '_LEDT', $smwgPageSpecialProperties ) ) {
			$di = SMWDIWikiPage::newFromTitle( $user->getUserPage() );

			if ( !is_null( $di ) ) {
				$semdata->addPropertyObjectValue( new SMWDIProperty( '_LEDT' ), $di );
			}
		}

		if ( in_array( '_NEWP', $smwgPageSpecialProperties ) ) {
			$semdata->addPropertyObjectValue(
				new SMWDIProperty( '_NEWP' ),
				new SMWDIBoolean( is_null( $rev->getParentId() ) )
			);
		}

		return true;
	}

	/**
	 * Hook where the storage of data is triggered. This happens when
	 * saving an article but possibly also when running update jobs.
	 */
	static public function onLinksUpdateConstructed( $links_update ) {
		self::storeData( $links_update->mParserOutput, $links_update->mTitle, true );
		return true;
	}

	/**
	 * This method will be called whenever an article is deleted so that
	 * semantic properties are cleared appropriately.
	 */
	static public function onArticleDelete( &$article, &$user, &$reason ) {
		smwfGetStore()->deleteSubject( $article->getTitle() );
		return true; // always return true, in order not to stop MW's hook processing!
	}

	/**
	 * This method will be called whenever an article is moved so that
	 * semantic properties are moved accordingly.
	 */
	static public function onTitleMoveComplete( &$old_title, &$new_title, &$user, $pageid, $redirid ) {
		smwfGetStore()->changeTitle( $old_title, $new_title, $pageid, $redirid );
		return true; // always return true, in order not to stop MW's hook processing!
	}

	/**
	 * Create an SMWDITime object from a MediaWiki timestamp. A timestamp
	 * is a 14 character string YYYYMMDDhhmmss.
	 *
	 * @param $timestamp string MediaWiki timestamp
	 * @return SWMDITime object or null if errors occurred
	 */
	static protected function getDataItemFromMWTimestamp( $timestamp ) {
		$year  = intval( substr( $timestamp, 0, 4 ) );
		$month = intval( substr( $timestamp, 4, 2 ) );
		$day   = intval( substr( $timestamp, 6, 2 ) );
		$hour  = intval( substr( $timestamp, 8, 2 ) );
		$min   = intval( substr( $timestamp, 10, 2 ) );
		$sec   = intval( substr( $timestamp, 12, 2 ) );
		try {
			return new SMWDITime( SMWDITime::CM_GREGORIAN, $year, $month, $day, $hour, $min, $sec );
		} catch ( SMWDataItemException $e ) {
			// we rely on MW timestamp format above -- if it ever changes,
			// exceptions might possibly occur but this should not prevent editing
			trigger_error( $e->getMessage(), E_USER_NOTICE );
			return null;
		}
	}

}
