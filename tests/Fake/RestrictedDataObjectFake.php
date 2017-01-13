<?php

namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\Dev\TestOnly;

class RestrictedDataObjectFake extends DataObjectFake implements TestOnly
{
	public function canCreate($member = null, $context = []) { return false; }
	
	public function canEdit($member = null) { return false; }
	
	public function canView($member = null) { return false; }
	
	public function canDelete($member = null) { return false; }
}
