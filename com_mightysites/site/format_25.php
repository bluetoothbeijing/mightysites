<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2013 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('_JEXEC') or die('Restricted access');

class JRegistryFormatPHP extends JRegistryFormat
{
	public function objectToString($object, $params = array()) 
	{
		// Build the object variables string
		$vars = '';
		foreach (get_object_vars($object) as $k => $v)
		{
			if (is_scalar($v))
			{
				$vars .= "\tpublic $" . $k . " = '" . addcslashes($v, '\\\'') . "';\n";
			}
			elseif (is_array($v) || is_object($v))
			{
				$vars .= "\tpublic $" . $k . " = " . $this->getArrayString((array) $v) . ";\n";
			}
		}

		$str = "<?php\nclass ".$params['class']." {\n";
		$str .= $vars;
		$str .= "}";

		// Use the closing tag if it not set to false in parameters.
		if (!isset($params['closingtag']) || $params['closingtag'] !== false) {
			$str .= "\n?>";
		}
		
		// default behavior
		if ($params['class'] != 'JConfig') {
			return $str;
		}
		// our behavior
		else {
			$app = JFactory::getApplication();
			// get domain
			require_once(JPATH_ADMINISTRATOR.'/components/com_mightysites/helpers/helper.php');

			$mighty = $app->input->getString('mighty');
			$file 	= MightysitesHelper::getConfigFilename();
			
			// Get the new FTP credentials.
			jimport('joomla.client.helper');
			$ftp = JClientHelper::getCredentials('ftp', true);
			
			// Attempt to make the file writeable if using FTP.
			if (!$ftp['enabled'] && JPath::isOwner($file) && !JPath::setPermissions($file, '0644')) {
				$link = $mighty ? 'index.php?option=com_config&tmpl=component&mighty='.$mighty : 'index.php?option=com_config';
				$app->redirect($link, JText::_('COM_CONFIG_ERROR_CONFIGURATION_PHP_NOTWRITABLE'), 'error');
			}

			// Attempt to write the configuration file as a PHP class named JConfig.
			if (!JFile::write($file, $str)) {
				$link = $mighty ? 'index.php?option=com_config&tmpl=component&mighty='.$mighty : 'index.php?option=com_config';
				$app->redirect($link, JText::_('COM_CONFIG_ERROR_WRITE_FAILED'), 'error');
			}
			
			MightysitesHelper::patchConfiguration();

			// Get the new FTP credentials.
			$ftp = JClientHelper::getCredentials('ftp', true);

			// Attempt to make the file unwriteable if using FTP.
			if (JFactory::getConfig()->get('ftp_enable') == 0 && !$ftp['enabled'] && JPath::isOwner($file) && !JPath::setPermissions($file, '0444')) {
				JError::raiseNotice('SOME_ERROR_CODE', JText::_('COM_CONFIG_ERROR_CONFIGURATION_PHP_NOTUNWRITABLE'));
			}
	
			// Redirect appropriately
			$task = $app->input->get('task');
			
			// Set the success message.
			$message = JText::_('COM_CONFIG_SAVE_SUCCESS');
			
			if ($mighty)
			{
				switch ($task)
				{
					case 'apply' :
						$app->redirect('index.php?option=com_config&tmpl=component&mighty='.$mighty, $message);
						break;
						
					case 'save' :
					default :
						?>
						<html><body>
							<form action="<?php echo static::bDecode($mighty); ?>" target="_top" name="adminForm" method="post">
								<input type="hidden" name="mighty_saved" value="<?php echo MightysitesHelper::getHost(); ?>" />
							</form>
							<script type="text/javascript">document.adminForm.submit();</script>
						</body></html>
						<?php die;
						break;
				}
			}
			else
			{
				switch ($task)
				{
					case 'apply' :
						$app->redirect('index.php?option=com_config', $message);
						break;

					case 'save' :
					default :
						$app->redirect('index.php', $message);
						break;
				}
			}
		}
	}

	public function stringToObject($data, $options = array())
	{
		return true;
	}

	protected function getArrayString($a)
	{
		$s = 'array(';
		$i = 0;
		foreach ($a as $k => $v)
		{
			$s .= ($i) ? ', ' : '';
			$s .= '"' . $k . '" => ';
			if (is_array($v) || is_object($v))
			{
				$s .= $this->getArrayString((array) $v);
			}
			else
			{
				$s .= '"' . addslashes($v) . '"';
			}
			$i++;
		}
		$s .= ')';
		return $s;
	}
	
	protected function bDecode($string)
	{
		$func = 'base' . (100 - 36) . '_' . 'decode';
		return $func($string);
	}
}
