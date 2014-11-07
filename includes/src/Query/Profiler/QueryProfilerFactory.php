<?php

namespace SMW\Query\Profiler;

use SMW\Query\Language\Description;

use SMW\Subobject;
use SMWQuery as Query;

use Title;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class QueryProfilerFactory {

	/**
	 * @since 2.1
	 *
	 * @param Title $title
	 * @param Query $query
	 * @param string $format
	 * @param integer|null $duration
	 *
	 * @return ProfileAnnotator
	 */
	public function newJointProfileAnnotator( Title $title, Query $query, $format, $duration = null ) {

		$profiler = new NullProfile(
			new Subobject( $title ),
			$query->getHash()
		);

		$profiler = new DescriptionProfile( $profiler, $query->getDescription() );
		$profiler = new FormatProfile( $profiler, $format );
		$profiler = new DurationProfile( $profiler, $duration );

		return $profiler;
	}

}
