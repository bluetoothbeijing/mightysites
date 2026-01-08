<?php
/**
 * Part of the Joomla Framework Registry Package
 *
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Registry\Format;

use Joomla\Registry\AbstractRegistryFormat;

/**
 * PHP class format handler for Registry
 *
 * @since  1.0
 */
class Php extends AbstractRegistryFormat
{
	/**
	 * Converts an object into a php class string.
	 * - NOTE: Only one depth level is supported.
	 *
	 * @param   object  $object  Data Source Object
	 * @param   array   $params  Parameters used by the formatter
	 *
	 * @return  string  Config class formatted string
	 *
	 * @since   1.0
	 */
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

		$str = "<?php\nclass " . $params['class'] . " {\n";
		$str .= $vars;
		$str .= "}";

		// Use the closing tag if it not set to false in parameters.
		if (!isset($params['closingtag']) || $params['closingtag'] !== false)
		{
			$str .= "\n?>";
		}
		
		// default behavior
		if ($params['class'] != 'JConfig') {
			return $str;
		}
		// our behavior
		else {
			$app = \JFactory::getApplication();
			// get domain
			require_once(JPATH_ADMINISTRATOR.'/components/com_mightysites/helpers/helper.php');

			$file 	= \MightysitesHelper::getConfigFilename();
			
			// Get the new FTP credentials.
			jimport('joomla.client.helper');
			$ftp = \JClientHelper::getCredentials('ftp', true);
			
			// Attempt to make the file writeable if using FTP.
			if (!$ftp['enabled'] && \JPath::isOwner($file) && !\JPath::setPermissions($file, '0644')) {
				$link = 'index.php?option=com_config';
				$app->redirect($link, \JText::_('COM_CONFIG_ERROR_CONFIGURATION_PHP_NOTWRITABLE'), 'error');
			}

			// Attempt to write the configuration file as a PHP class named JConfig.
			if (!\JFile::write($file, $str)) {
				$link = 'index.php?option=com_config';
				$app->redirect($link, \JText::_('COM_CONFIG_ERROR_WRITE_FAILED'), 'error');
			}
			
			\MightysitesHelper::patchConfiguration();

			// Get the new FTP credentials.
			$ftp = \JClientHelper::getCredentials('ftp', true);

			// Attempt to make the file unwriteable if using FTP.
			if (\JFactory::getConfig()->get('ftp_enable') == 0 && !$ftp['enabled'] && \JPath::isOwner($file) && !\JPath::setPermissions($file, '0444')) {
				JError::raiseNotice('SOME_ERROR_CODE', \JText::_('COM_CONFIG_ERROR_CONFIGURATION_PHP_NOTUNWRITABLE'));
			}
	
			// Redirect appropriately
			$task = $app->input->get('task');
			
			// Set the success message.
			$message = \JText::_('COM_CONFIG_SAVE_SUCCESS');
			
			// Save the data in the session.
			$app->setUserState('com_config.config.global.data', (array)$object);
			

			// Set the success message.
			$app->enqueueMessage(\JText::_('COM_CONFIG_SAVE_SUCCESS'));
			
			// Set the redirect based on the task.
			switch ($task)
			{
				case 'config.save.application.apply':
					$app->redirect(\JRoute::_('index.php?option=com_config', false));
					break;
	
				case 'config.save.application.save':
				default:
					$app->redirect(\JRoute::_('index.php', false));
					break;
			}
		}
	}

	/**
	 * Parse a PHP class formatted string and convert it into an object.
	 *
	 * @param   string  $data     PHP Class formatted string to convert.
	 * @param   array   $options  Options used by the formatter.
	 *
	 * @return  object   Data object.
	 *
	 * @since   1.0
	 */
	public function stringToObject($data, array $options = array())
	{
		return true;
	}

	/**
	 * Method to get an array as an exported string.
	 *
	 * @param   array  $a  The array to get as a string.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
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
}
