<?php

namespace SMW\Query\ProfileAnnotator;

use SMW\DIWikiPage;
use SMWContainerSemanticData as ContainerSemanticData;
use SMWDIContainer as DIContainer;
use SMWQuery as Query;

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

		$subject = new DIWikiPage(
			$query->getSubject()->getDBkey(),
			$query->getSubject()->getNamespace(),
			$query->getSubject()->getInterwiki(),
			$query->getQueryId()
		);

		$container = new DIContainer(
			new ContainerSemanticData( $subject )
		);

		$nullProfileAnnotator = new NullProfileAnnotator(
			$container
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
