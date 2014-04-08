<?php

namespace SMW\Store;

/**
 * Interface for PropertyStatisticsStore rebuilders.
 *
 * @since 1.9
 *
 * @ingroup SMWStore
 *
 * @license GNU GPL v2 or later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface PropertyStatisticsRebuilder {

	/**
	 * @since 1.9
	 *
	 * @param PropertyStatisticsStore $propStatsStore
	 */
	public function rebuild( PropertyStatisticsStore $propStatsStore );

}
