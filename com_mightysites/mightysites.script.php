<?php
/**
* @package		MightySites
* @copyright	Copyright (C) 2009-2013 AlterBrains.com. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
*/

defined('JPATH_BASE') or die;

class com_MightysitesInstallerScript
{
	function postflight($type, $parent)
	{
		$db = JFactory::getDBO();
		
		// Check PHP version
		if (version_compare(PHP_VERSION, '5.3.0') < 0) {
 			JFactory::getApplication()->enqueueMessage('MightySites requires PHP 5.3+, please ask your hosting provider to update PHP.', 'error');
			return;
		}

		require_once(JPATH_ADMINISTRATOR.'/components/com_mightysites/helpers/helper.php');
		
		// Load our language
		JFactory::getLanguage()->load('com_mightysites');
		
		// Create current site
		$domain = MightysitesHelper::getHost();
		
		$query = 'INSERT IGNORE INTO `#__mightysites` (`id`, `type`, `domain`) VALUES(1, 1, '.$db->quote($domain).')';
		$db->setQuery($query);
		$db->execute();

		// Create new config
		$fname 	= MightysitesHelper::getConfigFilename();
		
		if (!file_exists($fname))
		{
			$config = JFile::read(JPATH_CONFIGURATION.'/configuration.php');
			
			if ($config)
			{
				if (JFile::write($fname, $config))
				{
					// Try to make configuration.php unwriteable
					$ftp = JClientHelper::getCredentials('ftp');
					jimport('joomla.filesystem.path');
					if (!$ftp['enabled'] && JPath::isOwner($fname) && !JPath::setPermissions($fname, '0644')) {
						//JError::raiseNotice('SOME_ERROR_CODE', 'Could not make configuration.php writable');
					}
				}
				else
				{
					return;
				}
			}
		}

		// Patch configuration.php!
		MightysitesHelper::patchConfiguration();
		
		// delete old admin file before 2.1.0
		$old = JPATH_SITE.'/administrator/components/com_mightysites/admin.mightysites.php';
		if (file_exists($old))
		{
			jimport('joomla.filesystem.path');
			JFile::delete($old);
		}
		
		// Get columns
		$query = 'SELECT * FROM `#__mightysites`';
		$db->setQuery($query, 0, 1);
		$columns = $db->loadObject();
		
		// New in 3.2.3
		if (!property_exists($columns, 'aliases')) {
			$query = 'ALTER TABLE `#__mightysites` ADD `aliases` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `domain`';
			$db->setQuery($query);
			$db->execute();
		}
		
		// Move old config files to new location - since 3.2.5
		foreach(MightysitesHelper::getSites() as $site)
		{
			$fname 	= JPATH_SITE.'/configuration_'.MightysitesHelper::prepareDomain($site->domain).'.php';
			$fname2 = JPATH_SITE.'/components/com_mightysites/configuration/configuration_'.MightysitesHelper::prepareDomain($site->domain).'.php';
			
			if (file_exists($fname) && is_writable(JPATH_SITE.'/components/com_mightysites/configuration'))
			{
				JFile::move($fname, $fname2);
			}
		}
	}
	
	function uninstall($parent)
	{
		// revert config
		$db = JFactory::getDBO();
		
		$query = 'SELECT * FROM `#__mightysites` WHERE id=1';
		$db->setQuery($query, 0, 1);
		$root = $db->loadObject();

		require_once(JPATH_ADMINISTRATOR.'/components/com_mightysites/helpers/helper.php');
		
		jimport('joomla.filesystem.file');
		
		if (isset($root->id))
		{
			$error = true;
	
			$fname = MightysitesHelper::getConfigFilename($root->domain);

			if (file_exists($fname))
			{
				$new_config = JFile::read($fname);
				$old_config = JFile::read(JPATH_SITE.'/configuration.php');
				
				if ($new_config && JString::strpos($old_config, 'class JConfig') === false)
				{
					/*
					if (JString::strpos($config, 'var $mighty =') !== false) {
						$config = preg_replace('/var \$mighty \= array ([^\;]*)\;\n/u', '', $config);
					}
					*/
					if (JFile::write(JPATH_SITE.'/configuration.php', $new_config))
					{
						$error = false;
					}
				}
			}
			
			if ($error)
			{
				JFactory::getApplication()->enqueueMessage('Please re-save Global configuration!', 'error');
			}
		}
	}
}
