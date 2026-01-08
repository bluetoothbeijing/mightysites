<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('_JEXEC') or die('Restricted access');

class MightysitesTableDatabase extends JTable
{ 

	public function __construct(&$db)
	{
		parent::__construct('#__mightysites', 'id', $db);
	}
	
	public function bind($array, $ignore = '')
	{
		if (isset($array['params']) && is_array($array['params'])) {
			$registry = new JRegistry();
			$registry->loadArray($array['params']);
			$array['params'] = (string)$registry;
		}
		
		// Force type!
		$array['type'] = 2;

		return parent::bind($array, $ignore);
	} 

	public function check()
	{
		$db = JFactory::getDBO();

		// Check tables prefix.
		if (!preg_match('/^[a-z0-9_]*$/', $this->dbprefix)) {
			$this->setError(JText::sprintf('COM_MIGHTYSITES_ERROR_INVALID_DBPREFIX', $this->dbprefix));
			return false;
		}
		
		// Check for unique table prefix.
		$db->setQuery('SELECT `id` FROM `#__mightysites` WHERE `db`='.$db->quote($this->db).' AND `dbprefix` = '.$db->quote($this->dbprefix), 0, 1);
		$id = $db->loadResult();
		if ($id && $id != $this->id) {
			$this->setError(JText::sprintf('COM_MIGHTYSITES_ERROR_DATABASE_EXISTS', $this->db, $this->dbprefix));
			return false;
		}
			
		return true;
	} 	
}