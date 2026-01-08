<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('_JEXEC') or die('Restricted access');

class MightysitesViewDatabases extends JViewLegacy {
	
	public function display($tpl = null)
	{
		$this->state		 = $this->get('State');
		$this->items		 = $this->get('Items');
		$this->pagination	 = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		MightysitesHelper::topMenu();
		
		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode("\n", $errors));
			return false;
		}

		$this->addToolbar();

		parent::display($tpl);
	} 
	
	protected function addToolbar()
	{
		// Title
		JToolBarHelper::title(JText::_('COM_MIGHTYSITES_TITLE_DATABASES'), 'database');
		
		$user = JFactory::getUser();
		
		// Toolbar
		if ($user->authorise('core.create', 'com_mightysites'))
		{
			JToolBarHelper::addNew('database.add');
		}
		if ($user->authorise('core.edit', 'com_mightysites'))
		{
			JToolBarHelper::editList('database.edit');
		}
		if ($user->authorise('core.edit.state', 'com_mightysites'))
		{
			JToolbarHelper::checkin('databases.checkin');
		}
		if ($user->authorise('core.delete', 'com_mightysites'))
		{
			JToolBarHelper::divider();
			JToolBarHelper::deleteList(JText::_('COM_MIGHTYSITES_REALLY_DELETE_DATABASES'), 'databases.remove');
		}
		if ($user->authorise('core.admin', 'com_mightysites'))
		{
			JToolBarHelper::divider();
			JToolBarHelper::preferences('com_mightysites', '300');
		}
	}

	protected function getSortFields()
	{
		return array(
			'a.domain' 		=> JText::_('COM_MIGHTYSITES_HEADING_DATABASE_TITLE'),
			'a.type' 		=> JText::_('COM_MIGHTYSITES_HEADING_DATABASE_ORIGIN'),
			'a.db' 			=> JText::_('COM_MIGHTYSITES_HEADING_DATABASE_NAME'),
			'a.dbprefix' 	=> JText::_('COM_MIGHTYSITES_HEADING_DATABASE_PREFIX'),
			'a.id' 			=> JText::_('JGRID_HEADING_ID'),
		);
	}
}
