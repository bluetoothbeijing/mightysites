<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('_JEXEC') or die;

jimport('joomla.application.component.modeladmin');


class MightysitesModelDatabase extends JModelAdmin
{

	protected $text_prefix = 'COM_MIGHTYSITES_DATABASES_';

	protected function canDelete($record)
	{
		if (!empty($record->id)) {
			// Don't delete sites!
			if ($record->type == 1) {
				return ;
			}

			return parent::canDelete($record);
		}
	}

	public function getTable($type = 'Database', $prefix = 'MightysitesTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param	array	$data		An optional array of data for the form to interogate.
	 * @param	boolean	$loadData	True if the form is to load its own data (default case), false if not.
	 * @return	JForm	A JForm object on success, false on failure
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app	= JFactory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_mightysites.database', 'database', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) {
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	The data for the form.
	 * @since	1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_mightysites.edit.database.data', array());

		if (empty($data)) {
			$data = $this->getItem();

			// Prime some default values.
			if ($this->getState('database.id') == 0) {
				$app = JFactory::getApplication();
				
				$data->set('type', 		2);
				$data->set('db', 		$app->getCfg('db'));
			}
		}

		return $data;
	}

	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk)) {
			
			if ($item->id) {
				
			}
			else {
				// Force type
				$item->type = 2;
			}
		}
		
		return $item;
	}
	
	public function save($data)
	{
		// Update database
		$result = parent::save($data);
		if ($result !== true) {
			return $result;
		}

		$isNew 	= $this->getState($this->getName() . '.new');
		$id 	= $this->getState($this->getName() . '.id');
		
		// Load new row
		$row = $this->getTable();
		$row->load($id);
		$row->params = new JRegistry($row->params);

		// Copy tables
		if ($isNew) {
			if ($data['source_db']) {
				$session = JFactory::getSession();
				$session->set('mighty_copy', 'index.php?option=com_mightysites&task=database.copy&from='.$data['source_db'].'&to='.$row->id.'&tmpl=component');
			}
		}
		
		return true;
	}

}
