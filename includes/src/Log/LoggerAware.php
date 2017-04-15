<?php

namespace SMW\Log;

/**
 * @see Psr\Log\LoggerAwareInterface
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.3
 */
interface LoggerAware {

	/**
	 * @see Psr\Log\LoggerAwareInterface::setLogger
	 */
	public function setLogger( LoggerInterface $logger );

}