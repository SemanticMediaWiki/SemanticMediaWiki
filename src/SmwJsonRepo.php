<?php

declare( strict_types = 1 );

namespace SMW;

use RuntimeException;

/**
 * @public
 * @since 4.0
 */
interface SmwJsonRepo {

	public function loadSmwJson( string $configDirectory ): ?array;

	/**
	 * @throws RuntimeException
	 */
	public function saveSmwJson( string $configDirectory, array $smwJson ): void;

}
