<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('_JEXEC') or die('Restricted access');

class MightysitesControllerAbout extends JControllerLegacy
{

	public function version()
	{
		$app = JFactory::getApplication();
		
		$current = str_replace('.', '', $app->input->get('current'));

		$version = new JVersion();
		
		$url = 'http://alterbrains.com/version?id=pkg_mightysites&release='.$version->RELEASE;
		
		if (!($latest = @file_get_contents($url))) {
			echo '<span style="color:red">', JText::_('COM_MIGHTYSITES_VERSION_ERROR'), '</span>';
		}
		else {
			if (str_replace('.', '', $latest) == $current) {
				echo '<span style="color:green">', JText::_('COM_MIGHTYSITES_VERSION_CURRENT'), '</span>';
			}
			else {
				echo '<span style="color:red">', JText::sprintf('COM_MIGHTYSITES_VERSION_LATEST', $latest), '</span><br /><br />', JText::sprintf('COM_MIGHTYSITES_VISIT_DADDY', '<a href="http://alterbrains.com" target="_blank">http://alterbrains.com</a>.');
			}
		}
		jexit();
	}

}