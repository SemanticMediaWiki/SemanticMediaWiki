<?php

namespace SMW\Serializers;

/**
 * Only returns the head of the subobject without serializing associated
 * dataItems.
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class FlatSemanticDataSerializer extends SemanticDataSerializer {

	/**
	 * @see SemanticDataSerializer::doSerializeSubobject
	 *
	 * @return array
	 */
	protected function doSerializeSubSemanticData( $subSemanticData ) {
		$subobjects = [];

		foreach ( $subSemanticData as $semanticData ) {
			$subobjects[] = $semanticData->getSubject()->getSerialization();
		}

		return $subobjects;
	}

}
