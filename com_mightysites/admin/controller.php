<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('_JEXEC') or die;

class MightysitesController extends JControllerLegacy
{
	protected $default_view = 'sites';
	
	public function display($cachable = false, $urlparams = false)
	{
		$app = JFactory::getApplication();
		
		$view		= $app->input->get('view', $this->default_view);
		$layout 	= $app->input->get('layout', 'default');
		$id			= $app->input->getInt('id');

		// Check for edit form.
		if ($view == 'site' && $layout == 'edit' && !$this->checkEditId('com_mightysites.edit.site', $id)) {
			// Somehow the person just went to the form - we don't allow that.
			$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
			$this->setMessage($this->getError(), 'error');
			$this->setRedirect(JRoute::_('index.php?option=com_mightysites&view=sites', false));

			return false;
		}

		if ($view == 'database' && $layout == 'edit' && !$this->checkEditId('com_mightysites.edit.database', $id)) {
			// Somehow the person just went to the form - we don't allow that.
			$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
			$this->setMessage($this->getError(), 'error');
			$this->setRedirect(JRoute::_('index.php?option=com_mightysites&view=databases', false));

			return false;
		}
		
		// Copy DB scheduled?
		$session 	= JFactory::getSession();
		$copyDB 	= $session->get('mighty_copy');
		if ($copyDB) {
			$session->set('mighty_copy', '');
			$y = JDEBUG ? '350' : '250';
			JFactory::getDocument()->addScriptDeclaration('	
				window.addEvent("domready", function(){
					SqueezeBox.open(null, {handler: "iframe", url: "'.$copyDB.'", size: {x: 770, y: '.$y.'} });
				});
			');
			JHtml::_('behavior.modal');
		}

		parent::display();
		return $this;
	}
}
