<?php
/**
 * Basic abstract classes for SMW's storage abstraction layer.
 *
 * @author Markus Krötzsch
 */

global $smwgIP;
require_once($smwgIP . '/includes/SMW_SemanticData.php');
require_once($smwgIP . '/includes/storage/SMW_Query.php');
require_once($smwgIP . '/includes/storage/SMW_QueryResult.php');

/**
 * The abstract base class for all classes that implement access to some
 * semantic store. Besides the relevant interface, this class provides default
 * implementations for some optional methods, which inform the caller that 
 * these methods are not implemented.
 */
abstract class SMWStore {

///// Reading methods /////

	/**
	 * Get an array of all special values stored for the given subject and special property
	 * (identified as usual by an integer constant). The result is an array which may contain
	 * different kinds of contents depending on the special property that was requested.
	 */
	abstract function getSpecialValues(Title $subject, $specialprop, $limit = -1, $offset = 0);

	/**
	 * Get an array of all attribute values stored for the given subject and atttribute. The result 
	 * is an array of SMWDataValue objects.
	 */
	abstract function getAttributeValues(Title $subject, Title $attribute, $limit = -1, $offset = 0);
	
	/**
	 * Get an array of all attributes for which the given subject has some value. The result is an
	 * array of Title objects.
	 */
	abstract function getAttributes(Title $subject, $limit = -1, $offset = 0);

	/**
	 * Get an array of all objects that a given subject relates to via the given relation. The
	 * result is an array of Title objects.
	 */
	abstract function getRelationObjects(Title $subject, Title $relation, $limit = -1, $offset = 0);

	/**
	 * Get an array of all subjects that are related to a given object via the given relation. The
	 * result is an array of Title objects.
	 */
	abstract function getRelationSubjects(Title $relation, Title $object, $limit = -1, $offset = 0);

	/**
	 * Get an array of all relations via which the given subject relates to some object. The result is an
	 * array of Title objects.
	 */
	abstract function getOutRelations(Title $subject, $limit = -1, $offset = 0);

	/**
	 * Get an array of all relations for which there is some subject that relates to the given object. 
	 * The result is an array of Title objects.
	 */
	abstract function getInRelations(Title $object, $limit = -1, $offset = 0);

///// Writing methods /////

	/**
	 * Delete all semantic properties that the given subject has. This
	 * includes relations, attributes, and special properties. This does not
	 * delete the respective text from the wiki, but only clears the stored 
	 * data.
	 */
	abstract function deleteSubject(Title $subject);

	/**
	 * Update the semantic data stored for some individual. The data is given
	 * as a SMWSemData object, which contains all semantic data for one particular
	 * subject.
	 */
	abstract function updateData(SMWSemanticData $data);

	/**
	 * Update the store to reflect a renaming of some article. The old and new title objects
	 * are given. Since this is typically triggered when moving articles, the ID of the title
	 * objects is normally not affected by the change, which is reflected by the value of $keepid.
	 * If $keepid is true, the old and new id of the title is the id of $newtitle, and not the
	 * id of $oldtitle.
	 */
	abstract function changeTitle(Title $oldtitle, Title $newtitle, $keepid = true);

///// Query answering /////

	/**
	 * Execute the provided query and return the result as an SMWQueryResult.
	 */
	abstract function getQueryResult(SMWQuery $query);

///// Setup store /////

	/**
	 * Setup all storage structures properly for using the store. This function performs tasks like
	 * creation of database tables. It is called upon installation as well as on upgrade: hence it
	 * must be able to upgrade existing storage structures if needed. It should return "true" if 
	 * successful and return a meaningful string error message otherwise.
	 */
	abstract function setup();

}

?>