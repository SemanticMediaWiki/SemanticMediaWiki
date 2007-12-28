<?php
/*
 * This is an implementation of the SMW store that still uses the default
 * SMW SQL Store for everything SMW does, but it decorates all edits to
 * the store with calls to a RAP store, so it keeps in parallel a second
 * store with all the semantic data. This allows for a SPARQL endpoint.
 * 
 * @author Denny Vrandecic
 */

if( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

global $smwgIP;
require_once( "$smwgIP/includes/storage/SMW_Store.php" );
require_once( "$smwgIP/includes/storage/SMW_SQLStore.php" );
require_once( "$smwgIP/includes/SMW_DataValueFactory.php" );

/**
 * Storage access class for using RAP as a triple store.
 * Most of the functions are simply forwarded to the SQL store.
 */
class SMWRAPStore extends SMWStore {
	protected $sqlstore;
	protected $rapstore;
	protected $modeluri;
	protected $baseuri;

	public function SMWRAPStore() {
		global $smwgRAPPath;
		define("RDFAPI_INCLUDE_DIR", $smwgRAPPath);
		include(RDFAPI_INCLUDE_DIR . "RDFAPI.php");
		$this->sqlstore = new SMWSQLStore();
		
		$this->modeluri = "http://example.org/model/"; // TODO needs some value
		$this->baseuri = "http://example.org/id/"; // TODO needs some value
	}

///// Writing methods /////

	/**
	 * Delete all semantic properties that the given subject has. This
	 * includes relations, attributes, and special properties. This does not
	 * delete the respective text from the wiki, but only clears the stored
	 * data.
	 */
	function deleteSubject(Title $subject) {
		$rdfmodel = $this->getRAPModel();
		$this->closeRAP();
		return $this->sqlstore->deleteSubject($subject);
	}

	/**
	 * Update the semantic data stored for some individual. The data is given
	 * as a SMWSemData object, which contains all semantic data for one particular
	 * subject. The boolean $newpage specifies whether the page is stored for the
	 * first time or not.
	 */
	function updateData(SMWSemanticData $data, $newpage) {
		if ($data->hasProperties()) {
			// Create a local memmodel
			$model = ModelFactory::getDefaultModel();
			
			// Translate SMWSemanticData to a RAP Model
			$rapsub = new Resource($this->getURI($data->getSubject()));
			$properties = $data->getProperties();
			foreach ($properties as $prop) {
				if (is_int($prop)) continue;
				$rapprop = new Resource($this->getURI($prop));
				$values = $data->getPropertyValues($prop);
				foreach ($values as $val) {
					if ($val->getTypeID() == "_wpg") {
						$rapval = new Resource($this->getURI($val->getTitle()));
						$statement = new Statement($rapsub, $rapprop, $rapval);
						$model->add($statement);
					} else {
						// TODO Save as literal
					}
				}
			}
			
			// Now add the local model to the DB model
			$rdfmodel = $this->getRAPModel();
			// First delete existing statements about subject
			$oldmodel = $rdfmodel->find($rapsub, null, null);
			$i = $oldmodel->getStatementIterator();
			$i->moveFirst();
			while ($i->current() != null) {
				$rdfmodel->remove($i->current());
				$i->next();
			}		
			$rdfmodel->addModel($model);
			$rdfmodel->close();
			$model->close();
			
			$this->closeRAP();
		}
		return $this->sqlstore->updateData($data, $newpage);
	}
	
	/**
	 * Having a title of a page, what is the URI that is described by that page?
	 */
	protected function getURI(Title $title) {
		global $smwgIP;
		include_once("$smwgIP/specials/ExportRDF/SMW_SpecialExportRDF.php");
		return ExportRDF::makeURIfromTitle($title);
	}

	/**
	 * Update the store to reflect a renaming of some article. The old and new title objects
	 * are given. Since this is typically triggered when moving articles, the ID of the title
	 * objects is normally not affected by the change, which is reflected by the value of $keepid.
	 * If $keepid is true, the old and new id of the title is the id of $newtitle, and not the
	 * id of $oldtitle.
	 */
	function changeTitle(Title $oldtitle, Title $newtitle, $keepid = true) {
		$rdfmodel = $this->getRAPModel();
		$this->closeRAP();
		return $this->sqlstore->changeTitle($oldtitle, $newtitle, $keepid);
	}

///// Setup store /////

	/**
	 * Setup all storage structures properly for using the store. This function performs tasks like
	 * creation of database tables. It is called upon installation as well as on upgrade: hence it
	 * must be able to upgrade existing storage structures if needed. It should return "true" if
	 * successful and return a meaningful string error message otherwise.
	 *
	 * The parameter $verbose determines whether the procedure is allowed to report on its progress.
	 * This is doen by just using print and possibly ob_flush/flush. This is also relevant for preventing
	 * timeouts during long operations. All output must be valid XHTML, but should preferrably be plain
	 * text, possibly with some linebreaks and weak markup.
	 */
	function setup($verbose = true) {
		$this->reportProgress("Opening connection to DB for RAP ...\n",$verbose);
		$rdfstore = $this->getRAPStore();
		$this->reportProgress("Check if DB schema is already set up for RAP ...\n",$verbose);
		if ($rdfstore->isSetup('MySQL')) {
			$this->reportProgress("RAP DB schema is already set up.\n",$verbose);
		} else {
			$this->reportProgress("Creating DB schema for RAP ...\n",$verbose);
 			$rdfstore->createTables('MySQL'); // TODO MySQL specific
			$this->reportProgress("RAP DB schema created.\n",$verbose);
		}
		$this->reportProgress("Checking RAP model...\n",$verbose);
		if ($rdfstore->modelExists($this->modeluri)) {
			$this->reportProgress("RAP model exiists.\n",$verbose);			
		} else {
			$this->reportProgress("Creating RAP model...\n",$verbose);
			$rdfstore->getNewModel($this->modeluri, $this->baseuri);
			$this->reportProgress("Created RAP model $this->modeluri\n",$verbose);
		}
		$this->closeRAP();
		$this->reportProgress("RAP setup finished. Handing over to SQL store setup.\n\n",$verbose);
		return $this->sqlstore->setup($verbose);
	}

	function drop($verbose = true) {
		/// TODO: undo all DB changes introduced by setup()
		/// Well, not all, just delete the created model. The database tables must retain, since
		/// there are only one set of tables for several models.
		return $this->sqlstore->drop();
	}

	/**
	 * Returns the connection to the RAP Database store. As of now, only MySQL
	 * is supported. TODO allow other DBs
	 */
	protected function getRAPStore() {
		// TODO only for MySQL, check for other databases!
		// Also, RAP ignores prefixes for tables. Bad RAP. Need to check with
		// the RAP developers to change that.
		global $wgDBserver, $wgDBname, $wgDBuser, $wgDBpassword;
		$this->rapstore = ModelFactory::getDbStore('MySQL', $wgDBserver, $wgDBname, $wgDBuser, $wgDBpassword);
		return $this->rapstore;
	}
		
	/**
	 * Returns the actual model where all the triples are saved.
	 */
	protected function getRAPModel() {
		$rapstore = $this->getRAPStore();
		return $rapstore->getModel($this->modeluri);
	} 
	
	/**
	 * Closes the connection to the RAP DB. As of now, this is disabled since it
	 * seems to close the connection to the MW DB as well (probably, because it 
	 * is the same DB...) TODO check if this can or does loead to a resource leak
	 */
	protected function closeRAP() {
		//$this->rapstore->close();
	}	
		
///// Reading methods -- All forwarded /////
	function getSpecialValues(Title $subject, $specialprop, $requestoptions = NULL) {
		return $this->sqlstore->getSpecialValues($subject, $specialprop, $requestoptions);
	}
	function getSpecialSubjects($specialprop, $value, $requestoptions = NULL) {
		return $this->sqlstore->getSpecialSubjects($specialprop, $value, $requestoptions);
	}
	function getPropertyValues(Title $subject, Title $property, $requestoptions = NULL, $outputformat = '') {
		return $this->sqlstore->getPropertyValues($subject, $property, $requestoptions, $outputformat);
	}
	function getPropertySubjects(Title $property, SMWDataValue $value, $requestoptions = NULL) {
		return $this->sqlstore->getPropertySubjects($property, $value, $requestoptions);
	}
	function getAllPropertySubjects(Title $property, $requestoptions = NULL) {
		return $this->sqlstore->getAllPropertySubjects($property, $requestoptions);
	}
	function getProperties(Title $subject, $requestoptions = NULL) {
		return $this->sqlstore->getProperties($subject, $requestoptions);
	}
	function getInProperties(SMWDataValue $object, $requestoptions = NULL) {
		return $this->sqlstore->getInProperties($object, $requestoptions);	
	}

///// Query answering -- all forwarded /////

	function getQueryResult(SMWQuery $query) {
		return $this->sqlstore->getQueryResult($query);
	}

///// Special page functions -- all forwarded /////

	function getPropertiesSpecial($requestoptions = NULL) {
		return $this->sqlstore->getPropertiesSpecial($requestoptions);
	}
	function getUnusedPropertiesSpecial($requestoptions = NULL) {
		return $this->sqlstore->getUnusedPropertiesSpecial($requestoptions);
	}
	function getWantedPropertiesSpecial($requestoptions = NULL) {
		return $this->sqlstore->getWantedPropertiesSpecial($requestoptions);
	}
	function getStatistics() {
		return $this->sqlstore->getStatistics();
	}

	/**
	 * Print some output to indicate progress. The output message is given by
	 * $msg, while $verbose indicates whether or not output is desired at all.
	 * Copied from SMW_SQLStore.
	 */
	protected function reportProgress($msg, $verbose) {
		if (!$verbose) {
			return;
		}
		if (ob_get_level() == 0) { // be sure to have some buffer, otherwise some PHPs complain
			ob_start();
		}
		print $msg;
		ob_flush();
		flush();
	}
}

