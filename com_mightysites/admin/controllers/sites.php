<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controlleradmin'); 

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.path');
jimport('joomla.client.helper');

class MightysitesControllerSites extends JControllerAdmin
{
	protected $text_prefix = 'COM_MIGHTYSITES_SITES';

	public function getModel($name = 'Site', $prefix = 'MightysitesModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function remove()
	{
		// Access check.
		if (!JFactory::getUser()->authorise('core.delete', 'com_mightysites'))
		{
			return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
		}
		
		$app = JFactory::getApplication();

		$ids	= $app->input->get('cid', array(), 'array');
		$n		= count($ids);
		JArrayHelper::toInteger($ids);

		$delete_tables = $app->input->get('delete_tables');
		
		if ($n)
		{
			$n = 0;
			foreach ($ids as $id) {
				$site 	= MightysitesHelper::getSite($id, true);
				
				$config_domain 	= MightysitesHelper::prepareDomain($site->domain);
				if ($config_domain) {
					// don't delete ourself :)
					if ($site->id == 1 && MightysitesHelper::prepareDomain() == $config_domain) {
						$app->enqueueMessage(JText::sprintf('COM_MIGHTYSITES_NOT_DELETE_CURRENT', $site->domain), 'notice');
					} else {
						if ($site->type == 1) {
							// Delete Tables & Views
							if ($delete_tables == 'true') {
								$db2 = MightysitesHelper::getDBO($site);

								$tables = MightysitesHelper::getTables($db2);
								if (count($tables)) {
									foreach ($tables as $table) {
										if (strpos($table, $site->dbprefix) === 0) {
											$db2->setQuery('DROP TABLE `'.$table.'`');
											$db2->execute() or $app->enqueueMessage($db2->getErrorMsg(), 'error');
										}
									}
								}

								$views = MightysitesHelper::getViews($db2);
								if (count($views)) {
									foreach ($views as $view) {
										if (strpos($view, $site->dbprefix) === 0) {
											$db2->setQuery('DROP VIEW `'.$view.'`');
											$db2->execute() or $app->enqueueMessage($db2->getErrorMsg(), 'error');
										}
									}
								}
								
								// restore current $db
								$db	= JFactory::getDBO();
								$db = null;
							}
							
							// Delete config
							$fname 	= MightySitesHelper::getConfigFilename($config_domain);
							if (!JFile::delete($fname)) {
								$app->enqueueMessage(JText::sprintf('COM_MIGHTYSITES_CANT_DELETE_CONFIG', $fname), 'error');
							}

							// Delete symlink
							if (strpos($site->domain, '/') !== false) {
								$parts = explode('/', $site->domain);
								array_shift($parts);
	
								$path = implode('/', $parts);
								$file = JPATH_SITE.'/'.$path;
								
								if (file_exists($file) && is_link($file)) {
									if (!unlink($file)) {
										$app->enqueueMessage(JText::sprintf('COM_MIGHTYSITES_ERROR_SYMLINK_DELETE', $file), 'error');
									}
								}
							}
						}
						
						// Delete in database
						$db	= JFactory::getDBO();
						$query = 'DELETE FROM #__mightysites WHERE id = ' . $id;
						$db->setQuery($query);
						if (!$db->execute()) {
							JError::raiseWarning( 500, $row->getError());
						}
						$n++;
					}
				}
			}
		}
		
		$mes = $n ? JText::_('COM_MIGHTYSITES_DOMAINS_DELETED') : null;
		$this->setRedirect('index.php?option=com_mightysites&view=sites', $mes);
	}
	
	public function publish()
	{
		// Access check.
		if (!JFactory::getUser()->authorise('core.edit.state', 'com_mightysites'))
		{
			return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
		}

		jimport('joomla.filesystem.path');
		jimport('joomla.filesystem.file'); 
		
		$app = JFactory::getApplication();
		
		$db			= JFactory::getDBO();
		$user		= JFactory::getUser();
		$ids		= $app->input->get('cid', array(), 'array');
		$task		= $app->input->get('task');
		$publish	= ($task == 'publish');
		$n			= count($ids);

		if (empty($ids))
		{
			return JError::raiseWarning( 500, JText::_('COM_MIGHTYSITES_NO_ITEMS'));
		}
		
		JArrayHelper::toInteger($ids);

		if (count($ids))
		{
			foreach ($ids as $id)
			{
				$config_site = MightysitesHelper::getSite($id);
				
				if ($config_site->type == 1 && isset($config_site->domain))
				{
					$file = MightysitesHelper::getConfigFilename($config_site->domain);
					if (!file_exists($file)) {
						$app->redirect('index.php?option=com_mightysites', JText::sprintf('COM_MIGHTYSITES_FILE_DOESNT_EXIST', $file), 'error');
					}
					if (!is_readable($file)) {
						$app->redirect('index.php?option=com_mightysites', JText::sprintf('COM_MIGHTYSITES_FILE_NOT_READABLE', $file), 'error');
					}
					
					$config = JFile::read($file);
					
					if ($publish) {
						$config = preg_replace('#public \$offline = \'1\';#u', 'public $offline = \'0\';', $config);
					} else {
						$config = preg_replace('#public \$offline = \'0\';#u', 'public $offline = \'1\';', $config);
					}

					$ftp = JClientHelper::getCredentials('ftp');
					
					// Attempt to make the file writeable if using FTP.
					if (!$ftp['enabled'] && JPath::isOwner($file) && !JPath::setPermissions($file, '0644'))
					{
						JError::raiseNotice('SOME_ERROR_CODE', JText::_('COM_CONFIG_ERROR_CONFIGURATION_PHP_NOTWRITABLE'));
					} 

					if (!is_writable(JPATH_CONFIGURATION)) {
						$app->redirect('index.php?option=com_mightysites', JText::sprintf('COM_MIGHTYSITES_FILE_NOT_WRITABLE', $file), 'error');
					}
	
					if (!JFile::write($file, $config)) {
						$app->redirect('index.php?option=com_mightysites', JText::sprintf('COM_MIGHTYSITES_CANT_WRITE_FILE', $file), 'error');
					}
					
					// Attempt to make the file unwriteable if using FTP.
					if (!$ftp['enabled'] && JPath::isOwner($file) && !JPath::setPermissions($file, '0444'))
					{
						JError::raiseNotice('SOME_ERROR_CODE', JText::_('COM_CONFIG_ERROR_CONFIGURATION_PHP_NOTUNWRITABLE'));
					}
					
					$mes = $publish ? 'COM_MIGHTYSITES_SITE_PUBLISHED' : 'COM_MIGHTYSITES_SITE_UNPUBLISHED';
					$app->enqueueMessage(JText::sprintf($mes, $config_site->domain));
				}
			}
		}

		$this->setRedirect('index.php?option=com_mightysites&view=sites');
	}
		
	public function cancel()
	{
		$this->setRedirect('index.php?option=com_mightysites&view=sites');

		// Initialize variables
		$db		= JFactory::getDBO();
		$post	= JRequest::get('post');
		$row	= JTable::getInstance('Site', 'Mightysites');
		$row->bind($post);
		$row->checkin();
	}
}
