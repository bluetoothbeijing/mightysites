<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('_JEXEC') or die('Restricted access');

class MightysitesViewSite extends JViewLegacy
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
		
		// load tables
		if ($this->getLayout() == 'tables')
		{
			$this->tables = $this->getTables();
		}
		else
		{
			$this->synchs_core 		= $this->getSynchs('core');
			$this->synchs_custom 	= $this->getSynchs('custom');
		}
		
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
		JFactory::getApplication()->input->set('hidemainmenu', true);

		$user		= JFactory::getUser();
		$userId		= $user->get('id');
		$isNew		= ($this->item->id == 0);
		$checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));

		JToolBarHelper::title(JText::_($isNew ? 'COM_MIGHTYSITES_TITLE_SITE_ADD' : 'COM_MIGHTYSITES_TITLE_SITE_EDIT'), 'health');

		// If not checked out, can save the item.
		if (!$checkedOut && $user->authorise('core.edit', 'com_mightysites'))
		{
			JToolBarHelper::apply('site.apply');
			JToolBarHelper::save('site.save');
			JToolbarHelper::save2new('site.save2new');
		}
		
		if (empty($this->item->id))
		{
			JToolBarHelper::cancel('site.cancel');
		}
		else
		{
			JToolBarHelper::cancel('site.cancel', 'JTOOLBAR_CLOSE');
		}
	}
	
		
	protected function getTables()
	{
		$tables = array();
		
		if ($this->item->id)
		{
			$db = MightysitesHelper::getDBO($this->item);
			
			if ($db && $db->connected())
			{
				// We display both Tables & Views
				foreach ($db->getTableList() as $table)
				{
					if (strpos($table, $this->item->dbprefix) === 0)
					{
						$name = substr($table, strlen($this->item->dbprefix));
						
						$tables[$name] = MightysitesHelper::sitesList('jform[tables]['.$name.']', $this->params->get('table_'.$name), null, $this->item->domain, JText::_('COM_MIGHTYSITES_OWN_DATA'));
					}
				}
			}
		}
		return $tables;
	}
	
	
	// Synchs
	protected function getSynchs($type)
	{
		$synchs = array();

		foreach (MightysitesHelper::getSynchs($type) as $synch)
		{
			// New code
			$content = $this->params->get('content');
			if (is_object($content) || is_array($content))
			{
				$content = (array) $content;
				
				$value = isset($content[$synch]) ? $content[$synch] : '';
			}
			// old legacy
			else
			{
				$value = $this->params->get($synch);
			}
			
			$synchs[$synch] = MightysitesHelper::sitesList('jform[content]['.$synch.']', $value, null, $this->item->id, JText::_('COM_MIGHTYSITES_OWN_DATA'));
		}
		
		return $synchs;
	}
}
