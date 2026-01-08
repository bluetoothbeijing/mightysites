<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');

class MightysitesModelDatabases extends JModelList
{

	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'domain', 'a.domain',
				'type', 'a.type',
				'db', 'a.db',
				'dbprefix', 'a.dbprefix',
			);
		}

		parent::__construct($config);
	}

	protected function populateState($ordering = null, $direction = null)
	{
		$app = JFactory::getApplication('administrator');

		// Load the filter state.
		$search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		// Load the filter type.
		$type = $this->getUserStateFromRequest($this->context.'.filter.type', 'filter_type');
		$this->setState('filter.type', $type);

		// Load the parameters.
		$params = JComponentHelper::getParams('com_mightysites');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.id', 'asc');
	}

	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.type');

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		// Create a new query object.
		$db		= $this->getDbo();
		$query	= $db->getQuery(true);
		$user	= JFactory::getUser();

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'a.*'
			)
		);
		$query->from($db->quoteName('#__mightysites').' AS a');

		// Join over the users for the checked out user.
		$query->select('uc.name AS editor');
		$query->join('LEFT', '#__users AS uc ON uc.id = a.checked_out');

		// Filter by type.
		$type = $this->getState('filter.type');
		if ($type)
		{
			$query->where('a.type='.$type);
		}
		
		// Filter by search in title
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = '.(int) substr($search, 3));
			}
			else
			{
				$search = $db->Quote('%'.$db->escape($search, true).'%');
				$query->where('(
					a.domain LIKE '.$search.' 
					OR a.db LIKE '.$search.' 
					OR a.dbprefix LIKE '.$search.' 
				)');
			}
		}

		// Add the list ordering clause.
		$orderCol	= $this->state->get('list.ordering');
		$orderDirn	= $this->state->get('list.direction');
		$query->order($db->escape($orderCol.' '.$orderDirn));

		return $query;
	}
	
	public function getItems()
	{ 
		$items = parent::getItems();
		
		if ($items)
		{
			foreach ($items as &$item)
			{
				// Init params
				$item->params = new JRegistry($item->params);
				
				// Add config for sites
				if ($item->type == 1)
				{
					MightysitesHelper::attachConfig($item);
				}
			}
		}
		
		return $items;
	}
}
