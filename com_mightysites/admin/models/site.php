<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('_JEXEC') or die;

jimport('joomla.filesystem.file');

jimport('joomla.application.component.modeladmin');

class MightysitesModelSite extends JModelAdmin
{
	protected $text_prefix = 'COM_MIGHTYSITES_SITES_';

	protected function canDelete($record)
	{
		if (!empty($record->id)) {
			// Don't delete root!
			if ($record->id == 1) {
				return ;
			}
			// Don't delete databases!
			if ($record->type == 2) {
				return ;
			}

			return parent::canDelete($record);
		}
	}

	public function getTable($type = 'Site', $prefix = 'MightysitesTable', $config = array())
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
		// Get the form.
		$form = $this->loadForm('com_mightysites.site', 'site', array('control' => 'jform', 'load_data' => $loadData));
		
		// Require fields
		if ($this->getState('site.id'))
		{
			$form->setFieldAttribute('source_config', 'required', 'false');

			// Don't edit secret of this site.
			/*
			if ($this->getState('site.id') == MightysitesHelper::getCurrentSite()->id) {
				$form->setFieldAttribute('secret', 'readonly', 'true', 'params');
			}
			*/
			
			// If site uses same database - we can prettify it a bit
			$config = JFactory::getConfig();
			$site 	= MightysitesHelper::getSite($this->getState('site.id'), true);
			
			if ($site->db == $config->get('db') && $site->dbprefix == $config->get('dbprefix'))
			{
				// Pretiify Template Style
				$form->setFieldAttribute('template', 'type', 'templatestyle', 'params');
				$form->setFieldAttribute('template', 'class', '', 'params');

				// Pretiify Home
				$form->setFieldAttribute('home', 'type', 'menuitem', 'params');
				$form->setFieldAttribute('home', 'class', '', 'params');
			}
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
		$data = JFactory::getApplication()->getUserState('com_mightysites.edit.site.data', array());

		if (empty($data))
		{
			$data = $this->getItem();

			// Prime some default values.
			if ($this->getState('site.id') == 0)
			{
				$app = JFactory::getApplication();
				
				$data->set('type', 		1);
				$data->set('db', 		$app->getCfg('db'));
				$data->set('dbprefix', 	$app->getCfg('dbprefix'));
				$data->set('user', 		$app->getCfg('user'));
				$data->set('password', 	$app->getCfg('password'));
			}
		}

		return $data;
	}

	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk)) {
			
			if ($item->id)
			{
				// add config
				MightysitesHelper::attachConfig($item);
				
				// pass legacy vars
				// todo - remove for Joomla 3.0
				if (isset($item->mighty_language)) {
					$item->params['language'] 				= $item->mighty_language;
				}
				if (isset($item->mighty_template)) {
					$item->params['template'] 				= $item->mighty_template;
				}
				if (isset($item->mighty_force_template)) {
					$item->params['force_template'] 		= $item->mighty_force_template;
				}
				if (isset($item->mighty_home)) {
					$item->params['home'] 					= $item->mighty_home;
				}
				if (isset($item->mighty_langoverride)) {
					$item->params['langoverride'] 			= $item->mighty_langoverride;
				}
				if (isset($item->mighty_enable)) {
					$item->params['enable_replacements'] 	= $item->mighty_enable;
				}
				if (isset($item->params['slogin'])) {
					$item->params['single_login'] 			= $item->params['slogin'];
				}
				if (isset($item->params['slogout'])) {
					$item->params['single_logout'] 			= $item->params['slogout'];
				}
				if (isset($item->secret)) {
					$item->params['secret'] 				= $item->secret;
				}
				if (isset($item->cookie_domain)) {
					$item->params['cookie_domain'] 			= $item->cookie_domain;
				}
				
				// Legacy before 3.3.1. @todo - remove in 2018
				if (!isset($item->mighty_hidemenuitems))
				{
					$item->mighty_hidemenuitems = $item->mighty_hidemenus;
					$item->mighty_onlymenuitems = $item->mighty_onlymenus;

					$item->mighty_hidemenus = '';
					$item->mighty_onlymenus = '';
				}
				// legacy stop
				
				if (isset($item->mighty_hidemenus)) {
					$item->params['hidemenus'] 			= $item->mighty_hidemenus;
				}
				if (isset($item->mighty_onlymenus)) {
					$item->params['onlymenus'] 			= $item->mighty_onlymenus;
				}
				if (isset($item->mighty_hidemenuitems)) {
					$item->params['hidemenuitems'] 			= $item->mighty_hidemenuitems;
				}
				if (isset($item->mighty_onlymenuitems)) {
					$item->params['onlymenuitems'] 			= $item->mighty_onlymenuitems;
				}
			}
			else
			{
				// Force type
				$item->type = 1;
			}
		}
		
		return $item;
	}

	public function save($data)
	{
		$app = JFactory::getApplication();
		
		// Prepare domain name
		$data['domain'] = trim(strtolower($data['domain']), ' ./\\');
		
		if (substr($data['domain'], 0, 7) == 'http://') {
			$data['domain'] = substr($data['domain'], 7);
		}
		if (substr($data['domain'], 0, 8) == 'https://') {
			$data['domain'] = substr($data['domain'], 8);
		}
		/* Let's allow www. prefix
		if (substr($data['domain'], 0, 4) == 'www.') {
			$data['domain'] = substr($data['domain'], 4);
		}
		*/
		if (empty($data['domain']))
		{
			$this->setError(JText::_('COM_MIGHTYSITES_ERROR_DOMAIN_EMPTY'));
			return false;
		}
		
		// Prepare single login/logout params
		$single_domain 	= $app->input->get('single_domain', array(), 'array');
		$single_key 	= $app->input->get('single_key', 	array(), 'array');
		
		if (count($single_domain))
		{
			$i = 0;
			$data['params']['single_domains'] = array();
			
			foreach ($single_domain as $value)
			{
				if (!empty($value))
				{
					$value = trim(strtolower($value), ' /');
					
					// Not to itself!
					if ($value == $data['domain']) {
						continue;
					}
					
					$data['params']['single_domain'.(string)$i] 	= $value;
					$data['params']['single_key'.(string)$i] 		= trim($single_key[$i]);
					
					$data['params']['single_domains'][$value] = trim($single_key[$i]);
				}
				$i++;
			}
		}
		
		// Prepare Aliases
		if (strlen($data['aliases']))
		{
			$data['aliases'] = strtolower($data['aliases']);
			$data['aliases'] = strtr($data['aliases'], "\r\n", "\n");
			$data['aliases'] = array_filter(explode("\n", $data['aliases']));
			
			foreach ($data['aliases'] as &$alias)
			{
				$alias = trim($alias, '/ ');
				if (substr($alias, 0, 7) == 'http://') {
					$alias = substr($alias, 7);
				}
				if (substr($alias, 0, 8) == 'https://') {
					$alias = substr($alias, 8);
				}
				if (substr($alias, 0, 4) == 'www.') {
					$alias = substr($alias, 4);
				}
				// Still smbd will try to enter this here!
				if ($alias == $data['domain']) {
					$alias = '';
				}
				// Alias can't be current domain
				if (MightysitesHelper::getSite($alias)) {
					$app->enqueueMessage(JText::sprintf('COM_MIGHTYSITES_ERROR_ALIAS_EXISTS_SITE', $alias), 'error');	
					$alias = '';
				}
				// Check aliases of other sites - no duplicates!
				foreach (MightysitesHelper::getSites() as $site2)
				{
					if ($site2->aliases)
					{
						$aliases2 = explode("\n", $site2->aliases);
						
						if (in_array($alias, $aliases2) && $site2->id != $data['id'])
						{
							$app->enqueueMessage(JText::sprintf('COM_MIGHTYSITES_ERROR_ALIAS_EXISTS', $alias, $site2->domain), 'error');	
							$alias = '';
						}
					}
				}
			}
			
			$data['aliases'] = implode("\n", array_filter($data['aliases']));
		}
		
		// get all fields
		$jform = $app->input->get('jform', array(), 'array');
		
		// add tables
		if (isset($jform['tables']) && is_array($jform['tables']))
		{
			foreach ($jform['tables'] as $i => $value) {
				if ($value) {
					$data['params']['table_'.$i] = $value;
				}
			}
		}
		
		// New site?
		$isNew = empty($data['id']);
		
		if ($isNew)
		{
			// Load source site.
			$source_site = $data['source_config'];
			$source_site = MightysitesHelper::getSite($source_site);
			
			// New site will get source params
			$data['params'] = $source_site->params->toArray();
			
			// But not this, otherwise for sure nobody will login.
			unset($data['params']['cookie_domain']);
		}

		// add content
		$data['params']['content'] = array();
		if (isset($jform['content']) && is_array($jform['content']))
		{
			foreach ($jform['content'] as $pk => $pv) {
				if ($pv !== '') {
					$data['params']['content'][$pk] = $pv;
				}
			}
		}

		// Load current row
		$id = $this->getState($this->getName() . '.id');

		$before = $this->getTable();
		$before->load($id);
		$before->params = new JRegistry($before->params);
		
		// Update database
		$result = parent::save($data);
		if ($result !== true) {
			return $result;
		}

		// Load new row
		$id = $this->getState($this->getName() . '.id');
		
		$row = $this->getTable();
		$row->load($id);
		$row->params = new JRegistry($row->params);

		
		$params	= isset($data['params']) ? $data['params'] : array();
		
		if ($isNew)
		{
			// Create config
			$config = $this->loadConfig($source_site->domain);
			
			// New secret key
			jimport('joomla.user.helper');
			$config->secret = JUserHelper::genRandomPassword(16);
			// new $live_site, just empty
			$config->live_site = '';
			// none session handler, database doesn't work sometimes
			//$config->session_handler = 'none';
			// empty cookie domain
			$config->cookie_domain  = '';
		}
		else
		{
			// Domain changed? Compare non-www entries!
			$before_domain = (substr($before->domain, 0, 4) == 'www.') ? substr($before->domain, 4) : $before->domain;
			$row_domain    = (substr($row->domain, 0, 4) == 'www.') ? substr($row->domain, 4) : $row->domain;
			
			if ($before_domain != $row_domain)
			{
				$this->copyConfig($before->domain, $row->domain);
				$this->removeConfig($before->domain);
			}

			// Load current config
			$config = $this->loadConfig($row->domain);
		}

		// New system settings.
		$config->db 			= $data['db'];
		$config->user 			= $data['user'];
		$config->dbprefix 		= $data['dbprefix'];
		$config->password 		= $data['password'];
		$config->dbtype 		= $data['dbtype'];
		
		if (isset($params['secret']) && $params['secret']) {
			$config->secret 		= $params['secret'];
		}
		if (isset($params['cookie_domain'])) {
			$config->cookie_domain 	= $params['cookie_domain'];
		}

		// Create replacements
		MightysitesHelper::attachConfig($row, $config);
		
		// Next part is only active for existing site, so new site with config cloned from source config has data of source site.
		if (!$isNew)
		{
			$replacements = $this->createTablesReplacements($row);
			
			// Check dummies
			if (isset($replacements[0]) && is_array($replacements[0]) && count($replacements[0]) > 200)
			{
				$app->enqueueMessage(JText::_('СOM_MIGHTYSITES_ERROR_TOO_MUCH_SHARINGS'), 'notice');
			}
			
			// Inject our settings
			// Params can be absent for new site!
			$config->mighty 				= $replacements;
			$config->mighty_enable 			= isset($params['enable_replacements']) ? $params['enable_replacements'] : '';
			$config->mighty_language 		= isset($params['language']) ? $params['language'] : '';
			$config->mighty_template 		= isset($params['template']) ? $params['template'] : '';
			$config->mighty_force_template 	= isset($params['force_template']) ? $params['force_template'] : '';
			$config->mighty_home 			= isset($params['home']) ? $params['home'] : '';
			$config->mighty_langoverride 	= isset($params['langoverride']) ? $params['langoverride'] : '';
			$config->cache_path 			= isset($params['cache_path']) ? $params['cache_path'] : '';
			$config->mighty_slogin 			= isset($params['single_login']) ? $params['single_login'] : '';
			$config->mighty_slogout 		= isset($params['single_logout']) ? $params['single_logout'] : '';
			$config->mighty_sdomains 		= isset($params['single_domains']) ? $params['single_domains'] : '';
			$config->mighty_css 			= isset($params['custom_css']) ? ( $params['custom_css'] ? explode("\n", str_replace("\r\n", "\n", $params['custom_css'])) : array() ) : array();
			$config->mighty_js 				= isset($params['custom_js']) ? $params['custom_js'] : '';
			$config->mighty_hidemenus 		= isset($params['hidemenus']) ? array_filter($params['hidemenus']) : '';
			$config->mighty_onlymenus 		= isset($params['onlymenus']) ? array_filter($params['onlymenus']) : '';
			$config->mighty_hidemenuitems 	= isset($params['hidemenuitems']) ? array_filter($params['hidemenuitems']) : '';
			$config->mighty_onlymenuitems 	= isset($params['onlymenuitems']) ? array_filter($params['onlymenuitems']) : '';
			$config->mighty_hidemodules 	= isset($params['hidemodules']) ? array_filter($params['hidemodules']) : '';
			$config->mighty_onlymodules 	= isset($params['onlymodules']) ? array_filter($params['onlymodules']) : '';
			$config->mighty_hideplugins 	= isset($params['hideplugins']) ? array_filter($params['hideplugins']) : '';
			$config->mighty_hidecontentcats = isset($params['hidecontentcats']) ? array_filter($params['hidecontentcats']) : '';
			$config->mighty_onlycontentcats = isset($params['onlycontentcats']) ? array_filter($params['onlycontentcats']) : '';
			$config->mighty_favicon 		= isset($params['favicon']) ? $params['favicon'] : '';
			$config->mighty_mijoshopid 		= isset($params['mijoshopid']) ? $params['mijoshopid'] : '';
			$config->mighty_file_path 		= isset($params['file_path']) ? $params['file_path'] : '';
			$config->mighty_image_path 		= isset($params['file_path']) ? $params['image_path'] : '';
			$config->mighty_new_usertype 	= isset($params['new_usertype']) ? $params['new_usertype'] : '';
			$config->mighty_yootheme_style 	= isset($params['yootheme_style']) ? $params['yootheme_style'] : '';
			$config->mighty_falang 			= isset($params['falang']) ? $params['falang'] : '';
		}
		
		// Save config
		$this->saveConfig($data['domain'], $config, $isNew);

		// Copy tables
		if ($isNew)
		{
			if ($data['source_db'])
			{
				$session = JFactory::getSession();
				$session->set('mighty_copy', 'index.php?option=com_mightysites&task=database.copy&from='.$data['source_db'].'&to='.$row->id.'&tmpl=component');
			}
		}

		// Update aliases
		if ($before->aliases != $row->aliases || $before->domain != $row->domain)
		{
			$before->aliases 	= explode("\n", $before->aliases);
			$row->aliases 		= explode("\n", $row->aliases);
			
			$old_aliases = array_diff($before->aliases, $row->aliases);
			$new_aliases = array_diff($row->aliases, $before->aliases);
			
			// Domain was changed - change all!
			if ($before->domain != $row->domain) {
				$new_aliases = $row->aliases;
			}
			
			if ($old_aliases) {
				foreach ($old_aliases as $old_alias) {
					$this->removeAlias($old_alias);
				}
			}

			if ($new_aliases) {
				foreach ($new_aliases as $new_alias) {
					$this->createAlias($row->domain, $new_alias);
				}
			}
		}
		
		// Auto-check if configuration.php is patched + auto-update default.php config
		MightysitesHelper::patchConfiguration();
		
		return true;
	}

	protected function loadConfig($domain)
	{
		$app = JFactory::getApplication();
		
		$fname 	= MightysitesHelper::getConfigFilename($domain);
		
		if (!file_exists($fname)) {
			$app->redirect('index.php?option=com_mightysites', JText::sprintf('COM_MIGHTYSITES_ERROR_NEW_CONFIG_DOESNT_EXIST', $fname), 'error');
		}
		if (!is_readable($fname)) {
			$app->redirect('index.php?option=com_mightysites', JText::sprintf('COM_MIGHTYSITES_ERROR_NEW_CONFIG_NOT_READABLE', $fname), 'error');
		}
		if (!is_writable(JPATH_CONFIGURATION)) {
			// not used, can be writable by FTP!
			//$app->redirect('index.php?option=com_mightysites', JText::sprintf('COM_MIGHTYSITES_ERROR_NEW_CONFIG_NOT_WRITABLE', fname), 'error');
		}
		
		$config = JFile::read($fname);
		
		$classname = 'JConfig'.uniqid();
				
		if (!class_exists($classname)) {
			$config = strtr($config, array(
				'<?php' 		=> '',
				'class JConfig' => 'class '.$classname,
			));
			eval($config);
		}
		
		if (class_exists($classname)) {
			$config = new $classname;
		}
		
		return $config;
	}
	
	protected function saveConfig($domain, $config, $isNew = false)
	{
		$app = JFactory::getApplication();
		
		$file = MightysitesHelper::getConfigFilename($domain);
		
		// Get the FTP credentials.
		$ftp = JClientHelper::getCredentials('ftp', true);

		// Attempt to make the file writeable if using FTP.
		if (file_exists($file)) {
			if (!$ftp['enabled'] && JPath::isOwner($file) && !JPath::setPermissions($file, '0644')) {
				$app->redirect('index.php?option=com_mightysites', JText::sprintf('COM_MIGHTYSITES_ERROR_CONFIG_NOT_WRITABLE', $file), 'error');
			}
		}
		
		$registry = new JRegistry($config);
		
		// Don't use JConfig to not trigger our plugin.
		$configuration = $registry->toString('PHP', array('class' => 'JConfigggg', 'closingtag' => false));
		$configuration = strtr($configuration, array(
			'JConfigggg' => 'JConfig',
		));
		
		if (!JFile::write($file, $configuration)) {
			$app->redirect('index.php?option=com_mightysites', JText::sprintf('COM_MIGHTYSITES_ERROR_CONFIG_WRITE_FAILED', $file), 'error');
		}
		
		// Try to make configuration.php unwriteable
		if (!$ftp['enabled'] && JPath::isOwner($file) && !JPath::setPermissions($file, '0444')) {
			$app->redirect('index.php?option=com_mightysites', JText::sprintf('COM_MIGHTYSITES_ERROR_CONFIG_NOT_UNWRITABLE', $file), 'error');
		}
	}
	
	protected function copyConfig($domain_from, $domain_to)
	{
		$app = JFactory::getApplication();
		
		$file_from 	= MightysitesHelper::getConfigFilename($domain_from);
		$file_to 	= MightysitesHelper::getConfigFilename($domain_to);
		
		if ($file_from != $file_to)
		{
			if (!JFile::copy($file_from, $file_to))
			{
				$app->redirect('index.php?option=com_mightysites', JText::sprintf('COM_MIGHTYSITES_ERROR_CONFIG_WRITE_FAILED', $file_to), 'error');
			}
		}
	}

	protected function removeConfig($domain)
	{
		$file = MightysitesHelper::getConfigFilename($domain);
		
		return JFile::delete($file);
	}
	
	protected function removeAlias($alias)
	{
		if (empty($alias)) {
			return;
		}
		
		$filename = MightysitesHelper::getConfigFilename($alias);
		
		if (file_exists($filename)) {
			JFile::delete($filename);
		}
	}

	protected function createAlias($domain, $alias)
	{
		if (empty($alias))
		{
			return;
		}
		
		$filename = MightysitesHelper::getConfigFilename($alias);
		
		if (file_exists($filename))
		{
			JFile::delete($filename);
		}
		
		$str = '<?php 
define(\'MIGHTY_DOMAIN\', \''.MightysitesHelper::prepareDomain($domain).'\');
define(\'MIGHTY_CONFIG\', __DIR__ . \'/configuration_'.MightysitesHelper::prepareDomain($domain).'.php\');
require_once MIGHTY_CONFIG;';

		if (!JFile::write($filename, $str))
		{
			JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_MIGHTYSITES_ERROR_ALIAS_WRITE_FAILED', $filename, $alias));	
		}
	}
	
	protected function createTablesReplacements(&$site, $tables = array())
	{
		$params = $site->params;
		$content = new JRegistry($params->get('content', array()));
		
		$data 	= array();
		$tables	= array();

		// Core extensions
		if ($content->get('extensions'))
		{
			$tables['extensions'] 			= $content->get('extensions');
			//$tables['schemas'] 				= $content->get('extensions');	// not share, otherwise Joomla version is lost in slave sites
			$tables['updates'] 				= $content->get('extensions');
			$tables['update_categories'] 	= $content->get('extensions');
			$tables['update_sites'] 		= $content->get('extensions');
			$tables['update_sites_extensions'] = $content->get('extensions');

			// load backend menu components also, for backend only.
			
			// Special script since 3.7, todo - remove once 3.7 is min
			if (version_compare(JVERSION, '3.7', 'ge'))
			{
				$data['/SELECT m.id, m.title, m.alias, m.link, m.parent_id, m.img, e.element, m.menutype\r?\nFROM #__menu AS m/'] = 
					'SELECT m.id, m.title, m.alias, m.link, m.parent_id, m.img, e.element, m.menutype FROM '.$this->_createTableReplacements($site, 'menu', $content->get('extensions')).' AS m';
			}
			else
			{
				$data['/SELECT m.id, m.title, m.alias, m.link, m.parent_id, m.img, e.element\r?\nFROM #__menu AS m/'] = 
					'SELECT m.id, m.title, m.alias, m.link, m.parent_id, m.img, e.element FROM '.$this->_createTableReplacements($site, 'menu', $content->get('extensions')).' AS m';
			}
		}
		if ($content->get('permissions')) {
			$tables['assets'] 				= $content->get('permissions');
		}
		if ($content->get('users')) {
			$tables['usergroups'] 			= $content->get('users');
			$tables['users'] 				= $content->get('users');
			$tables['user_notes'] 			= $content->get('users');
			$tables['user_profiles'] 		= $content->get('users');
			$tables['user_usergroup_map'] 	= $content->get('users');
			$tables['user_keys'] 			= $content->get('users');
			$tables['viewlevels'] 			= $content->get('users');
		}
		if ($content->get('sessions')) {
			$tables['session'] 				= $content->get('sessions');
		}
		if ($content->get('templates')) {
			$tables['template_styles'] 		= $content->get('templates');
		}
		if ($content->get('categories')) {
			$tables['categories'] 			= $content->get('categories');
		}
		if ($content->get('content')) {
			$tables['content'] 				= $content->get('content');
			$tables['content_frontpage'] 	= $content->get('content');
			$tables['content_rating'] 		= $content->get('content');
			$tables['content_types'] 		= $content->get('content');
			$tables['ucm_base'] 			= $content->get('content');
			$tables['ucm_content'] 			= $content->get('content');
			$tables['ucm_history'] 			= $content->get('content');
		}
		if ($content->get('languages')) {
			$tables['languages'] 			= $content->get('languages');
			$tables['overrider'] 			= $content->get('languages');
		}
		if ($content->get('menus')) {
			$tables['menu'] 				= $content->get('menus');
			$tables['menu_types'] 			= $content->get('menus');
			$tables['associations'] 		= $content->get('menus');
		}
		if ($content->get('modules')) {
			$tables['modules'] 				= $content->get('modules');
			$tables['modules_menu'] 		= $content->get('modules');
		}
		if ($content->get('newsfeeds')) {
			$tables['newsfeeds'] 			= $content->get('newsfeeds');
		}
		if ($content->get('weblinks')) {
			$tables['weblinks'] 			= $content->get('weblinks');
		}
		if ($content->get('banners')) {
			$tables['banners'] 				= $content->get('banners');
			$tables['banner_clients'] 		= $content->get('banners');
			$tables['banner_tracks'] 		= $content->get('banners');
		}
		if ($content->get('contacts')) {
			$tables['contact_details'] 		= $content->get('contacts');
		}
		if ($content->get('messages')) {
			$tables['messages'] 			= $content->get('messages');
			$tables['messages_cfg'] 		= $content->get('messages');
		}
		if ($content->get('tags')) {
			$tables['tags'] 				= $content->get('tags');
		}
		if ($content->get('tags_refs')) {
			$tables['contentitem_tag_map'] 	= $content->get('tags_refs');
		}
		if ($content->get('redirect')) {
			$tables['redirect_links'] 		= $content->get('redirect');
		}
		
		// 3rd-party extensions
		if ($content->get('smartsearch')) {
			$data['/`?#__finder_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'finder_', 	$content->get('smartsearch'), '') 		. '$1`';
		}
		if ($content->get('js')) {
			$data['/`?#__community_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'community_', 	$content->get('js'), '') 		. '$1`';
		}
		if ($content->get('js_no_config')) {
			$data['/(`?)(\#__community)((?!_config)[^0-9a-z_]*)/u'] 	= '$1' . str_replace('`.`', '$1.$1', $this->_createTableReplacements($site, 'community', 	$content->get('js_no_config'), '')) . '$4';
		}
		if ($content->get('cb')) {
			$data['/`?#__comprofiler([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'comprofiler', 	$content->get('cb'), '') 		. '$1`';
		}
		if ($content->get('mtree')) {
			$data['/`?#__mt_([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'mt_', 			$content->get('mtree'), '') 		. '$1`';
		}
		if ($content->get('sobi2')) {
			$data['/`?#__sobi2_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'sobi2_', 		$content->get('sobi2'), '') 		. '$1`';
		}
		if ($content->get('sobipro')) {
			$data['/`?#__sobipro_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'sobipro_', 		$content->get('sobipro'), '') 	. '$1`';
		}
		if ($content->get('gridiron')) {
			$data['/`?#__gridiron_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'gridiron_', 	$content->get('gridiron'), '') 	. '$1`';
		}
		if ($content->get('uddeim')) {
			$data['/`?#__uddeim([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'uddeim', 		$content->get('uddeim'), '') 	. '$1`';
		}
		if ($content->get('zoo')) {
			$data['/`?#__zoo_([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'zoo_', 			$content->get('zoo'), '') 		. '$1`';
		}
		if ($content->get('dtregister')) {
			$data['/`?#__dtregister([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'dtregister', 	$content->get('dtregister'), '') . '$1`';
		}
		if ($content->get('easyblog')) {
			$data['/`?#__easyblog_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'easyblog_', 	$content->get('easyblog'), '') 	. '$1`';
		}
		if ($content->get('easyblog_no_config')) {
			$data['/(`?)(\#__easyblog)((?!_configs)[^0-9a-z_]*)/u'] 	= '$1' . str_replace('`.`', '$1.$1', $this->_createTableReplacements($site, 'easyblog', 	$content->get('easyblog_no_config'), '')) . '$4';
		}
		if ($content->get('joomsport')) {
			$data['/`?#__bl_([0-9a-z_]*)`?/u'] 				= '`' . $this->_createTableReplacements($site, 'bl_', 			$content->get('joomsport'), '') 	. '$1`';
		}
		if ($content->get('rsform')) {
			$data['/`?#__rsform_([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'rsform_', 		$content->get('rsform'), '') 	. '$1`';
		}
		if ($content->get('rsfiles')) {
			$data['/`?#__rsfiles_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'rsfiles_', 		$content->get('rsfiles'), '') 	. '$1`';
		}
		if ($content->get('acymailing')) {
			$data['/`?#__acymailing_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'acymailing_', 	$content->get('acymailing'), '') . '$1`';
		}
		if ($content->get('jce')) {
			$tables['wf_profiles'] = $content->get('jce');
		}
		if ($content->get('adsmanager')) {
			$data['/`?#__adsmanager_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'adsmanager_', 	$content->get('adsmanager'), '') 	. '$1`';
		}
		if ($content->get('joomshopping')) {
			$data['/`?#__jshopping_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'jshopping_', 	$content->get('joomshopping'), '') 	. '$1`';
		}
		if ($content->get('kunena')) {
			$data['/`?#__kunena_([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'kunena_', 		$content->get('kunena'), '') 		. '$1`';
		}
		if ($content->get('k2')) {
			$data['/`?#__k2_([0-9a-z_]*)`?/u'] 				= '`' . $this->_createTableReplacements($site, 'k2_', 			$content->get('k2'), '') 			. '$1`';
		}
		if ($content->get('pf')) {
			$data['/`?#__pf_([0-9a-z_]*)`?/u'] 				= '`' . $this->_createTableReplacements($site, 'pf_', 			$content->get('pf'), '') 			. '$1`';
		}
		if ($content->get('jcp')) {
			$data['/`?#__jcp_([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'jcp_', 			$content->get('jcp'), '') 			. '$1`';
		}
		if ($content->get('enmasse')) {
			$data['/`?#__enmasse_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'enmasse_', 		$content->get('enmasse'), '') 		. '$1`';
		}
		if ($content->get('phocadownload')) {
			$data['/`?#__phocadownload([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'phocadownload', $content->get('phocadownload'), '') 	. '$1`';
		}
		if ($content->get('phocagallery')) {
			$data['/`?#__phocagallery([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'phocagallery', 	$content->get('phocagallery'), '') 	. '$1`';
		}
		if ($content->get('jreviews')) {
			$data['/`?#__jreviews_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'jreviews_', 	$content->get('jreviews'), '') 		. '$1`';
		}
		// A bit more complex for VM, it uses queries like
		// WHERE #__virtuemart_products_en_gb.`virtuemart_product_id`
		// FROM `#__virtuemart_products_en_gb` `p`
		if ($content->get('virtuemart')) {
			$data['/(`?)(\#__virtuemart)([^0-9a-z_]*)/u'] 	= '$1' . str_replace('`.`', '$1.$1', $this->_createTableReplacements($site, 'virtuemart', 	$content->get('virtuemart'), '')) . '$3';
		}
		if ($content->get('virtuemart_no_config')) {
			$data['/(`?)(\#__virtuemart)((?!_configs)[^0-9a-z_]*)/u'] 	= '$1' . str_replace('`.`', '$1.$1', $this->_createTableReplacements($site, 'virtuemart', 	$content->get('virtuemart_no_config'), '')) . '$4';
		}
		if ($content->get('jevents')) {
			$data['/`?#__jevents_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'jevents_', 		$content->get('jevents'), '') 				. '$1`';
		}
		if ($content->get('hwdms')) {
			$data['/`?#__hwdms_([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'hwdms_', 		$content->get('hwdms'), '') 					. '$1`';
		}
		if ($content->get('hwdms_no_config')) {
			$data['/(`?)(\#__hwdms)((?!_config)[^0-9a-z_]*)/u'] 	= '$1' . str_replace('`.`', '$1.$1', $this->_createTableReplacements($site, 'hwdms', 	$content->get('hwdms_no_config'), '')) . '$4';
		}
		if ($content->get('admintools')) {
			$data['/`?#__admintools_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'admintools_', 	$content->get('admintools'), '') 			. '$1`';
		}
		if ($content->get('ak')) {
			$data['/`?#__ak_([0-9a-z_]*)`?/u'] 				= '`' . $this->_createTableReplacements($site, 'ak_', 			$content->get('ak'), '') 					. '$1`';
		}
		if ($content->get('akeebasubs')) {
			$data['/`?#__akeebasubs_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'akeebasubs_', 	$content->get('akeebasubs'), '') 			. '$1`';
		}
		if ($content->get('feedgator')) {
			$data['/`?#__feedgator([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'feedgator', 	$content->get('feedgator'), '') 				. '$1`';
		}
		if ($content->get('jxtc_ezimg')) {
			$tables['jxtc_ezimg_images'] = $content->get('jxtc_ezimg');
		}
		if ($content->get('invitex')) {
			$tables['techjoomlaAPI_users'] = $content->get('invitex');
			$data['/`?#__invitex_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'invitex_', 				$content->get('invitex'), '') 			. '$1`';
		}
		if ($content->get('jbolo')) {
			$data['/`?#__jbolo([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'jbolo', 				$content->get('jbolo'), '') 				. '$1`';
		}
		if ($content->get('jxtc_appbook')) {
			$data['/`?#__jxtc_appbook_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'jxtc_appbook_', 		$content->get('jxtc_appbook'), '') 		. '$1`';
		}
		if ($content->get('jxtc_albumplayer')) {
			$data['/`?#__jxtc_albumplayer([0-9a-z_]*)`?/u'] = '`' . $this->_createTableReplacements($site, 'jxtc_albumplayer', 		$content->get('jxtc_albumplayer'), '') 	. '$1`';
		}
		if ($content->get('geo')) {
			$data['/`?#__geo_([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'geo_', 					$content->get('geo'), '') 				. '$1`';
		}
		if ($content->get('jxtc_powertabs')) {
			$data['/`?#__jxtc_powertabs([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'jxtc_powertabs', 		$content->get('jxtc_powertabs'), '') 	. '$1`';
		}
		if ($content->get('payplans')) {
			$data['/`?#__payplans_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'payplans_', 			$content->get('payplans'), '') 			. '$1`';
		}
		if ($content->get('jxtc_readinglist')) {
			$tables['jxtc_readinglist'] = $content->get('jxtc_readinglist');
		}
		if ($content->get('ad')) {
			$data['/`?#__ad_([0-9a-z_]*)`?/u'] 				= '`' . $this->_createTableReplacements($site, 'ad_', 					$content->get('ad'), '') 				. '$1`';
		}
		if ($content->get('locator')) {
			$data['/`?#__location_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'location_', 			$content->get('locator'), '') 			. '$1`';
			$tables['locations'] = $content->get('locator');
		}
		if ($content->get('pin')) {
			$data['/`?#__pin_([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'pin_', 					$content->get('pin'), '') 				. '$1`';
		}
		if ($content->get('hikashop')) {
			$data['/`?#__hikashop_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'hikashop_', 			$content->get('hikashop'), '') 			. '$1`';
		}
		if ($content->get('docman')) {
			$data['/`?#__docman([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'docman', 				$content->get('docman'), '') 			. '$1`';
		}
		if ($content->get('swmenu')) {
			$data['/`?#__swmenu_([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'swmenu_', 				$content->get('swmenu'), '') 			. '$1`';
		}
		if ($content->get('virtueuploads')) {
			$data['/`?#__virtueuploads([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'virtueuploads', 		$content->get('virtueuploads'), '') 		. '$1`';
		}
		if ($content->get('djcf')) {
			$data['/`?#__djcf_([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'djcf_', 				$content->get('djcf'), '') 				. '$1`';
		}
		if ($content->get('chronoforms')) {
			$tables['chronoforms'] 			= $content->get('chronoforms');
			$tables['chronoform_actions'] 	= $content->get('chronoforms');
		}
		if ($content->get('eventlist')) {
			$data['/`?#__eventlist_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'eventlist_', 			$content->get('eventlist'), '') 			. '$1`';
		}
		if ($content->get('fpss')) {
			$data['/`?#__fpss_([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'fpss_', 				$content->get('fpss'), '') 				. '$1`';
		}
		if ($content->get('imageshow')) {
			$data['/`?#__imageshow_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'imageshow_', 			$content->get('imageshow'), '') 			. '$1`';
			$tables['jsn_imageshow_messages'] 	= $content->get('imageshow');
			$tables['jsn_imageshow_config'] 	= $content->get('imageshow');
		}
		if ($content->get('uniform')) {
			$data['/`?#__jsn_uniform_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'jsn_uniform_', 			$content->get('uniform'), '') 			. '$1`';
		}
		if ($content->get('widgetkit')) {
			$tables['widgetkit_widget'] 		= $content->get('widgetkit');
		}
		if ($content->get('listbingo')) {	
			$data['/`?#__listbingo_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'listbingo_', 			$content->get('listbingo'), '') 			. '$1`';
		}
		if ($content->get('redshop')) {
			$data['/`?#__redshop_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'redshop_', 				$content->get('redshop'), '') 			. '$1`';
		}
		if ($content->get('jvle')) {
			$data['/`?#__jvle_([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'jvle_', 				$content->get('listbingo'), '') 			. '$1`';
		}
		if ($content->get('bfstop')) {
			$tables['bfstop_failedlogin'] 	= $content->get('bfstop');
			$tables['bfstop_bannedip'] 		= $content->get('bfstop');
		}
		if ($content->get('komento')) {
			$data['/`?#__komento_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'komento_', 				$content->get('komento'), '') 			. '$1`';
		}
		if ($content->get('komento_no_config')) {
			$data['/(`?)(\#__komento)((?!_configs)[^0-9a-z_]*)/u'] 	= '$1' . str_replace('`.`', '$1.$1', $this->_createTableReplacements($site, 'komento', 	$content->get('komento_no_config'), '')) . '$4';
		}
		if ($content->get('jsn_poweradmin')) {
			$tables['jsn_poweradmin_history'] 		= $content->get('jsn_poweradmin');
			$tables['jsn_poweradmin_config'] 		= $content->get('jsn_poweradmin');
			$tables['jsn_poweradmin_menu_assets'] 	= $content->get('jsn_poweradmin');
		}
		if ($content->get('securitycheck')) {
			$data['/`?#__securitycheck([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'securitycheck', 		$content->get('securitycheck'), '') 		. '$1`';
		}
		if ($content->get('expautos')) {
			$data['/`?#__expautos_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'expautos_', 			$content->get('expautos'), '') 			. '$1`';
		}
		if ($content->get('mijosearch')) {
			$data['/`?#__mijosearch_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'mijosearch_', 			$content->get('mijosearch'), '') 		. '$1`';
		}
		if ($content->get('advancedmodules')) {
			$tables['advancedmodules'] 		= $content->get('advancedmodules');
		}
		if ($content->get('jck')) {
			$tables['jckplugins'] 				= $content->get('jck');
			$tables['jcktoolbars'] 				= $content->get('jck');
			$tables['jcktoolbarplugins'] 		= $content->get('jck');
			$tables['update_jckplugins'] 		= $content->get('jck');
		}
		if ($content->get('rereplacer')) {
			$tables['rereplacer'] 				= $content->get('rereplacer');
		}
		if ($content->get('magebridge')) {
			$data['/`?#__magebridge_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'magebridge_', 		$content->get('magebridge'), '') 		. '$1`';
		}
		if ($content->get('preachit')) {
			$tables['pibooks'] 					= $content->get('preachit');
			$tables['picomments'] 				= $content->get('preachit');
			$tables['pifilepath'] 				= $content->get('preachit');
			$tables['pimime'] 					= $content->get('preachit');
			$tables['pipodcast'] 				= $content->get('preachit');
			$tables['piseries'] 				= $content->get('preachit');
			$tables['piministry'] 				= $content->get('preachit');
			$tables['piteachers'] 				= $content->get('preachit');
			$tables['pistudies'] 				= $content->get('preachit');
			$tables['pimediaplayers'] 			= $content->get('preachit');
			$tables['pibckadmin'] 				= $content->get('preachit');
			$tables['pitemplates'] 				= $content->get('preachit');
			$tables['pipodmes'] 				= $content->get('preachit');
			$tables['piadminpodmes'] 			= $content->get('preachit');
			$tables['pibiblevers'] 				= $content->get('preachit');
		}
		if ($content->get('osmembership')) {
			$data['/`?#__osmembership_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'osmembership_', $content->get('osmembership'), '') 	. '$1`';
		}
		if ($content->get('mijosef')) {
			$data['/`?#__mijosef_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'mijosef_', 		$content->get('mijosef'), '') 		. '$1`';
		}
		if ($content->get('osemsc')) {
			$data['/`?#__osemsc_([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'osemsc_', 		$content->get('osemsc'), '') 		. '$1`';
			$tables['ose_activation'] 			= $content->get('osemsc');
		}
		if ($content->get('easydiscuss')) {
			$data['/`?#__discuss_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'discuss_', 		$content->get('easydiscuss'), '') 	. '$1`';
		}
		if ($content->get('hsusers')) {
			$tables['users_authentications'] 	= $content->get('hsusers');
			$tables['users_extended'] 			= $content->get('hsusers');
		}
		if ($content->get('quiz')) {
			$data['/`?#__quiz_([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'quiz_', 		$content->get('quiz'), '') 			. '$1`';
		}
		if ($content->get('muscol')) {
			$data['/`?#__muscol_([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'muscol_', 		$content->get('muscol'), '') 		. '$1`';
		}
		if ($content->get('allvideoshare')) {
			$data['/`?#__allvideoshare_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'allvideoshare_',$content->get('allvideoshare'), '') 	. '$1`';
		}
		if ($content->get('fua')) {
			$data['/`?#__fua_([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'fua_', 			$content->get('fua'), '') 			. '$1`';
		}
		if ($content->get('joaktree')) {
			$data['/`?#__joaktree_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'joaktree_', 	$content->get('joaktree'), '') 		. '$1`';
		}
		if ($content->get('rsticketspro')) {
			$data['/`?#__rsticketspro_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'rsticketspro_', $content->get('rsticketspro'), '') 	. '$1`';
		}
		if ($content->get('acesef')) {
			$data['/`?#__acesef_([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'acesef_', 		$content->get('acesef'), '') 		. '$1`';
		}
		if ($content->get('aup')) {
			$data['/`?#__alpha_userpoints([0-9a-z_]*)`?/u'] = '`' . $this->_createTableReplacements($site, 'alpha_userpoints', 	$content->get('aup'), '') 	. '$1`';
		}
		if ($content->get('vq')) {
			$data['/`?#__vq_([0-9a-z_]*)`?/u'] 				= '`' . $this->_createTableReplacements($site, 'vq_', 			$content->get('vq'), '') 			. '$1`';
		}
		if ($content->get('spidercalendar')) {
			$data['/`?#__spidercalendar_([0-9a-z_]*)`?/u'] = '`' . $this->_createTableReplacements($site, 'spidercalendar_', $content->get('spidercalendar'), '') . '$1`';
		}
		if ($content->get('jcomments')) {
			$data['/`?#__jcomments([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'jcomments', 	$content->get('jcomments'), '') 		. '$1`';
		}
		if ($content->get('mem')) {
			$data['/`?#__mem_([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'mem_', 			$content->get('mem'), '') 			. '$1`';
		}
		if ($content->get('paidsystem')) {
			$data['/`?#__paidsystem_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'paidsystem_', 	$content->get('paidsystem'), '') 	. '$1`';
		}
		if ($content->get('hikamarket')) {
			$data['/`?#__hikamarket_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'hikamarket_', 	$content->get('hikamarket'), '') 	. '$1`';
		}
		if ($content->get('rsmembership')) {
			$data['/`?#__rsmembership_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'rsmembership_', $content->get('rsmembership'), '') 	. '$1`';
		}
		if ($content->get('jfbconnect')) {
			$data['/`?#__jfbconnect_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'jfbconnect_',	$content->get('jfbconnect'), '') 	. '$1`';
			$data['/`?#__opengraph_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'opengraph_', 	$content->get('jfbconnect'), '') 	. '$1`';
		}
		if ($content->get('adagency')) {
			$data['/`?#__ad_agency_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'ad_agency_', 	$content->get('adagency'), '') 		. '$1`';
		}
		if ($content->get('fw_realestate')) {
			$data['/`?#__fw_realestate_([0-9a-z_]*)`?/u'] = '`' . $this->_createTableReplacements($site, 'fw_realestate_',$content->get('fw_realestate'), '') 	. '$1`';
		}
		if ($content->get('iproperty')) {
			$data['/`?#__iproperty([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'iproperty', 	$content->get('iproperty'), '') 		. '$1`';
		}
		if ($content->get('mijoshop')) {
			$data['/`?#__mijoshop_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'mijoshop_', 	$content->get('mijoshop'), '') 		. '$1`';
		}
		if ($content->get('snippets')) {
			$tables['snippets'] 		= $content->get('snippets');
		}
		if ($content->get('zhgooglemap')) {
			$data['/`?#__zhgooglemap_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'zhgooglemap_', 	$content->get('zhgooglemap'), '') 	. '$1`';
		}
		if ($content->get('cddir')) {
			$data['/`?#__cddir_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'cddir_', 		$content->get('cddir'), '') 			. '$1`';
		}
		if ($content->get('ajaxregister')) {
			$data['/`?#__ajaxregister_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'ajaxregister_', $content->get('ajaxregister'), '') 	. '$1`';
		}
		if ($content->get('jmap')) {
			$data['/`?#__jmap([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'jmap', 			$content->get('jmap'), '') 			. '$1`';
		}
		if ($content->get('emerald')) {
			$data['/`?#__emerald_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'emerald_', 		$content->get('emerald'), '') 		. '$1`';
		}
		if ($content->get('rseventspro')) {
			$data['/`?#__rseventspro_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'rseventspro_', 	$content->get('rseventspro'), '') 	. '$1`';
		}
		if ($content->get('jblance')) {
			$data['/`?#__jblance_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'jblance_', 		$content->get('jblance'), '') 		. '$1`';
		}
		if ($content->get('xmap')) {
			$tables['xmap_items'] 		= $content->get('xmap');
			$tables['xmap_sitemap'] 	= $content->get('xmap');
		}
		if ($content->get('j2store')) {
			$data['/`?#__j2store_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'j2store_', 		$content->get('j2store'), '') 		. '$1`';
		}
		if ($content->get('lovefactory')) {
			$data['/`?#__lovefactory_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'lovefactory_', 	$content->get('lovefactory'), '') 	. '$1`';
		}
		if ($content->get('social')) {
			$data['/`?#__social_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'social_', 		$content->get('social'), '') 		. '$1`';
		}
		if ($content->get('social_no_config')) {
			$data['/(`?)(\#__social)((?!_config)[^0-9a-z_]*)/u'] 	= '$1' . str_replace('`.`', '$1.$1', $this->_createTableReplacements($site, 'social', 	$content->get('social_no_config'), '')) . '$4';
		}
		if ($content->get('auctionfactory')) {
			$data['/`?#__bid_([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'bid_', 			$content->get('auctionfactory'), '') . '$1`';
			$tables['bids'] = $content->get('auctionfactory');
		}
		if ($content->get('hdwplayer')) {
			$data['/`?#__hdwplayer_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'hdwplayer_', 	$content->get('hdwplayer'), '') 		. '$1`';
		}
		if ($content->get('jomcl')) {
			$data['/`?#__jomcl_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'jomcl_', 		$content->get('jomcl'), '') 			. '$1`';
		}
		if ($content->get('regreminder')) {
			$data['/`?#__regreminder([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'regreminder', 	$content->get('regreminder'), '') 	. '$1`';
		}
		if ($content->get('flexicontact')) {
			$tables['flexicontact_log'] 		= $content->get('flexicontact');
		}
		// CComment uses very special SQL and can replaces prefixes directly and next escape them, so we should not use `
        if ($content->get('comment')) {
            $data['/`?#__comment([0-9a-z_]*)`?/u']         = str_replace('`', '', '`' . $this->_createTableReplacements($site, 'comment',         $content->get('comment'), '')         . '$1`');
        }
		if ($content->get('xtdir')) {
			$data['/`?#__xtdir_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'xtdir_', 		$content->get('xtdir'), '') 			. '$1`';
		}
		if ($content->get('offlajn')) {
			$data['/`?#__offlajn_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'offlajn_', 		$content->get('offlajn'), '') 		. '$1`';
		}
		if ($content->get('jobsfactory')) {
			$data['/`?#__jobsfactory_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'jobsfactory_', 	$content->get('jobsfactory'), '') 	. '$1`';
		}
		if ($content->get('vmvendor')) {
			$tables['vmvendor_vendorratings'] 		= $content->get('vmvendor');
		}
		if ($content->get('eshop')) {
			$data['/`?#__eshop_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'eshop_', 		$content->get('eshop'), '') 			. '$1`';
		}
		if ($content->get('flexicontent')) {
			$data['/`?#__flexicontent_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'flexicontent_', $content->get('flexicontent'), '') 	. '$1`';
		}
		if ($content->get('cmgroupbuying')) {
			$data['/`?#__cmgroupbuying_([0-9a-z_]*)`?/u'] = '`' . $this->_createTableReplacements($site, 'cmgroupbuying_', $content->get('cmgroupbuying'), '') . '$1`';
		}
		if ($content->get('joomgallery')) {
			$data['/`?#__joomgallery([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'joomgallery', 	$content->get('joomgallery'), '') . '$1`';
		}
		if ($content->get('joomgalaxy')) {
			$data['/`?#__joomgalaxy_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'joomgalaxy_', 	$content->get('joomgalaxy'), '') . '$1`';
		}
		if ($content->get('jdownloads')) {
			$data['/`?#__jdownloads_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'jdownloads_', 	$content->get('jdownloads'), '') . '$1`';
		}
		if ($content->get('jsn')) {
			$tables['jsn_fields'] 		= $content->get('jsn');
			$tables['jsn_users'] 		= $content->get('jsn');
			$data['/`?#__jsnsocial_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'jsnsocial_', 	$content->get('jsnsocial'), '') . '$1`';
		}
		if ($content->get('icagenda')) {
			$data['/`?#__icagenda([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'icagenda', 		$content->get('icagenda'), '') 	. '$1`';
		}
		if ($content->get('booking')) {
			$data['/`?#__booking_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'booking_', 		$content->get('booking'), '') 	. '$1`';
		}
		if ($content->get('cobalt')) {
			$data['/`?#__js_res_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'js_res_', 			$content->get('cobalt'), '') 	. '$1`';
		}
		if ($content->get('vikappointments')) {
			$data['/`?#__vikappointments_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'vikappointments_', 		$content->get('vikappointments'), '') 	. '$1`';
		}
		if ($content->get('joomcareer')) {
			$data['/`?#__joomcareer([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'joomcareer', 	$content->get('joomcareer'), '') . '$1`';
		}
		if ($content->get('kart')) {
			$data['/`?#__kart_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'kart_', 		$content->get('kart'), '') 		. '$1`';
			$tables['order_item_fee'] 		= $content->get('kart');
			$tables['q2c_order_history'] 	= $content->get('kart');
		}
		if ($content->get('kart_no_config')) {
			$data['/(`?)(\#__kart)((?!_store)[^0-9a-z_]*)/u'] 	= '$1' . str_replace('`.`', '$1.$1', $this->_createTableReplacements($site, 'kart', 	$content->get('kart_no_config'), '')) . '$4';
			$tables['order_item_fee'] 		= $content->get('kart');
			$tables['q2c_order_history'] 	= $content->get('kart');
		}
		if ($content->get('tjfields')) {
			$data['/`?#__tjfields_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'tjfields_', 	$content->get('tjfields'), '') 	. '$1`';
			$tables['tj_city'] 		= $content->get('tjfields');
			$tables['tj_country'] 	= $content->get('tjfields');
			$tables['tj_region'] 	= $content->get('tjfields');
		}
		if ($content->get('guru')) {
			$data['/`?#__guru_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'guru_', 		$content->get('guru'), '') 		. '$1`';
		}
		if ($content->get('vminvoice')) {
			$data['/`?#__vminvoice_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'vminvoice_', 	$content->get('vminvoice'), '') 	. '$1`';
		}
		if ($content->get('onepage')) {
			$data['/`?#__onepage_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'onepage_', 		$content->get('onepage'), '') 	. '$1`';
		}
		if ($content->get('breezingforms')) {
			$data['/`?#__facileforms_([0-9a-z_]*)`?/u'] = '`' . $this->_createTableReplacements($site, 'facileforms_', 	$content->get('breezingforms'), '') 	. '$1`';
			$tables['breezingforms'] 	= $content->get('breezingforms');
		}
		if ($content->get('vikbooking')) {
			$data['/`?#__vikbooking_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'vikbooking_', 	$content->get('vikbooking'), '') 	. '$1`';
		}
		if ($content->get('kandanda')) {
			$data['/`?#__kandanda_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'kandanda_', 	$content->get('kandanda'), '') 		. '$1`';
		}
		if ($content->get('upgflickrsuite')) {
			$data['/`?#__upgflickrsuite([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'upgflickrsuite', 	$content->get('upgflickrsuite'), '') . '$1`';
		}
		if ($content->get('fieldsattach')) {
			$data['/`?#__fieldsattach([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'fieldsattach', 	$content->get('fieldsattach'), '') . '$1`';
		}
		if ($content->get('jmapmyldap')) {
			$tables['sh_ldap_config'] 	= $content->get('jmapmyldap');
			$tables['sh_config'] 		= $content->get('jmapmyldap');
		}
		if ($content->get('sh404sef')) {
			$data['/`?#__sh404sef_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'sh404sef_', 	$content->get('sh404sef'), '') 		. '$1`';
		}
		if ($content->get('matukio')) {
			$data['/`?#__matukio([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'matukio', 		$content->get('matukio'), '') 		. '$1`';
		}
		if ($content->get('lmsking')) {
			$data['/`?#__lk_([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'lk_', 			$content->get('lmsking'), '') 		. '$1`';
		}
		if ($content->get('judownload')) {
			$data['/`?#__judownload_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'judownload_', 	$content->get('judownload'), '') 	. '$1`';
		}
		if ($content->get('jgive')) {
			$data['/`?#__jg_([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'jg_', 			$content->get('jgive'), '') 			. '$1`';
		}
		if ($content->get('form2content')) {
			$data['/`?#__f2c_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'f2c_', 			$content->get('form2content'), '') 	. '$1`';
		}
		if ($content->get('jticketing')) {
			$data['/`?#__jticketing_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'jticketing_', 	$content->get('jticketing'), '') 	. '$1`';
			$tables['Stripe_xref'] 		= $content->get('jticketing');
			$tables['tjlms_user_xref'] 	= $content->get('jticketing');
		}
		if ($content->get('falang')) {
			$data['/`?#__falang_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'falang_', 		$content->get('falang'), '') 		. '$1`';
		}
		if ($content->get('asurveys')) {
			$data['/`?#__asurveys_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'asurveys_', 	$content->get('asurveys'), '') 		. '$1`';
		}
		if ($content->get('youtubegallery')) {
			$data['/`?#__youtubegallery_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'youtubegallery_', 	$content->get('youtubegallery'), '') 		. '$1`';
		}
		if ($content->get('dpcalendar')) {
			$data['/`?#__dpcalendar_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'dpcalendar_', 	$content->get('dpcalendar'), '') 		. '$1`';
		}
		if ($content->get('rstbox')) {
			$tables['rstbox']		= $content->get('rstbox');
			$tables['rstbox_logs'] 	= $content->get('rstbox_logs');
		}
		if ($content->get('contentbuilder')) {
			$data['/`?#__contentbuilder_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'contentbuilder_', 	$content->get('contentbuilder'), '') 		. '$1`';
		}
		if ($content->get('communityanswers')) {
			$data['/`?#__answers_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'answers_', 	$content->get('communityanswers'), '') 		. '$1`';
		}
		if ($content->get('cmlivedeal')) {
			$data['/`?#__cmlivedeal_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'cmlivedeal_', 	$content->get('cmlivedeal'), '') 		. '$1`';
		}
		if ($content->get('creativecontactform')) {
			$tables['creative_forms']        = $content->get('creativecontactform');
			$tables['creative_fields']       = $content->get('creativecontactform');
			$tables['creative_field_types']  = $content->get('creativecontactform');
			$tables['creative_form_options'] = $content->get('creativecontactform');
			$tables['contact_templates']     = $content->get('creativecontactform');
			$tables['creative_field_types']  = $content->get('creativecontactform');
			$tables['creative_submissions']  = $content->get('creativecontactform');
		}
		if ($content->get('fst')) {
			$data['/`?#__fst_([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'fst_', 	$content->get('fst'), '') 		. '$1`';
		}
		if ($content->get('fsj_faqs')) {
			$data['/`?#__fsj_faqs_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'fsj_faqs_', 	$content->get('fsj_faqs'), '') 		. '$1`';
		}
		if ($content->get('fsf_faq')) {
			$data['/`?#__fsf_faq_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'fsf_faq_', 		$content->get('fsf_faq'), '') 		. '$1`';
		}
		if ($content->get('jcalpro')) {
			$data['/`?#__jcalpro_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'jcalpro_', 	$content->get('jcalpro'), '') 		. '$1`';
		}
		if ($content->get('lifestream')) {
			$data['/`?#__lifestream_([0-9a-z_]*)`?/u'] 	= '`' . $this->_createTableReplacements($site, 'lifestream_', 	$content->get('lifestream'), '') 		. '$1`';
		}
		if ($content->get('eb')) {
			$data['/`?#__eb_([0-9a-z_]*)`?/u'] 			= '`' . $this->_createTableReplacements($site, 'eb_', 	$content->get('eb'), '') 		. '$1`';
		}
		if ($content->get('convertforms')) {
			$data['/`?#__convertforms([0-9a-z_]*)`?/u'] 		= '`' . $this->_createTableReplacements($site, 'convertforms', 		$content->get('convertforms'), '') 		. '$1`';
		}
		if ($content->get('mytestimonials')) {
			$tables['mytestimonials_testimonial']  = $content->get('mytestimonials');
		}
		if ($content->get('mymaplocations')) {
			$tables['mymaplocations_location']  = $content->get('mymaplocations');
		}
		
		
		// to be continued...

		// Apply plugins
		JFactory::getApplication()->triggerEvent('onMightysitesCreateReplacements', array($params, $content, $site, &$data, &$tables, $this));
		
		// Custom tables
		foreach ($params->toArray() as $table => $from)
		{
			if (substr($table, 0, 6) == 'table_' && $from)
			{
				$tables[substr($table, 6)] = $from;
			}
		}
				
		// Tables
		if (count($tables))
		{
			foreach ($tables as $table => $from)
			{
				if ($from)
				{
					$data['/`?#__'.$table.'`?([\., \n\)]+)/u'] = '`' . $this->_createTableReplacements($site, $table, $from, '') . '`$1';
				}
			}
		}
		
		// Always load mighty from root
		$data['/`?#__mightysites[` \n]+/u'] = $this->_createTableReplacements($site, 'mightysites', 1);
		
		// remove empty data
		if (count($data))
		{
			foreach ($data as $key => $value)
			{
				if (empty($value))
				{
					unset($data[$key]);
				}
			}
		}
		
		return count($data) ? array(array_keys($data), array_values($data)) : array();
	}

	public function _createTableReplacements($site, $table, $from, $enclosure = '`')
	{
		$from = MightysitesHelper::getSite($from, true);
		
		if ($from)
		{
			$ending = $enclosure ? ' ' : '';
			
			if ($site->db == $from->db)
			{
				return $enclosure . $from->dbprefix . $table . $enclosure . $ending;
			}
			else
			{
				return $enclosure . $from->db . '`.`' . $from->dbprefix . $table . $enclosure . $ending;
			}
		}
	}
}
