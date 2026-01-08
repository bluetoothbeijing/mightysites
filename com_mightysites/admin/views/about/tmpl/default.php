<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('_JEXEC') or die('Restricted access');

JHTML::_('behavior.tooltip');

$this->sidebar = JHtmlSidebar::render();
		
$component 	= JTable::getInstance('extension');
$component->load(JComponentHelper::getComponent('com_mightysites')->id);
if (strlen($component->manifest_cache)) {
	$data = json_decode($component->manifest_cache);
	if ($data) {
		foreach ($data as $key => $value) {
			$component->$key = $value;
		}
	}
}
?>

<script type="text/javascript">
	function checkVersion() {
		document.getElementById('versionButton').disabled = true;
		document.getElementById('versionSpinner').style.display = 'block';
		document.getElementById('versionDiv').innerHTML = '';
		new Request.HTML({
			url: 'index.php?option=com_mightysites&task=about.version&current=<?php echo $component->version;?>',
			method: 'get',
			update: document.getElementById('versionDiv'),
			onComplete: function(resp) { 
				document.getElementById('versionSpinner').style.display = 'none';
				document.getElementById('versionButton').disabled = false;
			}
		}).send();
	}
</script>

<form name="adminForm">
<?php if(!empty( $this->sidebar)): ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php else : ?>
	<div id="j-main-container">
<?php endif;?>

		
		<img src="components/com_mightysites/images/mightysites.png" alt="" align="left" />
		
		<table cellpadding="10" cellspacing="0">
			<tr valign="top">
				<td width="1%" nowrap="nowrap"><b><?php echo JText::_('COM_MIGHTYSITES_YOUR_VERSION');?></b></td>
				<td><?php echo $component->version;?></td>
			</tr>
			<tr valign="top">
				<td><input type="button" class="btn" value="<?php echo JText::_('COM_MIGHTYSITES_CHECK_LATEST_VERSION');?>" onclick="checkVersion()" id="versionButton" /></td>
				<td>
					<div class="progress progress-striped active" id="versionSpinner" style="display:none; width:100px;">
						<div class="bar" style="width: 100%;"></div>
					</div>
					<span id="versionDiv"></span>
				</td>
			</tr>
		</table>
		
	</div>
</form>
