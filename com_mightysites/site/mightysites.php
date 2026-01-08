<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2014 AlterBrains.com. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
*/

/*
defined('JPATH_BASE') or die;
*/

// Setup base.
define('MIGHTY_BASE', substr(__DIR__, 0, -27));

// Setup domain. It can be already defined in alias config.
$domain = 'default';

if (!empty($_SERVER['HTTP_HOST']))
{
	$domain = $_SERVER['HTTP_HOST'];
}
elseif (!empty($_SERVER['SERVER_NAME']))
{
	$domain = $_SERVER['SERVER_NAME'];
}
// CLI mode - allow '--domain' parameter
elseif (defined('STDOUT') && defined('STDIN') && isset($_SERVER['argv']))
{
	foreach ($_SERVER['argv'] as $argv)
	{
		if (substr($argv, 0, 9) == '--domain=')
		{
			list(, $domain) = explode('=', $argv);
			break;
		}
	}
}

// Remove port
if (strpos($domain, ':') !== false)
{
	list($domain, $tmp) = explode(':', $domain);
}

$domain = (substr($domain, 0, 4) == 'www.') ? substr($domain, 4) : $domain;
$domain = preg_replace('#([^A-Z0-9])#i', '_', $domain);

$mighty_domain = $domain;

// Setup config.
if (file_exists(MIGHTY_BASE.'/configuration_'.$mighty_domain.'.php'))
{
	$config_file = MIGHTY_BASE.'/configuration_'.$mighty_domain.'.php';
}
elseif (file_exists(MIGHTY_BASE.'/components/com_mightysites/configuration/configuration_'.$mighty_domain.'.php'))
{
	$config_file = MIGHTY_BASE.'/components/com_mightysites/configuration/configuration_'.$mighty_domain.'.php';
}
elseif (file_exists(MIGHTY_BASE.'/components/com_mightysites/configuration/default.php'))
{
	$config_file = MIGHTY_BASE.'/components/com_mightysites/configuration/default.php';
}

