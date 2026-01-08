<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('groupedlist');

jimport('joomla.filesystem.folder');

class JFormFieldYoothemestyle extends JFormFieldGroupedList
{
	protected $type = 'Yoothemestyle';

	protected function getGroups()
	{
		$groups = array();
		
		// Add Module Style Field
		$groups[][] = JHtml::_('select.option', '', JText::_('JOPTION_USE_DEFAULT'));
		
		
		foreach ($this->getTemplates() as $template => $data)
		{
			$group = ucfirst(substr($template, 4));

			// YooTheme pro
			if (file_exists($config_path = JPATH_SITE . '/templates/' . $template . '/less'))
			{
			}
			// Warp 6
			elseif (file_exists($config_path = JPATH_SITE . '/templates/' . $template . '/config'))
			{
				$config_data = json_decode(file_get_contents($config_path));
				
				$profiles = array_keys(get_object_vars($config_data->profile_data));
				
				$groups[$group] = array();

				foreach ($profiles as $profile)
				{
					$groups[$group][] = JHtml::_('select.option', '_current_profile.'.$template.'.'.$profile, $group . ' - ' . $profile);
				}
			}
			// Warp 7
			elseif (file_exists(JPATH_SITE . '/templates/' . $template . '/config.json'))
			{
				$styles_path = JPATH_SITE . '/templates/' . $template . '/styles';
				
				if (is_dir($styles_path))
				{
					$groups[$group] = array();
					
					foreach (JFolder::folders($styles_path) as $style)
					{
						$groups[$group][] = JHtml::_('select.option', '_style.'.$template.'.'.$style, $group . ' - ' . ucfirst($style));
					}
				}
			}
		}
		
		return $groups;
	}
	
	protected function getTemplates()
	{
		$db = JFactory::getDbo();

		// Get the database object and a new query object.
		$query = $db->getQuery(true);

		// Build the query.
		$query->select('element, name, enabled')
			->from('#__extensions')
			->where('client_id = 0')
			->where('type = ' . $db->quote('template'))
			->where('name LIKE ' . $db->quote('yoo_%'));

		// Set the query and load the templates.
		$db->setQuery($query);
		$templates = $db->loadObjectList('element');

		return $templates;
	}
}
