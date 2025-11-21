<?php

declare( strict_types = 1 );

namespace SMW\ResultFormats;

use SMW\ResultFormats\SimpleResult\SimpleQueryResult;

/**
 * New alternative to ResultPrinter, bringing a much simpler design that allows
 * for constructor injection and testability.
 *
 * @since 3.2
 */
interface ResultPresenter {

	// TODO: add parameter for non-global side effects
	public function presentResult( SimpleQueryResult $result ): string;

}
