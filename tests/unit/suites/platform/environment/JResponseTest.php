<?php
/**
 * @package     Joomla.UnitTest
 * @subpackage  Environment
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

require_once JPATH_PLATFORM . '/joomla/environment/response.php';

/**
 * Test class for JResponse.
 * Generated by PHPUnit on 2011-03-25 at 00:12:25.
 *
 * @package     Joomla.UnitTest
 * @subpackage  Environment
 * @since       11.1
 */
class JResponseTest extends TestCase
{
	/**
	 * Sets up the fixture, for example, open a network connection.
	 * This method is called before a test is executed.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	protected function setUp()
	{
		$this->saveFactoryState();

		JFactory::$application = $this->getMockWeb();
	}

	/**
	 * Tears down the fixture, for example, close a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown()
	{
		$this->restoreFactoryState();
	}

	/**
	 * Test...
	 *
	 * @return void
	 */
	public function testAllowCache()
	{
		$this->assertThat(
			JResponse::allowCache(),
			$this->equalTo(false)
		);

		JResponse::allowCache(true);
		$this->assertThat(
			JResponse::allowCache(),
			$this->equalTo(true)
		);

		$this->assertThat(
			JResponse::allowCache(false),
			$this->equalTo(false)
		);
	}

	/**
	 * Test...
	 *
	 * @return void
	 */
	public function testSetHeader()
	{
		JResponse::clearHeaders();
		JResponse::setHeader('somename', 'somevalue');

		$this->assertThat(
			count(JResponse::getHeaders()),
			$this->equalTo(1)
		);

		JResponse::clearHeaders();
	}

	/**
	 * Test...
	 *
	 * @return void
	 */
	public function testGetHeaders()
	{
		JResponse::clearHeaders();
		JResponse::setHeader('somename', 'somevalue');
		$headers = JResponse::getHeaders();

		$this->assertThat(
			$headers[0]['name'],
			$this->equalTo('somename')
		);

		$this->assertThat(
			$headers[0]['value'],
			$this->equalTo('somevalue')
		);

		JResponse::clearHeaders();
	}

	/**
	 * Test...
	 *
	 * @return void
	 */
	public function testClearHeaders()
	{
		JResponse::setHeader('somename', 'somevalue');
		JResponse::clearHeaders();

		$this->assertThat(
			count(JResponse::getHeaders()),
			$this->equalTo(0)
		);
	}

	/**
	 * Test...
	 *
	 * @todo Implement testSendHeaders().
	 *
	 * @return void
	 */
	public function testSendHeaders()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Test...
	 *
	 * @todo Implement testSetBody().
	 *
	 * @return void
	 */
	public function testSetBody()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Test...
	 *
	 * @todo Implement testPrependBody().
	 *
	 * @return void
	 */
	public function testPrependBody()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Test...
	 *
	 * @todo Implement testAppendBody().
	 *
	 * @return void
	 */
	public function testAppendBody()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Test...
	 *
	 * @todo Implement testGetBody().
	 *
	 * @return void
	 */
	public function testGetBody()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Test...
	 *
	 * @todo Implement testToString().
	 *
	 * @return void
	 */
	public function testToString()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

}
