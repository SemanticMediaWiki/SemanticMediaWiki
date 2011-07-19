<?php
/*
 * This test case is part of the SimpleSeleniumTestSuite.
 * Configuration for these tests are documented as part of SimpleSeleniumTestSuite.php
 */
class BuildChainOfPropertiesInAQuery extends SeleniumTestCase {


	public function testSetup()
	{
		set_time_limit(0); 
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->type("searchInput", "Testing person Michael Green");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=Testing person Michael Green");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[In love with::Testing person Laura Blue]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
		$this->click("link=Testing person Laura Blue");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[Daughter of::Testing person Jimmy Red]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "Testing person Sarah Yellow");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=Testing person Sarah Yellow");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[In love with::Testing person Denise Grey]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
		$this->click("link=Testing person Denise Grey");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[Daughter of::Testing person Jimmy Red]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "Testing person Mary Pink");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=Testing person Mary Pink");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[In love with::Testing person Jimmy Red]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
	}

	public function testTest()
{
    $this->open($this->getUrl() ."index.php/Special:Ask");
    $this->type("q", "[[In love with.Daughter of::Testing person Jimmy Red]]");
    $this->click("//input[@value='Find results']");
    $this->waitForPageToLoad("30000");
    try {
        $this->assertTrue($this->isTextPresent("Testing person Michael Green"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertTrue($this->isTextPresent("Testing person Sarah Yellow"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Testing person Mary Pink"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
  }

	public function testTeardown()
	{
		$this->open($this->getUrl() ."index.php/Testing person Michael Green");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		
		$this->open($this->getUrl() ."index.php/Testing person Laura Blue");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		
		$this->open($this->getUrl() ."index.php/Testing person Sarah Yellow");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		
		$this->open($this->getUrl() ."index.php/Testing person Denise Grey");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		
		$this->open($this->getUrl() ."index.php/Testing person Mary Pink");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
	}
}
