<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.file');

abstract class MightysitesHelper
{
	private static $dbo;
	
	public static function getHost()
	{
		static $domain;
		
		if (!$domain) {
			$domain = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
			$domain = (substr($domain, 0, 4) == 'www.') ? substr($domain, 4) : $domain;
			
			if (strpos($domain, ':') !== false) {
				list($domain, $tmp) = explode(':', $domain);
			}
		}
		return $domain;
	}

	public static function getSynchs($type = null)
	{
		$custom = array(
			'js',
			'cb',
			'mtree',
			'sobi2',
			'sobipro',
			'gridiron',
			'uddeim',
			'zoo',
			'dtregister',
			'easyblog',
			'easyblog_no_config',
			'joomsport',
			'rsform',
			'rsfiles',
			'acymailing',
			'jce',
			'adsmanager',
			'joomshopping',
			'kunena',
			'k2',
			'pf',
			'jcp',
			'enmasse',
			'phocadownload',
			'phocagallery',
			'jreviews',
			'virtuemart',
			'virtuemart_no_config',
			'jevents',
			'hwdms',
			'hwdms_no_config',
			
			'admintools',
			'ak',
			'akeebasubs',
			'feedgator',
			'jxtc_ezimg',
			'invitex',
			'jbolo',
			'jxtc_appbook',
			'jxtc_albumplayer',
			'geo',
			'jxtc_powertabs',
			'payplans',
			'jxtc_readinglist',
			'ad',
			'locator',
			'pin',
			'hikashop',
			'docman',
			'swmenu',
			'virtueuploads',
			'djcf',
			'chronoforms',
			'eventlist',
			'fpss',
			'imageshow',
			'uniform',
			'widgetkit',
			'listbingo',
			'redshop',
			'jvle',
			'bfstop',
			'komento',
			'komento_no_config',
			'jsn_poweradmin',
			'securitycheck',
			'expautos',
			'mijosearch',
			'advancedmodules',
			'jck',
			'rereplacer',
			'magebridge',
			'preachit',
			'osmembership',
			'mijosef',
			'osemsc',
			'easydiscuss',
			'hsusers',
			'quiz',
			'muscol',
			'allvideoshare',
			'fua',
			'joaktree',
			'rsticketspro',
			'acesef',
			'aup',
			'vq',
			'spidercalendar',
			'jcomments',
			'mem',
			'paidsystem',
			'hikamarket',
			'rsmembership',
			'jfbconnect',
			'adagency',
			'fw_realestate',
			'iproperty',
			'mijoshop',
			'snippets',
			'zhgooglemap',
			'cddir',
			'ajaxregister',
			'jmap',
			'emerald',
			'rseventspro',
			'jblance',
			'xmap',
			'j2store',
			'js_no_config',
			'lovefactory',
			'social',
			'social_no_config',
			'auctionfactory',
			'hdwplayer',
			'jomcl',
			'regreminder',
			'flexicontact',
			'comment',
			'xtdir',
			'offlajn',
			'jobsfactory',
			'vmvendor',
			'eshop',
			'flexicontent',
			'cmgroupbuying',
			'joomgallery',
			'joomgalaxy',
			'jdownloads',
			'jsn',
			'icagenda',
			'booking',
			'cobalt',
			'vikappointments',
			'joomcareer',
			'kart',
			'kart_no_config',
			'tjfields',
			'guru',
			'vminvoice',
			'onepage',
			'breezingforms',
			'vikbooking',
			'kandanda',
			'upgflickrsuite',
			'fieldsattach', // com_fieldsattach
			'jmapmyldap', // com_shldap
			'sh404sef', // com_sh404sef
			'matukio', // com_matukio
			'lmsking', // com_lmsking
			'judownload', // com_judownload
			'jgive', // com_jgive
			'form2content', // com_form2content
			'jticketing', // com_jticketing
			'falang', // com_falang
			'asurveys', // com_asurveys
			'youtubegallery', // com_youtubegallery
			'dpcalendar', // com_dpcalendar
			'rstbox', // com_rstbox
			'contentbuilder', // com_contentbuilder
			'communityanswers', // com_communityanswers
			'cmlivedeal', // com_cmlivedeal
			'creativecontactform', // com_creativecontactform
			'fst', // com_fst
			'fsj_faqs', // com_fsj_faqs
			'fsf_faq', // com_fsf
			'jcalpro', // com_jcalpro
			'lifestream', // com_lifestream
			'eb', // com_eventbooking
			'convertforms', // com_convertforms
			'mytestimonials', // com_mytestimonials
			'mymaplocations', // com_mymaplocations
			
			
			
			


			// to be continued...
		);
		
		// Apply plugins
		JFactory::getApplication()->triggerEvent('onMightysitesGetSynchs', array(&$custom));
		
		// Sort by name
		// @todo - add check for component folder presence
		$custom2 = array();
		foreach ($custom as $key) {
			$custom2[$key] = JText::_('COM_MIGHTYSITES_SYNCH_LABEL_'.$key);
		}
		natcasesort($custom2);
		$custom = array_keys($custom2);

		$core = array(
			'extensions',
			'permissions',
			'users',
			
			// Users don't understand how it works, better skip
			// 'sessions',
			
			'templates',
			'categories',
			'content',
			'languages',
			'menus',
			'modules',
			'newsfeeds',
			'weblinks',
			'banners',
			'contacts',
			'messages',
			'smartsearch',
			'tags',
			'tags_refs',
			'redirect',
		);
		asort($core);
		
		switch ($type)
		{
			case 'custom':
				return $custom;
				break;
				
			case 'core':
				return $core;
				break;
				
			default:
				return array_merge($core, $custom);
				break;
		}
	}
	
