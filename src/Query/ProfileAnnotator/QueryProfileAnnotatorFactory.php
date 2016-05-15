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

		$nullProfileAnnotator = new NullProfileAnnotator(
			$this->newDIContainer( $query )
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

	/**
	 * @param Query $query
	 *
	 * @return DIContainer
	 */
	private function newDIContainer( Query $query ) {

		$subject = $query->getContextPage();

		if ( $subject === null ) {
			$containerSemanticData = ContainerSemanticData::makeAnonymousContainer();
		} else {
			$subject = new DIWikiPage(
				$subject->getDBkey(),
				$subject->getNamespace(),
				$subject->getInterwiki(),
				$query->getQueryId()
			);

			$containerSemanticData = new ContainerSemanticData( $subject );
		}

		return new DIContainer(
			$containerSemanticData
		);
	}

}
