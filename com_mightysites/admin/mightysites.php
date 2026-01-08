<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('_JEXEC') or die;

@ini_set('max_execution_time', '10000');
@set_time_limit(10000);
@ignore_user_abort(true);

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_mightysites'))
{
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

// Load Helper
require_once JPATH_ADMINISTRATOR.'/components/com_mightysites/helpers/helper.php';

// Load Plugins
JPluginHelper::importPlugin('mightysites');

// Only Daddy rocks!
$host_site = MightysitesHelper::getCurrentSite();
if ((!isset($host_site->id) || $host_site->id != 1) && (isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] != '127.0.0.1'))
{
	JFactory::getApplication()->redirect('index.php', JText::sprintf('COM_MIGHTYSITES_INVALID_HOST', MightysitesHelper::getSite(1)->domain), 'error');
}

$controller	= JControllerLegacy::getInstance('Mightysites');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
