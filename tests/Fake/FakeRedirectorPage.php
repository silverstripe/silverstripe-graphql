<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use Page;

/**
 * Because otherwise we have to include silverstripe-cms as a dependency just
 * to get the test to work.
 */
class FakeRedirectorPage extends Page {

	private static $db = [
		"RedirectionType" => "Enum('Internal,External','Internal')",
		"ExternalURL" => "Varchar(2083)"
	];

}