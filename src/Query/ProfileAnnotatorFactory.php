<?php

namespace SMW\Query;

use SMW\DIWikiPage;
use SMW\Query\ProfileAnnotators\NullProfileAnnotator;
use SMW\Query\ProfileAnnotators\DescriptionProfileAnnotator;
use SMW\Query\ProfileAnnotators\FormatProfileAnnotator;
use SMW\Query\ProfileAnnotators\DurationProfileAnnotator;
use SMW\Query\ProfileAnnotators\SourceProfileAnnotator;
use SMWContainerSemanticData as ContainerSemanticData;
use SMWDIContainer as DIContainer;
use SMWQuery as Query;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ProfileAnnotatorFactory {

	/**
	 * @since 2.5
	 *
	 * @param Query $query
	 *
	 * @return DescriptionProfileAnnotator
	 */
	public function newDescriptionProfileAnnotator( Query $query ) {

		$profileAnnotator = new NullProfileAnnotator(
			$this->newDIContainer( $query )
		);

		$profileAnnotator = new DescriptionProfileAnnotator(
			$profileAnnotator,
			$query->getDescription()
		);

		return $profileAnnotator;
	}

	/**
	 * @since 2.1
	 *
	 * @param Query $query
	 * @param string $format
	 *
	 * @return ProfileAnnotator
	 */
	public function newCombinedProfileAnnotator( Query $query, $format ) {

		$profileAnnotator = $this->newDescriptionProfileAnnotator(
			$query
		);

		$profileAnnotator = $this->newFormatProfileAnnotator(
			$profileAnnotator,
			$format
		);

		$profileAnnotator = $this->mergeWithDurationProfileAnnotator(
			$profileAnnotator,
			$query
		);

		return $profileAnnotator;
	}

	private function newFormatProfileAnnotator( $profileAnnotator, $format ) {
		return new FormatProfileAnnotator( $profileAnnotator, $format );
	}

	private function mergeWithDurationProfileAnnotator( $profileAnnotator, $query ) {

		if ( $query->getOptionBy( 'smwgQueryDurationEnabled' ) === false ) {
			return $profileAnnotator;
		}

		if ( ( $duration = $query->getOptionBy( Query::PROC_QUERY_TIME ) ) > 0 ) {
			$profileAnnotator = new DurationProfileAnnotator( $profileAnnotator, $duration );
		}

		if ( ( $duration = $query->getOptionBy( Query::PROC_PRINT_TIME ) ) > 0 ) {
			$profileAnnotator = new DurationProfileAnnotator( $profileAnnotator, $duration );
		}

		return $profileAnnotator;
	}

	/**
	 * #1416 create container manually to avoid any issues that may arise from
	 * a failed Title::makeTitleSafe.
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