	// In fact returns all Sites & Databases
	public static function getSites($key = 'domain')
	{
		static $sites;
		
		if (!isset($sites[$key]))
		{
			$db = JFactory::getDBO();
			
			$db->setQuery('SELECT * FROM #__mightysites ORDER BY `domain`');
			
			$sites[$key] = $db->loadObjectList($key);
			
			foreach ($sites[$key] as &$site) {
				// Prepare domain cmd
				$site->domain_cmd = preg_replace('#([^A-Z0-9])#i', '_', $site->domain);
				// Params
				$site->params = new JRegistry($site->params);
			}
		}
		
		return $sites[$key];
	}
	
	// In fact returns a Site or Database
	public static function getSite($id, $attach_config = false)
	{
		$sites = self::getSites();
		
		// By domain
		if (isset($sites[$id])) {
			return $attach_config ? self::attachConfig($sites[$id]) : $sites[$id];
		}
		
		// By id or parsed domain
		foreach ($sites as $site) {
			if ($id == $site->id || $id == $site->domain_cmd) {
				return $attach_config ? self::attachConfig($site) : $site;
			}
		}
		
		return false;
	}
	
	public static function getCurrentSite($attach_config = false)
	{
		$site = self::getSite(defined('MIGHTY_DOMAIN') ? MIGHTY_DOMAIN : self::getHost(), $attach_config);
		
		return $site;
	}
	
	public static function sitesList($name, $value = null, $script = null, $skip = null, $first = null, $type = false, $first_value = '', $second = null, $second_value = null)
	{
		$options = array();
		
		if ($first) {
			$options[] = JHTML::_('select.option', $first_value, $first, 'value', 'text');
		}
		
		if ($second) {
			$options[] = JHTML::_('select.option', $second_value, $second, 'value', 'text');
		}

		$sites = self::getSites();
		
		foreach ($sites as $site)
		{
			if (empty($skip) || ($skip != $site->id))
			{
				if ($type && $site->type != $type) {}
				else {
					$options[]	= JHTML::_('select.option',  $site->id, $site->domain, 'value', 'text');
				}
			}
		}
		
		return JHTML::_('select.genericlist', $options, $name, 'class="inputbox" '.$script, 'value', 'text', $value);
	}
	
	public static function getDatabases($key = 'id')
	{
		static $databases;
		
		if (!isset($databases[$key])) {
			$db = JFactory::getDBO();
			$db->setQuery('SELECT * FROM #__mightysites ORDER BY `domain`');
			$databases[$key] = $db->loadObjectList($key);
		}
		
		return $databases[$key];
	}

