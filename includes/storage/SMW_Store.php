<?php
/**
 * Basic abstract classes for SMW's storage abstraction layer.
 *
 * @author Markus Krötzsch
 */

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

}

 
?>