// Check for virtual folder if we have request URI.
if (!empty($_SERVER['REQUEST_URI']))
{
	if ($folders = array_filter(explode('/', trim($_SERVER['REQUEST_URI'], '/'))))
	{
		if (strpos(end($folders), '.') !== false)
		{
			array_pop($folders);
		}
		if ($folders && end($folders) == 'administrator')
		{
			array_pop($folders);
		}
		
		while ($folders)
		{
			$segment = preg_replace('#([^A-Z0-9])#i', '_', implode('.', $folders));
			
			if (file_exists(MIGHTY_BASE . '/configuration_'.$mighty_domain.'_'.$segment.'.php'))
			{
				$config_file = MIGHTY_BASE . '/configuration_'.$mighty_domain.'_'.$segment.'.php';
				$mighty_domain .= '_'.$segment;
				$subfolder = true;
				break;
			}
			elseif (file_exists(MIGHTY_BASE . '/components/com_mightysites/configuration/configuration_'.$mighty_domain.'_'.$segment.'.php'))
			{
				$config_file = MIGHTY_BASE . '/components/com_mightysites/configuration/configuration_'.$mighty_domain.'_'.$segment.'.php';
				$mighty_domain .= '_'.$segment;
				$subfolder = true;
				break;
			}
	
			array_pop($folders);
		}
	}
	
	// Correct possible issues with subfolder site and RewriteBase
	if (!empty($subfolder))
	{
		if (isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME'] == '/index.php')
		{
			$_SERVER['SCRIPT_NAME'] = '/'.$segment.'/index.php';
		}
		if (isset($_SERVER['PHP_SELF']) && $_SERVER['PHP_SELF'] == '/index.php')
		{
			$_SERVER['PHP_SELF'] = '/'.$segment.'/index.php';
		}
	}
}

// Load personal config file
if (isset($config_file))
{
	require_once $config_file;
} 
// not for Akeeba kickstart restore
elseif (!defined('KICKSTART') && !defined('_AKEEBA'))
{
	die('Invalid domain, please ensure that MightySites is configured properly.');
}

// Setup domain. It can be already defined in alias config.
if (!defined('MIGHTY_DOMAIN'))
{
	define('MIGHTY_DOMAIN', $mighty_domain);
}

// Setup config. It can be already defined in alias config.
if (!defined('MIGHTY_CONFIG'))
{
	define('MIGHTY_CONFIG', $config_file);
}

//error_reporting(E_ALL);

$config = new JConfig;

// Load our driver.

// sometimes configuration can be loaded by 3rd-party extensions directly, no constant or class in this case
if (defined('JPATH_SITE') && class_exists('JVersion') && isset($config->mighty_enable) && $config->mighty_enable && isset($config->mighty[0][0]))
{
	// Remove legacy
	if ($config->dbtype == 'mightysites')
	{
		$config->dbtype = 'mysql';
	}
	
	// Falang compatibility is first, only for frontend.
	if (!empty($config->mighty_falang) && JPATH_SITE == JPATH_BASE && file_exists(JPATH_SITE.'/plugins/system/falangdriver/falang_database.php'))
	{
		defined('DS') ? null : define('DS', DIRECTORY_SEPARATOR);
		require_once JPATH_SITE.'/plugins/system/falangdriver/falang_database.php';
		class_alias('JFalangDatabase', 'JDatabaseMightysitesBase');
	}
	// Non-Falang environment.
	else
	{
		if (version_compare(JVERSION, '3.0', 'ge'))
		{
			require_once JPATH_SITE.'/libraries/joomla/database/database.php';
			require_once JPATH_SITE.'/libraries/joomla/database/driver.php';
			require_once JPATH_SITE.'/libraries/joomla/database/driver/'.$config->dbtype.'.php';
			class_alias('JDatabaseDriver'.ucfirst($config->dbtype), 'JDatabaseMightysitesBase');
		}
		else
		{
			require_once JPATH_SITE.'/libraries/joomla/database/database.php';
			require_once JPATH_SITE.'/libraries/joomla/database/database/'.$config->dbtype.'.php';
			class_alias('JDatabase'.$config->dbtype, 'JDatabaseMightysitesBase');
		}
	}
	
	class JDatabaseMightysites extends JDatabaseMightysitesBase
	{
		public $count 	= null;
		public $log 	= null;
		public $mighty 	= null;
		
		public function __construct($options)
		{
			parent::__construct($options);
			
			$this->mighty = JFactory::getConfig()->get('mighty');
			
			// stupid IIS can use \r\n
			if (PHP_EOL === "\r\n" && isset($this->mighty[0][0]))
			{
				foreach($this->mighty[0] as &$tmp) {
					$tmp = str_replace('\n', '\r\n', $tmp);
				}
			}
		}

		// Not here!
		public function __destruct()
		{
		}
				
		public function replacePrefix($sql, $prefix = '#__')
		{
			// We need to allow parent usual method
			if (isset($GLOBALS['no_mightysharing']) || empty($this->mighty))
			{
				return parent::replacePrefix($sql, $prefix);
			}
			
			// Initialize variables.
			$escaped = false;
			$startPos = 0;
			$quoteChar = '';
			$literal = '';
	
			$sql = trim($sql);
			$n = strlen($sql);
	
			while ($startPos < $n)
			{
				$ip = strpos($sql, $prefix, $startPos);
				if ($ip === false)
				{
					break;
				}
	
				$j = strpos($sql, "'", $startPos);
				$k = strpos($sql, '"', $startPos);
				if (($k !== false) && (($k < $j) || ($j === false)))
				{
					$quoteChar	= '"';
					$j			= $k;
				}
				else
				{
					$quoteChar	= "'";
				}
	
				if ($j === false)
				{
					$j = $n;
				}
				
				 // We need to add extra space in order to match our patterns and next trim it.
                $literal .= str_replace($prefix, $this->tablePrefix, trim(preg_replace($this->mighty[0], $this->mighty[1], substr($sql, $startPos, $j - $startPos) . ' ')));
				
				$startPos = $j;
				
				$j = $startPos + 1;
	
				if ($j >= $n)
				{
					break;
				}
	
				// quote comes first, find end of quote
				while (true)
				{
					$k = strpos($sql, $quoteChar, $j);
					$escaped = false;
					if ($k === false)
					{
						break;
					}
					$l = $k - 1;
					while ($l >= 0 && $sql{$l} == '\\')
					{
						$l--;
						$escaped = !$escaped;
					}
					if ($escaped)
					{
						$j = $k + 1;
						continue;
					}
					break;
				}
				if ($k === false)
				{
					// error in the query - no end quote; ignore it
					break;
				}
				$literal .= substr($sql, $startPos, $k - $startPos + 1);
				$startPos = $k + 1;
			}
			if ($startPos < $n)
			{
				$literal .= substr($sql, $startPos, $n - $startPos);
			}
	
			return $literal;
		}
		
		public static function changeHandler()
		{
			$db 	= JFactory::getDBO();
			$config = JFactory::getConfig();
			$debug 	= $config->get('debug');
			$debug 	? $log = $db->getLog() : null;
			
			JFactory::$database = new JDatabaseMightysites(array(
				'driver' 	=> 'mightysites', 
				'host' 		=> $config->get('host'), 
				'user' 		=> $config->get('user'), 
				'password' 	=> $config->get('password'), 
				'database' 	=> $config->get('db'), 
				'prefix' 	=> $config->get('dbprefix')
			));
			
			if ($debug)
			{
				$db = JFactory::getDBO();
				$db->setDebug(($debug == 0) ? false : true);
				$db->count = count($log);
				$db->log = $log;
			}
		}
	}

	JDatabaseMightysites::changeHandler();
}

// Overload format
if (defined('JVERSION'))
{
	if (version_compare(JVERSION, '3.3', 'ge'))
	{
		require_once __DIR__.'/format_33.php';
	}
	elseif (version_compare(JVERSION, '3.0', 'ge'))
	{
		require_once __DIR__.'/format_30.php';
	}
	else
	{
		require_once __DIR__.'/format_25.php';
	}
}
