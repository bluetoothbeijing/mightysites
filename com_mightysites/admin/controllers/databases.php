<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controlleradmin'); 

class MightysitesControllerDatabases extends JControllerAdmin
{
	protected $text_prefix = 'COM_MIGHTYSITES_DATABASES';

	public function getModel($name = 'Database', $prefix = 'MightysitesModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);
		return $model;
	}

	public function remove()
	{
		// Access check.
		if (!JFactory::getUser()->authorise('core.delete', 'com_mightysites')) {
			return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
		}
		
		$app = JFactory::getApplication();

		$ids	= $app->input->get('cid', array(), 'array');
		$n		= count($ids);
		JArrayHelper::toInteger($ids);

		$delete_tables = $app->input->get('delete_tables');
		
		if ($n) {
			$n = 0;
			foreach ($ids as $id) {
				$site 	= MightysitesHelper::getSite($id);
				
				// Only databases!
				if ($site->type == 2) {
					// Delete Tables & Views
					if ($delete_tables == 'true') {
						$db2 = MightysitesHelper::getDBO($site);
						
						$tables = MightysitesHelper::getTables($db2);
						if (count($tables)) {
							foreach ($tables as $table) {
								if (strpos($table, $site->dbprefix) === 0) {
									$db2->setQuery('DROP TABLE `'.$table.'`');
									$db2->execute() or $app->enqueueMessage($db2->getErrorMsg(), 'error');
								}
							}
						}

						$views = MightysitesHelper::getViews($db2);
						if (count($views)) {
							foreach ($views as $view) {
								if (strpos($view, $site->dbprefix) === 0) {
									$db2->setQuery('DROP VIEW `'.$view.'`');
									$db2->execute() or $app->enqueueMessage($db2->getErrorMsg(), 'error');
								}
							}
						}
						
						// restore current $db
						$db	= JFactory::getDBO();
						$db = null;
					}
					// delete in database
					$db	= JFactory::getDBO();
					$query = 'DELETE FROM #__mightysites WHERE id = ' . $id;
					$db->setQuery($query);
					if (!$db->execute()) {
						JError::raiseWarning( 500, $row->getError());
					}
					$n++;
				}
			}
		}
		
		$mes = $n ? JText::_('COM_MIGHTYSITES_DATABASES_DELETED') : null;
		$this->setRedirect('index.php?option=com_mightysites&view=databases', $mes);
	}
	
	public function cancel()
	{
		$this->setRedirect('index.php?option=com_mightysites&view=databases');

		// Initialize variables
		$db		= JFactory::getDBO();
		$post	= JRequest::get('post');
		$row	= JTable::getInstance('Site', 'Mightysites');
		$row->bind($post);
		$row->checkin();
	}

}