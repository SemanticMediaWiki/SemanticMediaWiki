<?php

declare( strict_types = 1 );

namespace SMW\ResultFormats\SimpleResult;

use SMW\Query\PrintRequest;
use SMWDataItem;

/**
 * @since 3.2
 */
class PropertyValueCollection {

	private $printRequest;
	private $dataItems;

	/**
	 * @param PrintRequest $printRequest
	 * @param SMWDataItem[] $dataItems
	 */
	public function __construct( PrintRequest $printRequest, array $dataItems ) {
		$this->printRequest = $printRequest;
		$this->dataItems = $dataItems;
	}

	public function getPrintRequest(): PrintRequest {
		return $this->printRequest;
	}

	/**
	 * @return SMWDataItem[]
	 */
	public function getDataItems(): array {
		return $this->dataItems;
	}

}
