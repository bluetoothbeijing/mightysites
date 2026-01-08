<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('_JEXEC') or die('Restricted access');

class MightysitesViewDatabase extends JViewLegacy
{
	protected $state;
	protected $item;
	protected $form;

	public function display($tpl = null)
	{
		$this->state	= $this->get('State');
		$this->item		= $this->get('Item');
		$this->form		= $this->get('Form');

		$this->params	= new JRegistry($this->item->params);
		
		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			JError::raiseError(500, implode("\n", $errors));
			return false;
		}
		
		$this->addToolbar();

		parent::display($tpl);
	}

	protected function addToolbar()
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);

		$user		= JFactory::getUser();
		$userId		= $user->get('id');
		$isNew		= ($this->item->id == 0);
		$checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));

		JToolBarHelper::title(JText::_($isNew ? 'COM_MIGHTYSITES_TITLE_DATABASE_ADD' : 'COM_MIGHTYSITES_TITLE_DATABASE_EDIT'), 'database');

		// If not checked out, can save the item.
		if (!$checkedOut && $user->authorise('core.edit', 'com_mightysites'))
		{
			JToolBarHelper::apply('database.apply');
			JToolBarHelper::save('database.save');
			JToolbarHelper::save2new('database.save2new');
		}
		
		if (empty($this->item->id))
		{
			JToolBarHelper::cancel('database.cancel');
		}
		else
		{
			JToolBarHelper::cancel('database.cancel', 'JTOOLBAR_CLOSE');
		}
	}
	
}
