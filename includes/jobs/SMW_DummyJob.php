<?php
/* The DummyJob Class
 * Do not subclass this class. This is just a programming template.
 * 
 * 1. Rename the class, insert the Classnamein the construct and fill the run()
 *    method with the tasks the job should do for you.
 * 2. Register the job in the SMW_Globalfuncions ($wgJobClasses array)
 * 3. Insert the job into the queue using its insert() method
 *    or if you generate a bunch of jobs, put them in an array $jobs and use 
 *    Job::batchInsert($jobs)
 *   
 * @author Daniel M. Herzig
 */
class SMW_DummyJob extends Job {

	//Constructor
	function __construct($title, $params = '', $id = 0) {
		wfDebug(__METHOD__);
		parent::__construct( 'Classname', $title, $params, $id );
	}

	/**
	 * Run method
	 * @return boolean success
	 */
	function run() {

		//What ever the Job has to do...

		return true;
	}
}
