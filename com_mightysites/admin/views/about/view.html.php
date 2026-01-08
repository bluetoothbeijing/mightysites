<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('_JEXEC') or die();

class MightySitesViewAbout extends JViewLegacy
{
	public function display($tpl = null)
	{
		$this->addToolbar(); 

		parent::display($tpl);
	}
	
	protected function addToolbar()
	{
		JToolBarHelper::title(JText::_('COM_MIGHTYSITES_TITLE_ABOUT'), 'help');

		MightysitesHelper::topmenu();
	}
}