	public static function databasesList($name, $value = null, $script = null, $skip = array('information_schema'))
	{
		$options = array();
		$options[] = JHTML::_('select.option', '', JText::_('COM_MIGHTYSITES_SELECT_DATABASE'), 'value', 'text');
		
		$db = JFactory::getDBO();
		$db->setQuery('SHOW DATABASES');
		$rows = $db->loadColumn();
		
		if (count($rows)) {
			foreach ($rows as $row) {
				if (!in_array($row, $skip)) {
					$options[]	= JHTML::_('select.option',  $row, $row, 'value', 'text');
				}
			}
		}
		return JHTML::_('select.genericlist', $options, $name, 'class="inputbox" '.$script, 'value', 'text', $value);
	}
	
	public static function menuitemsList($name, $value, $script = null)
	{
		$types = array('default' => array(JHTML::_('select.option', '', JText::_('COM_MIGHTYSITES_DEFAULT_MENUITEM'), 'value', 'text')));
		
		$db = JFactory::getDBO();
		$query = 'SELECT id, menutype, title, level FROM #__menu WHERE client_id=0 AND id > 1 AND published=1 ORDER BY lft';
		$db->setQuery($query);
		
		foreach ($db->loadObjectList() as $item) {
			if (!isset($types[$item->menutype])) {
				$types[$item->menutype] = array();
			}
			$types[$item->menutype][] = JHTML::_('select.option', $item->id, str_repeat(' - ', $item->level-1).$item->title, 'value', 'text');
		}

		return JHTML::_('select.groupedlist', $types, $name, array(
			'list.attr' 			=> 'class="inputbox" '.$script,
			'id' 					=> $name,
			'list.select' 			=> $value,
			'group.items' 			=> null,
			'option.key.toHtml' 	=> false,
			'option.text.toHtml' 	=> false
		));
	}

	public static function prepareDomain($domain = null)
	{
		if (!$domain) {
			$domain = self::getHost();
		}

		if (substr($domain, 0, 4) == 'www.') {
			$domain = substr($domain, 4);
		}
	
		// Use Punycode!
		
		$domain = JStringPunycode::toPunycode($domain);
		
		// Normalize filename.
		$domain = preg_replace('#([^A-Za-z0-9])#i', '_', $domain);
		
		return $domain;
	}
	
	public static function getConfigFilename($domain = null, $force_root = false)
	{
		// current config
		if (!$domain)
		{
			if (defined('MIGHTY_CONFIG')) {
				return MIGHTY_CONFIG;
			}
			
			$domain = self::getHost();
		}
		
		$fname = JPATH_SITE.'/configuration_'.self::prepareDomain($domain).'.php';
		
		if (!file_exists($fname) && !$force_root)
		{
			$fname = JPATH_SITE.'/components/com_mightysites/configuration/configuration_'.self::prepareDomain($domain).'.php';
		}
		
		return $fname;
	}
	
	public static function attachConfig(&$row, $config = null)
	{
		$app = JFactory::getApplication();
		
		if ($row->type == 1)
		{
			// Load as file into string.
			if (!$config)
			{
				$fname 	= self::getConfigFilename($row->domain);
				
				if (file_exists($fname)) {
					$config = JFile::read($fname);
				} else {
					$app->enqueueMessage(JText::sprintf('COM_MIGHTYSITES_NO_CONFIG', $fname, $row->domain), 'error');
					return $row;
				}
			}
			
			// Parse into object.
			if (is_string($config))
			{
				$classname = 'JConfig'.$row->id;
				
				if (!class_exists($classname))
				{
					$config = strtr($config, array(
						'<?php'."\n" 		=> "\n",
						'<?php'."\r\n" 		=> "\r\n",
						'<?php'."\r" 		=> "\r",
						'class JConfig' => 'class '.$classname,
					));
					eval($config);
				}
				
				if (class_exists($classname)) {
					$config = new $classname;
				}
			}
			
			// Parse object
			if (is_object($config))
			{
				foreach ((array)$config as $key => $value) {
					$row->$key = $value;
				}
				$row->published = !@$row->offline;
			}
		}
		
		return $row;
	}
	
