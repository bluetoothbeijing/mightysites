<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controllerform');


class MightysitesControllerDatabase extends JControllerForm
{
	public function getModel($name = 'Database', $prefix = 'MightysitesModel', $config = array('ignore_request' => false))
	{
		$model = parent::getModel($name, $prefix, $config);
		return $model;
	}
	
	public function create()
	{
		// Access check.
		if (!JFactory::getUser()->authorise('core.create', 'com_mightysites')) {
			return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
		}

		$app 	= JFactory::getApplication();
		$db 	= JFactory::getDBO();
		
		// Check for request forgeries
		JSession::checkToken() or jexit('Invalid Token');
		
		// Get vars
		$dbname = $app->input->getString('db');
		
		if (!$db->connected()) {
			echo '<span style="color:red">', JText::_('COM_MIGHTYSITES_ERROR_INVALID_USER'), '</span>';
		}
		else {
			$db->setQuery('CREATE DATABASE `'.$dbname.'` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');
			
			try {
				$db->execute();
			}
			catch (RuntimeException $e) {
				echo '<span style="color:red">', JText::sprintf('COM_MIGHTYSITES_ERROR_CUSTOM', $db->getErrorMsg()), '</span>';
				exit();
			}
			
			echo '<span style="color:green">', JText::sprintf('COM_MIGHTYSITES_DATABASE_CREATED', $dbname), '</span>';
		}
		
		JFactory::getApplication()->close();
	}
	
	public function check()
	{
		$app = JFactory::getApplication();
		
		// Check for request forgeries
		JSession::checkToken() or jexit('Invalid Token');
		
		// Get vars
		$db 		= $app->input->getString('db');
		$dbprefix 	= $app->input->getString('dbprefix');
		$user 		= $app->input->getString('user');
		$password 	= $app->input->getString('password');
		$id 		= $app->input->getInt('id');
		$type 		= $app->input->getInt('type');
		
		// Use current?
		if (empty($user))
		{
			$user 		= $app->getCfg('user');
			$password 	= $app->getCfg('password');
		}
		
		// Close current connection, otherwise no mysqli_connect() errors
		JFactory::getDBO()->__destruct();
		
		// Check connection
		$link = @mysqli_connect($app->getCfg('host'), $user, $password);
		if ($link === false)
		{
			echo '<span style="color:red">', JText::_('COM_MIGHTYSITES_ERROR_INVALID_USER'), '</span>';
		}
		else
		{
			if (!mysqli_select_db($link, $db))
			{
				echo '<span style="color:red">', JText::sprintf('COM_MIGHTYSITES_ERROR_CUSTOM', mysqli_error($link)), '</span>';
			}
			else
			{
				if ($type == 1)
				{
					if ($id)
					{
						echo '<span style="color:green">', JText::_('COM_MIGHTYSITES_CONNECTED'), '</span>';
					}
					else
					{
						if (mysqli_query($link, 'SELECT COUNT(*) FROM ' . $dbprefix . 'users'))
						{
							echo '<span style="color:#f0ad4e">', JText::sprintf('COM_MIGHTYSITES_WARNING_TABLES_EXIST', $dbprefix, $db), '</span>';
						}
						else
						{
							echo '<span style="color:green">', JText::_('COM_MIGHTYSITES_CONNECTED'), '</span>';
						}
					}
				}
				elseif ($type == 2)
				{
					if (!mysqli_query($link, 'SELECT COUNT(*) FROM ' . $dbprefix . 'users'))
					{
						echo '<span style="color:red">', JText::sprintf('COM_MIGHTYSITES_ERROR_NO_TABLES_EXIST', $dbprefix, $db), '</span>';
					}
					else
					{
						echo '<span style="color:green">', JText::_('COM_MIGHTYSITES_CONNECTED'), '</span>';
					}
				}
				
				mysqli_close($link);
			}
		}
		
		// Fix Joomla db
		$this->restoreDB();
		
		// Fix ugly mysqli notice
		error_reporting(0);
		
		JFactory::getApplication()->close();
	}
	
