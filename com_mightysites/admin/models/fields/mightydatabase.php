<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('list');

class JFormFieldMightydatabase extends JFormFieldList
{
	protected $type = 'Mightydatabase';

	protected function getOptions()
	{
		$options = array();
		
		$sites = MightysitesHelper::getDatabases();
		foreach ($sites as $site) {
			$options[]	= JHTML::_('select.option',  $site->id, $site->domain, 'value', 'text');
		}
		
		// Merge any additional options in the XML definition.
		$options = array_merge(
			parent::getOptions(),
			$options
		);

		return $options;
	}
}
