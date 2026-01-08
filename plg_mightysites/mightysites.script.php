<?php
/**
* @package		MightySites
* @copyright	Copyright (C) 2009-2013 AlterBrains.com. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
*/

defined('JPATH_BASE') or die;

class plgSystemMightysitesInstallerScript {

	function install($parent) {
		JFactory::getApplication()->enqueueMessage(JText::_('Successfully installed "System - MightySites" plugin!'));
	}

	function uninstall($parent) {
		JFactory::getApplication()->enqueueMessage(JText::_('Successfully uninstalled "System - MightySites" plugin!'));
	}

	function update($parent) {
		JFactory::getApplication()->enqueueMessage(JText::_('Successfully updated "System - MightySites" plugin!'));
	}
	
}