	public function copy()
	{
		$app = JFactory::getApplication();
		
		// Access check.
		if (!JFactory::getUser()->authorise('core.create', 'com_mightysites')) {
			return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
		}

		$from 		= $app->input->get('from');
		$to 		= $app->input->get('to');
		$table		= $app->input->getInt('table', 0);
		$tmpl 		= $app->input->get('tmpl');
		
		// Get source site
		$from = MightysitesHelper::getSite($from, true);
		if (!isset($from->id)) {
			JError::raiseError(500, JText::_('COM_MIGHTYSITES_INVALID_SOURCE'));
		}
		
		// Get destination site
		$to = MightysitesHelper::getSite($to, true);
		
		// Check
		if (($from->db == $to->db) && ($from->dbprefix == $to->dbprefix)) {
			$this->setRedirect('index.php?option=com_mightysites', JText::_('COM_MIGHTYSITES_SOURCE_DEST_EQUAL'));
		}
		
		// Get Dbo
		$db = MightysitesHelper::getDBO($from);
		
		// Get tables & views list from site.
		$tables = MightysitesHelper::getTables($db);
		$views 	= MightysitesHelper::getViews($db);
		
		// Main array, tables are copied first because they are used in views.
		$tables_views = array_merge($tables, $views);

		// Filter tables without source prefix
		if (count($tables_views)) {
			foreach ($tables_views as $key => $value) {
				if (strpos($value, $from->dbprefix) !== 0) {
					unset($tables_views[$key]);
				}
				// Filter dest tables if we use same db
				if ($from->db == $to->db && strpos($value, $to->dbprefix) === 0) {
					unset($tables_views[$key]);
				}
			}
			// Rebuild keys
			$tables_views = explode(',,,', implode(',,,', $tables_views));
		}
		
		// Remember views code.
		$views_code = array();
		foreach ($tables_views as $tables_view) {
			if (in_array($tables_view, $views)) {
				$db->setQuery('SHOW CREATE VIEW `'.$tables_view.'`');
				$vres = $db->loadAssoc();
				if (isset($vres['Create View'])) {
					$views_code[$tables_view] = $vres['Create View'];
				}
			}
		}

		// Check tables
		if (!count($tables_views)) {
			JError::raiseError(500, JText::sprintf('COM_MIGHTYSITES_NO_TABLES', $from->domain, $from->db));
		}
		
		// source
		$source_table = $tables_views[$table];
		
		// destination
		$dest_table = $to->dbprefix . substr($source_table, strlen($from->dbprefix));
		
		// Table or View?
		$isTable 	= in_array($source_table, $tables);
		$isView 	= in_array($source_table, $views);
		
		// Connect to dest DB
		$db2 = MightysitesHelper::getDBO($to);
		
		JToolBarHelper::title('MightySites : ' . JText::_('COM_MIGHTYSITES_COPYING_TABLES'), 'config');
		
		echo '<h1>', JText::sprintf('COM_MIGHTYSITES_CREATING_TABLE', ($table+1), count($tables_views), $dest_table), '</h1>';
		echo '<h3>', JText::sprintf('COM_MIGHTYSITES_SOURCE_DATABASE', $from->db, $from->dbprefix), '</h3>';
		echo '<h3>', JText::sprintf('COM_MIGHTYSITES_DEST_DATABASE', $to->db, $to->dbprefix), '</h3>';
		echo '<h3 style="text-decoration:blink">', JText::_('COM_MIGHTYSITES_PLEASE_WAIT', $to->db, $to->dbprefix), '</h3>';
		
		$error = false;
		
		// Try to delete first
		if ($isTable) {
			$query = 'DROP TABLE IF EXISTS `'.$dest_table.'`';
		}
		if ($isView) {
			$query = 'DROP VIEW IF EXISTS `'.$dest_table.'`';
		}
		$db2->setQuery($query);
		
		if (JDEBUG) {
			echo $db2->getQuery().'<br/>';
		}
		
		if (!$db2->execute()) {
			die('<span style="color:red">'.$db2->getErrorMsg().'</span>');
		}

		// Create table or view
		if ($isTable) {
			$query = 'CREATE TABLE `'.$dest_table.'` LIKE `'.$from->db.'`.`'.$source_table.'`';
		}
		if ($isView) {
			if (isset($views_code[$source_table])) {
				$query = strtr($views_code[$source_table], array(
					'`'.$source_table.'`' 	=> '`'.$dest_table.'`',
					'`'.$from->dbprefix 	=> '`'.$to->dbprefix,
				));
			} else {
				$query = 'SELECT 1';
			}
		}

		$db2->setQuery($query);
		
		if (JDEBUG) {
			echo $db2->getQuery().'<br/>';
		}
		
		if (!$db2->execute()) {
			// can't copy view
			if ($db2->getErrorNum() == 1347) {
				// todo - probably do smth here
			}
			echo '<p style="color:red">'.$db2->getErrorMsg().'</p>';
			$error = true;
		}
		else {

			// Next copy data table
			if ($isTable) {
				$query = 'INSERT INTO `'.$dest_table.'` SELECT * FROM `'.$from->db.'`.`'.$source_table.'`';
				$db2->setQuery($query);
				
				if (JDEBUG) {
					echo $db2->getQuery().'<br/>';
				}
				
				if (!$db2->execute()) {
					echo '<span style="color:red">'.$db2->getErrorMsg().'</span>';
					$error = true;
				}
			}
		}
		
		$table++;
		
		// Finish
		if ($table == count($tables_views)) {
			if ($tmpl == 'component') {?>
				<script type="text/javascript" language="javascript">
					window.parent.SqueezeBox.close();
					alert('<?php echo JText::sprintf('COM_MIGHTYSITES_TABLES_CREATED', $to->db, $to->dbprefix);?>');
				</script>
				<?php
				exit();
			} else {
				$this->setRedirect('index.php?option=com_mightysites&view=databases', JText::sprintf('COM_MIGHTYSITES_TABLES_CREATED', $to->db, $to->dbprefix));
			}
		}
		// or Continue to next table
		else {
			JHtml::_('behavior.framework');
			$link = JRoute::_('index.php?option=com_mightysites&task=database.copy&from='.$from->id.'&to='.$to->id.'&table='.$table.'&tmpl='.$tmpl, false);
			// no errors - let's auto proceed
			if (!$error) {
				JFactory::getDocument()->addScriptDeclaration(
					'window.addEvent("load", function(){document.location.href="'.$link.'"});'
				);
			}
			// errors! show them!
			else {
				echo '<a href="', $link, '">', JText::_('COM_MIGHTYSITES_CONTINUE'), '</a>';
			}
		}
	}
	
	protected function restoreDB()
	{
		JFactory::$database = null;
	}

}
