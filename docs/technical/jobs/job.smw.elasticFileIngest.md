## smw.elasticFileIngest

* Description: The job is responsible for sending a file ingest request for a particular file page to the Elasticsearch cluster, and once completed, retrieves `File attachment` annotation information and stores them in Semantic MediaWiki.
* Reference class: [`FileIngestJob.php`][FileIngestJob.php]

## Notes

Due to size and memory consumption requirements by Elasticsearch and Tika, file content ingestion happens exclusively in the background via the command line (`waitOnCommandLine`). Only after the job has been executed successfully will the file content and additional annotations be accessible and available as indexed (searchable) content.

[FileIngestJob.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/Indexer/Jobs/FileIngestJob.php
