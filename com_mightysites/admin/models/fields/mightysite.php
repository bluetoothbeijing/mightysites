<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('list');

class JFormFieldMightysite extends JFormFieldList
{
	protected $type = 'Mightysite';

	protected function getOptions()
	{
		$options = array();
		
		$sites = MightysitesHelper::getSites();
		foreach ($sites as $site) {
			if ($site->type == 1 || (string)$this->element['database'] !== 'false') {
				$options[]	= JHTML::_('select.option',  $site->id, $site->domain, 'value', 'text');
			}
		}
		
		// Merge any additional options in the XML definition.
		$options = array_merge(
			parent::getOptions(),
			$options
		);

		return $options;
	}
}
