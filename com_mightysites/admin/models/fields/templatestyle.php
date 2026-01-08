<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('list');

/**
 * Form Field class for the Joomla Platform.
 * Supports a list of installed application languages
 *
 * @package     Joomla.Platform
 * @subpackage  Form
 * @see         JFormFieldContentLanguage for a select list of content languages.
 * @since       11.1
 */
class JFormFieldTemplatestyle extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $type = 'Templatestyle';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   11.1
	 */
	protected function getOptions()
	{
		$options = array();
		
		$db = JFactory::getDBO();
		$query = 'SELECT id, template, title FROM #__template_styles WHERE client_id=0 ORDER BY template, title';
		$db->setQuery($query);
		
		$tmp = '';
		foreach ($db->loadObjectList() as $style) {
			if ($tmp != $style->template) {
				if ($tmp) {
					$options[] = JHtml::_('select.optgroup', $tmp); 
				}
				$options[] = JHtml::_('select.optgroup', $style->template); 
				$tmp = $style->template;
			}
			$options[] = JHTML::_('select.option', $style->id, $style->title, 'value', 'text');
		}
		
		
		// Merge any additional options in the XML definition.
		$options = array_merge(
			parent::getOptions(),
			$options
		);

		return $options;
	}
}