	/*
	public static function getToken()
	{
		$params = JComponentHelper::getParams('com_mightysites');
		return $params->get('support_token');
	}
	*/
	
	public static function patchConfiguration()
	{
		// Update/create default config.
		$default_config_file = JPATH_SITE.'/components/com_mightysites/configuration/default.php';
		
		// Reload master domain from DB, it can be cached in our data.
		$db = JFactory::getDbo();
		
		$query = 'SELECT domain FROM `#__mightysites` WHERE id=1';
		$db->setQuery($query, 0, 1);
		$master_domain = $db->loadResult();
		
		if (!file_exists($default_config_file)
			||
			(strpos(file_get_contents($default_config_file), self::getConfigFilename($master_domain)) === false)
		)
		{
			
			$default_config = 
				'<?php' . "\n" .
				'define(\'MIGHTY_DOMAIN\', \''.self::prepareDomain($master_domain).'\');' . "\n" .
				'define(\'MIGHTY_CONFIG\', __DIR__ . \'/configuration_'.self::prepareDomain($master_domain).'.php\');' . "\n" .
				'require_once MIGHTY_CONFIG;';
			
			if (!JFile::write($default_config_file, $default_config))
			{
				$app->enqueueMessage(JText::sprintf('COM_MIGHTYSITES_CANT_WRITE_FILE', $default_config_file), 'error');
			}
		}
		
		// Get our params.
		$params = JComponentHelper::getParams('com_mightysites');

		// Update Joomla config.
		$file 	= JPATH_SITE.'/configuration.php';
		$str 	= '<?php require_once __DIR__.\'/components/com_mightysites/mightysites.php\';';

		$contents = file_get_contents($file);
		
		if (JString::strpos($contents, $str) !== 0 || ($params->get('varnish') && JString::strpos($contents, 'class JConfig') === false))
		{
			$app = JFactory::getApplication();
			
			// Add dummy code for Varnish cache.
			if ($params->get('varnish'))
			{
				$str .= "\n/*\n".file_get_contents(JPATH_SITE.'/components/com_mightysites/configuration/configuration_'.self::prepareDomain($master_domain).'.php')."\n*/";
			}
			
			// Load our language, it can be missed if we call method in com_config
			JFactory::getLanguage()->load('com_mightysites');
			
			// Get the new FTP credentials.
			$ftp = JClientHelper::getCredentials('ftp', true);
	
			// Attempt to make the file writeable if using FTP.
			if (!$ftp['enabled'] && JPath::isOwner($file) && !JPath::setPermissions($file, '0644')) {
				$app->enqueueMessage(JText::sprintf('COM_MIGHTYSITES_ERROR_CONFIG_PATCH_FAILED', $file), 'error');
				return false;
			}
		
			if (JFile::write($file, $str)) {
				// Attempt to make the file unwriteable if using FTP.
				if (JFactory::getConfig()->get('ftp_enable') == 0 && !$ftp['enabled'] && JPath::isOwner($file) && !JPath::setPermissions($file, '0644')) {
					$app->enqueueMessage(JText::sprintf('COM_MIGHTYSITES_ERROR_CONFIG_PATCH_FAILED', $file), 'error');
					return false;
				}
			}
			else {
				$app->enqueueMessage(JText::sprintf('COM_MIGHTYSITES_ERROR_CONFIG_PATCH_FAILED', $file), 'error');
				return false;
			}
		}
		
		return true;
	}
	
	public static function getDBO(&$site, $force_select = false)
	{
		if (!isset(self::$dbo[$site->id]))
		{
			$conf = JFactory::getConfig();
			
			$options = array(
				'driver' 	=> isset($site->dbtype) 	? $site->dbtype 	: $conf->get('dbtype'), 	// can be absent if Database
				'host' 		=> isset($site->host) 		? $site->host 		: $conf->get('host'), 		// can be absent if Database
				'user' 		=> isset($site->user) 		? $site->user 		: $conf->get('user'), 		// can be absent if Database
				'password' 	=> isset($site->password) 	? $site->password 	: $conf->get('password'), 	// can be absent if Database
				'database' 	=> $site->db, 
				'prefix' 	=> $site->dbprefix,
				'dummy'		=> md5(uniqid(mt_rand(), true))	// always get new instance, otherwise it can return db
			);
			
			if (self::$dbo[$site->id] = JDatabase::getInstance($options))
			{
				self::$dbo[$site->id]->connect();
			}
			
			if (!self::$dbo[$site->id] || !self::$dbo[$site->id]->connected())
			{
				JError::raiseError(500, JText::sprintf('COM_MIGHTYSITES_CANT_CONNECT_DB', $options['database'], $options['user'], @self::$dbo[$site->id]->getErrorMsg()));
			}
		}
		
		if ($force_select)
		{
			self::$dbo[$site->id]->select($site->db);
		}
		
		return self::$dbo[$site->id];
	}
	
