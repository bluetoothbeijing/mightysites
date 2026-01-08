<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controllerform');


class MightysitesControllerSite extends JControllerForm
{
	
	public function getModel($name = 'Site', $prefix = 'MightysitesModel', $config = array('ignore_request' => false))
	{
		$model = parent::getModel($name, $prefix, $config);
		return $model;
	}
	
	public function add()
	{
		if (!is_writable(JPATH_SITE)) {
			JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_MIGHTYSITES_ERROR_ROOT_NOT_WRITABLE', JPATH_SITE), 'error');
			$this->setRedirect('index.php?option=com_mightysites');
			return;
		}
		
		return parent::add();
	}
	
	public function login()
	{
		// Access check.
		if (!JFactory::getUser()->authorise('core.edit', 'com_mightysites')) {
			return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
		}

		$app 	= JFactory::getApplication();
		$user 	= JFactory::getUser();
		
		$return = base64_decode(JRequest::getString('return'));
		$domain = base64_decode(JRequest::getString('domain'));
		
		$to = MightysitesHelper::getSite($domain, true);
		$folder = $to->tmp_path;
		
		$token = md5(uniqid(mt_rand(), true));
		$fname = $folder . '/' . md5($token.$to->secret) . '.mighty';
		
		$data = serialize(array(
			'user_id' 	=> $user->id,
			'username' 	=> $user->username,
			'return' 	=> $return,
		));
		
		if (!JFile::write($fname, $data))
		{
			echo '<h1>', JText::_('COM_MIGHTYSITES_ERROR'), '</h1>';
			echo '<p>', JText::sprintf('COM_MIGHTYSITES_CANT_WRITE_FILE', $fname), '</p>';
			die;
		}
		
		// AdminTools token.
		$admintools_token = $to->params->get('admintools_token');
		
		$link = 'http://'.$domain.'/administrator/index.php?' . ($admintools_token ? $admintools_token . '&' : null) . 'mighty_token='.$token;
		
		$this->setRedirect($link);
	}

}
