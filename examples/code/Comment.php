<?php

namespace MyProject\GraphQL;

use SilverStripe\ORM\DataObject;

class Comment extends DataObject
{
	private static $db = [
		'Comment' => 'Text',
		'Author' => 'Varchar'
	];

	private static $has_one = [
		'Post' => 'MyProject\GraphQL\Post'
	];

	public function canView($member = null, $context = []) { return true; }
	public function canEdit($member = null, $context = []) { return true; }
	public function canCreate($member = null, $context = []) { return true; }
	public function canDelete($member = null, $context = []) { return true; }

}