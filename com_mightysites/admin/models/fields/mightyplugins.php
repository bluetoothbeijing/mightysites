<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('groupedlist');

class JFormFieldMightyPlugins extends JFormFieldGroupedList
{
	/**
	 * The field type.
	 *
	 * @var    string
	 * @since  11.4
	 */
	protected $type = 'MightyPlugins';

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array  An array of JHtml options.
	 *
	 * @since   11.4
	 */
	protected function getGroups()
	{
		// Get list of plugins
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('CONCAT(folder, ":", element) AS value, name AS text, folder, element')
			->from('#__extensions')
			//->where('enabled = 1')
			->where('folder != ""')
			->where('element != "mightysites"')
			->order('folder, ordering, name');
		$db->setQuery($query);

		$plugins = $db->loadObjectList();

		$lang = JFactory::getLanguage();

		foreach ($plugins as $i => $item)
		{
			$source = JPATH_PLUGINS . '/' . $item->folder . '/' . $item->element;
			$extension = 'plg_' . $item->folder . '_' . $item->element;
				$lang->load($extension . '.sys', JPATH_ADMINISTRATOR, null, false, true)
			||	$lang->load($extension . '.sys', $source, null, false, true);
			
			$group = ucfirst($item->folder);
			
			// Initialize the group if necessary.
			if (!isset($groups[$group]))
			{
				$groups[$group] = array();
			}

			$groups[$group][] = JHtml::_('select.option', $item->value, JText::_($item->text), 'value', 'text', false);
		}

		// Merge any additional groups in the XML definition.
		$groups = array_merge(parent::getGroups(), $groups);

		return $groups;
	}
}
