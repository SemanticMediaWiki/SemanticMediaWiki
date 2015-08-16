<?php

namespace SMW\Query\ProfileAnnotator;

use SMW\Subobject;
use SMWQuery as Query;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class QueryProfileAnnotatorFactory {

	/**
	 * @since 2.1
	 *
	 * @param Query $query
	 * @param string $format
	 * @param integer|null $duration
	 *
	 * @return ProfileAnnotator
	 */
	public function newJointProfileAnnotator( Query $query, $format, $duration = null ) {

		$nullProfileAnnotator = new NullProfileAnnotator(
			new Subobject( $query->getSubject()->getTitle() ),
			$query->getQueryId()
		);

		$descriptionProfileAnnotator = new DescriptionProfileAnnotator(
			$nullProfileAnnotator,
			$query->getDescription()
		);

		$formatProfileAnnotator = new FormatProfileAnnotator(
			$descriptionProfileAnnotator,
			$format
		);

		$durationProfileAnnotator = new DurationProfileAnnotator(
			$formatProfileAnnotator,
			$duration
		);

		return $durationProfileAnnotator;
	}

}
