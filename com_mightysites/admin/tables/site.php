<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('_JEXEC') or die('Restricted access');

class MightysitesTableSite extends JTable
{
	public function __construct(&$db)
	{
		parent::__construct('#__mightysites', 'id', $db);
	}
	
	public function bind($array, $ignore = '')
	{
		if (isset($array['params']) && is_array($array['params']))
		{
			$registry = new JRegistry();
			$registry->loadArray($array['params']);
			$array['params'] = (string)$registry;
		}
		
		// Force type!
		$array['type'] = 1;

		return parent::bind($array, $ignore);
	} 
	
	public function check()
	{
		$db = JFactory::getDBO();
		
		// Check tables prefix.
		if (!preg_match('/^[a-zA-Z0-9_]*$/', $this->dbprefix))
		{
			$this->setError(JText::sprintf('COM_MIGHTYSITES_ERROR_INVALID_DBPREFIX', $this->dbprefix));
			return false;
		}
		
		// Check for domain.
		$db->setQuery('SELECT `id` FROM `#__mightysites` WHERE `domain`='.$db->quote($this->domain), 0, 1);
		$id = $db->loadResult();
		if ($id && $id != $this->id)
		{
			$this->setError(JText::sprintf('COM_MIGHTYSITES_ERROR_DOMAIN_EXISTS', $this->domain));
			return false;
		}
		
		// Check for unique table prefix
		$db->setQuery('SELECT `id` FROM `#__mightysites` WHERE `dbprefix` LIKE '.$db->quote('%'.str_replace('_', '\_', $this->dbprefix).'%'), 0, 1);
		$id = $db->loadResult();
		if ($id && $id != $this->id)
		{
			//$this->setError(JText::sprintf('COM_MIGHTYSITES_ERROR_PREFIX_EXISTS', $this->dbprefix));
			//return false;
			JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_MIGHTYSITES_NOTICE_PREFIX_EXISTS', $this->dbprefix), 'notice');
		}
		
		// Virtual subfolder via symlink
		if (strpos($this->domain, '/') !== false)
		{
			$parts = explode('/', $this->domain);
			array_shift($parts);
			
			// Only site.com/test, not site.com/test/test2/, todo - check parent folder
			/*
			if (count($parts) > 1) {
				$this->setError(JText::sprintf('COM_MIGHTYSITES_ERROR_SYMLINK_LEVEL', $this->domain));
				return false;
			}
			*/
			
			$path 		= implode('/', $parts);
			$file 		= JPATH_SITE.'/'.$path;
			$symlink 	= false;
			
			// New site
			if (!$this->id)
			{
				// Already exists?
				if (file_exists($file))
				{
					// Link?
					if (is_link($file))
					{
						JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_MIGHTYSITES_NOTICE_SYMLINK_EXISTS', $file), 'notice');
					} 
					// File or folder?
					else
					{
						$this->setError(JText::sprintf('COM_MIGHTYSITES_ERROR_SYMLINK_EXISTS', $file));
						return false;
					}
				}
				else
				{
					$symlink = true;
				}
			}
			// Current site
			else
			{
				// Absent link? Recreate!
				if (!file_exists($file))
				{
					$symlink = true;
				}
			}
			
			// Create symlink
			if ($symlink)
			{
				$result = @symlink(JPATH_SITE, $file);
				if (!$result)
				{
					$this->setError(JText::sprintf('COM_MIGHTYSITES_ERROR_SYMLINK_FAILED', $file, JPATH_SITE));
					return false;
				}
			}
		}
			
		return true;
	}
}
