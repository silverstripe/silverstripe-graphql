<?php

namespace MyProject;

use SilverStripe\ORM\DataObject;

class Comment extends DataObject
{
	private static $db = [
		'Comment' => 'Text',
		'Author' => 'Varchar'
	];

	private static $has_one = [
		'Post' => Post::class
	];

	public function canView($member = null, $context = []) { return true; }
	public function canEdit($member = null, $context = []) { return true; }
	public function canCreate($member = null, $context = []) { return true; }
	public function canDelete($member = null, $context = []) { return true; }

}