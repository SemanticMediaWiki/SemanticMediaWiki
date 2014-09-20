<?php

namespace SMW\Query\Profiler;

use SMW\Query\Language\Description;

use SMW\Subobject;
use SMW\HashIdGenerator;

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
	 * @return ProfileAnnotator
	 */
	public function newQueryProfiler( Title $title, Description $description, array $queryParameters, $queryFormat, $queryDuration ) {

		$profiler = new NullProfile(
			new Subobject( $title ),
			new HashIdGenerator( $queryParameters )
		);

		$profiler = new DescriptionProfile( $profiler, $description );
		$profiler = new FormatProfile( $profiler, $queryFormat );
		$profiler = new DurationProfile( $profiler, $queryDuration );

		return $profiler;
	}

}
