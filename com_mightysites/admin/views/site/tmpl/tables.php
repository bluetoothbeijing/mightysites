<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('_JEXEC') or die;
?>

<div class="control-group">
	<div class="control-label"></div>
	<div class="controls">
		<?php echo MightysitesHelper::sitesList('tableComponentsSelect', -1, ' onchange="selectAllTo(\'tableComponents\', this.options[this.selectedIndex].value)"', $this->item->id, JText::_('COM_MIGHTYSITES_SELECT_ALL_TO'), false, '-1', JText::_('COM_MIGHTYSITES_OWN_DATA'), '');?>
	</div>
</div>
<hr/>

<?php foreach ($this->tables as $table => $input) {?>
<div class="control-group">
	<div class="control-label"><label><?php echo '#__', $table;?></label></div>
	<div class="controls"><?php echo $input;?></div>
</div>
<?php }?>

<?php
jexit();