<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('_JEXEC') or die('Restricted access');

class MightysitesViewSites extends JViewLegacy
{
	public function display($tpl = null)
	{
		$this->state		= $this->get('State');
		$this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		MightysitesHelper::topMenu();
		
		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode("\n", $errors));
			return false;
		}
		
		// Can we add new sites? Not now, may be lated.
/*		if ($_SERVER['PHP_SELF'] !== '/administrator/index.php')
		{
			$this->no_new_sites = true;
			
			JFactory::getApplication()->enqueueMessage(JText::_('COM_MIGHTYSITES_ERROR_NOT_IN_ROOT'), 'error');
			
		}
*/
		
		// Add some data, yes - here, not in model!
		foreach ($this->items as &$item)
		{
			// Title
			if ($item->id == 1)
			{
				// Highlight master site.
				$item->title = '<b title="'.JText::_('COM_MIGHTYSITES_PRIMARY_DOMAIN').'">'.$item->domain.'</b>';
			}
			else
			{
				$item->title = $item->domain;
			}
			
			// Links
			$mighty	= base64_encode(JFactory::getURI()->toString());
			//$item->link 	= $this->_getLink($item, 'index.php?option=com_config&tmpl=component&mighty='.$mighty);
			$item->link 	= $this->_getLink($item, 'index.php?option=com_config');
			$item->link2 	= $this->_getLink($item, 'index.php');
			
			// Infotip
			$item->contentTip 	= $this->_getContentInfo($item);
			$item->singleTip 	= $this->_getSingleInfo($item);
		}

		$this->addToolbar();

		parent::display($tpl);
	} 
	
	protected function addToolbar()
	{
		// Title
		JToolBarHelper::title(JText::_('COM_MIGHTYSITES_TITLE_SITES'), 'health');
		
		$user = JFactory::getUser();
		
		// Toolbar
		if ($user->authorise('core.create', 'com_mightysites') && empty($this->no_new_sites))
		{
			JToolBarHelper::addNew('site.add');
		}
		if ($user->authorise('core.edit', 'com_mightysites'))
		{
			JToolBarHelper::editList('site.edit');
			JToolBarHelper::divider();
		}
		if ($user->authorise('core.edit.state', 'com_mightysites'))
		{
			JToolBarHelper::publishList('sites.publish', 		'COM_MIGHTYSITES_ONLINE');
			JToolBarHelper::unpublishList('sites.unpublish', 	'COM_MIGHTYSITES_OFFLINE');
			JToolbarHelper::checkin('sites.checkin');
		}
		if ($user->authorise('core.delete', 'com_mightysites'))
		{
			JToolBarHelper::divider();
			JToolBarHelper::deleteList(JText::_('COM_MIGHTYSITES_REALLY_DELETE_SITES'), 'sites.remove');
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
			'a.domain'      => JText::_('COM_MIGHTYSITES_HEADING_SITE'),
			'a.db' 			=> JText::_('COM_MIGHTYSITES_HEADING_DATABASE_NAME'),
			'a.dbprefix' 	=> JText::_('COM_MIGHTYSITES_HEADING_DATABASE_PREFIX'),
			'a.id'          => JText::_('JGRID_HEADING_ID'),
		);
	}
	
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	protected function _getContentInfo($row)
	{
		// New code
		$content = $row->params->get('content');
		if (is_object($content) || is_array($content))
		{
			$content = new JRegistry($content);
		}
		// old legacy
		else
		{
			$content = $row->params;
		}
		
		$i = 0;
		$s = '<table class="table-condensed table-strapless">';
		foreach (MightysitesHelper::getSynchs() as $synch)
		{
			if ($content->get($synch))
			{
				$site = MightysitesHelper::getSite($content->get($synch));
				
				$s .= '<tr><td nowrap>'.JText::_('COM_MIGHTYSITES_SYNCH_LABEL_'.$synch).':</td><td>'.($site ? $site->domain : 'n/a').'</td></tr>';
				$i++;
			}
		}
		
		$s2 = '';
		foreach ($row->params->toArray() as $key => $value)
		{
			if (strpos($key, 'table_') === 0)
			{
				$site = MightysitesHelper::getSite($value);
				
				$s2 .= '<tr><td nowrap>#__'.substr($key, 6).':</td><td>'.($site ? $site->domain : 'n/a').'</td></tr>';
				$i++;
			}
		}
		if ($s2) {
			$s .= '<tr><td colspan="2"><hr/></td></tr>' . $s2;
		}
		
		$s .= '</table>';
		
		return $i ? htmlspecialchars($s, ENT_COMPAT, 'UTF-8') : null;
	}

	protected function _getSingleInfo($row)
	{
		if (isset($row->mighty_sdomains) && $row->mighty_sdomains)
		{
			$s = '<table>';
			$s .= '<tr><td nowrap>'.JText::_('COM_MIGHTYSITES_FIELD_SINGLE_LOGIN').':</td><td>'.((isset($row->mighty_slogin) && $row->mighty_slogin) ? JText::_('JENABLED') : JText::_('JDISABLED')).'</td></tr>';
			$s .= '<tr><td nowrap>'.JText::_('COM_MIGHTYSITES_FIELD_SINGLE_LOGOUT').':</td><td>'.((isset($row->mighty_slogout) && $row->mighty_slogout) ? JText::_('JENABLED') : JText::_('JDISABLED')).'</td></tr>';
			$s .= '<tr><td colspan="2"><hr/></td></tr>';
			
			foreach ($row->mighty_sdomains as $key => $value)
			{
				$s .= '<tr><td nowrap>'.$key.'</td></tr>';
			}
			
			$s .= '</table>';
			
			return htmlspecialchars($s, ENT_COMPAT, 'UTF-8');
		}
	}
	
	protected function _getLink(&$row, $link)
	{
		if ($row->domain != MightysitesHelper::getHost())
		{
			$link = 'index.php?option=com_mightysites&task=site.login&domain='.base64_encode($row->domain).'&return='.base64_encode($link);
		}
		
		return JRoute::_($link);
	}
	
	// Mod_security - that's for you, my dear!
	protected function showForm($link, $name = null)
	{
		$link = str_replace('&amp;', '&', $link);
		
		$parts = parse_url($link);
		
		$hiddens = array();
		
		if (isset($parts['query']))
		{
			parse_str($parts['query'], $vars);
			
			foreach ($vars as $key => $value)
			{
				$hiddens[] = '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
			}
		}
		 
		$html = '<form action="'.$parts['path'].'" target="_blank" method="post" id="'.$name.'" name="'.$name.'" style="display:none">';
		$html .= implode("\r\n", $hiddens);
		$html .= '</form>';
		
		return $html;
	}
}