	public static function getTables(&$db)
	{
		$db->setQuery('SHOW FULL TABLES WHERE Table_Type = "BASE TABLE"');
		return $db->loadColumn();
	}

	public static function getViews(&$db)
	{
		$db->setQuery('SHOW FULL TABLES WHERE Table_Type = "VIEW"');
		return $db->loadColumn();
	}
	
	public static function topMenu()
	{
		$app = JFactory::getApplication();
		
		$view = $app->input->get('view', 'sites');
		
		JHtmlSidebar::addEntry(JText::_('COM_MIGHTYSITES_SUBMENU_SITES'), 		'index.php?option=com_mightysites&view=sites', 		$view == 'sites');
		JHtmlSidebar::addEntry(JText::_('COM_MIGHTYSITES_SUBMENU_DATABASES'), 	'index.php?option=com_mightysites&view=databases', 	$view == 'databases');
		JHtmlSidebar::addEntry(JText::_('COM_MIGHTYSITES_SUBMENU_ABOUT'), 		'index.php?option=com_mightysites&view=about', 		$view == 'about');
		
		// check plugins
		$db = JFactory::getDBO();
		
		$query = 'SELECT * FROM #__extensions WHERE `element`="mightysites" AND type="plugin"';
		$db->setQuery($query, 0, 1);
		$plugin_mightysites = $db->loadObject();

		if (!$plugin_mightysites) {
			$app->enqueueMessage(JText::_('COM_MIGHTYSITES_PLUGIN_INSTALL_ERROR'), 'notice');
		}
		else if (!$plugin_mightysites->enabled) {
			$app->enqueueMessage(JText::sprintf('COM_MIGHTYSITES_PLUGIN_UNPUBLISHED', 'System - MightySites'), 'notice');
		} 
		
		// Auto correct ordering: move to first place
		if ($plugin_mightysites && $plugin_mightysites->ordering != -99980)
		{
			$query = 'UPDATE #__extensions SET ordering = -99980 WHERE `element`="mightysites" AND type="plugin"';
			$db->setQuery($query);
			$db->query();
			
			$cache = true;
		}

		$query = 'SELECT * FROM #__extensions WHERE `element`="languagefilter" AND type="plugin"';
		$db->setQuery($query, 0, 1);
		$plugin_languagefilter = $db->loadObject();
		
		// Also move "System - Language Filter" after our plugin - it's required for nice language override.
		if ($plugin_languagefilter && $plugin_languagefilter->ordering != -99990)
		{
			$query = 'UPDATE #__extensions SET ordering = -99990 WHERE `element`="languagefilter" AND type="plugin"';
			$db->setQuery($query);
			$db->query();

			$cache = true;
		}
			
		// Clear cache
		if (!empty($cache))
		{
			$conf = JFactory::getConfig();
			$cache = JCache::getInstance('', array(
				'defaultgroup'	=> '',
				'storage' 		=> $conf->get('cache_handler', ''),
				'caching'		=> true,
				'cachebase'		=> $conf->get('cache_path', JPATH_SITE . '/cache'),
			)); 
			$cache->clean('com_plugins'); 

			$cache = JCache::getInstance('', array(
				'defaultgroup'	=> '',
				'storage' 		=> $conf->get('cache_handler', ''),
				'caching'		=> true,
				'cachebase'		=> JPATH_SITE . '/administrator/cache',
			)); 
			$cache->clean('com_plugins'); 
		}
	}
}
