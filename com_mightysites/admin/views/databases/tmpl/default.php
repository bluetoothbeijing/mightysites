<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/
defined('_JEXEC') or die('Restricted access');

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.modal');

$user		= JFactory::getUser();
$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));

$this->sidebar = JHtmlSidebar::render();
?>
		
<script language="javascript" type="text/javascript">
	Joomla.submitbutton = function(task) {
		var form = document.adminForm;
		if (task == 'databases.remove' && window.confirm('<?php echo JText::_('COM_MIGHTYSITES_REALLY_DELETE_DB', true);?>')) {
			form.delete_tables.value = 'true';
		}
		Joomla.submitform(task, document.getElementById('adminForm'));
	}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_mightysites&view=databases');?>" method="post" name="adminForm" id="adminForm">
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<?php
		// Search tools bar
		echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));
		?>
		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
		<table class="table table-striped" id="weblinkList">
			<thead>
				<tr>
					<th class="nowrap hidden-phone" width="1%" align="left">
						<?php echo JText::_('Num');?>
					</th>
					<th width="1%" class="center">
						<?php echo JHtml::_('grid.checkall'); ?>
					</th>
					<th class="nowrap" align="left">
						<?php echo JHTML::_('searchtools.sort',  'COM_MIGHTYSITES_HEADING_DATABASE_TITLE', 'a.domain', $listDirn, $listOrder);?>
					</th>
					<th class="nowrap center hidden-phone" width="15%">
						<?php echo JHTML::_('searchtools.sort',  'COM_MIGHTYSITES_HEADING_DATABASE_ORIGIN', 'a.type', $listDirn, $listOrder);?>
					</th>
					<th class="nowrap center" width="15%">
						<?php echo JHTML::_('searchtools.sort',  'COM_MIGHTYSITES_HEADING_DATABASE_NAME', 'a.db', $listDirn, $listOrder);?>
					</th>
					<th class="nowrap center" width="15%">
						<?php echo JHTML::_('searchtools.sort',  'COM_MIGHTYSITES_HEADING_DATABASE_PREFIX', 'a.dbprefix', $listDirn, $listOrder);?>
					</th>
					<th class="nowrap center" width="1%">
						<?php echo JHTML::_('searchtools.sort',  'ID', 'a.id', $listDirn, $listOrder);?>
					</th>
				</tr>
			</thead>

			<tfoot>
				<tr>
					<td colspan="15">
						<?php echo $this->pagination->getListFooter();?>
					</td>
				</tr>
			</tfoot> 

			<tbody>
				<?php foreach ($this->items as $i => $item) :
				$canEdit	= $user->authorise('core.edit',			'com_mightysites');
				$canCheckin	= $user->authorise('core.manage',		'com_checkin') || $item->checked_out==$user->get('id') || $item->checked_out==0; 
				$canChange	= $user->authorise('core.edit.state',	'com_mightysites') && $canCheckin; 
				
				// Sites are edited via own link!
				if ($item->type == 1)
				{
					$link = 'index.php?option=com_mightysites&task=site.edit&id='.(int) $item->id;
				}
				else
				{
					$link = 'index.php?option=com_mightysites&task=database.edit&id='.(int) $item->id;
				}
			
				?>
				<tr class="row<?php echo $i % 2;?>"> 
					
					<td class="nowrap center hidden-phone">
						<?php echo $this->pagination->getRowOffset($i);?>
					</td>
					
					<td class="nowrap center"><?php if ($item->type == 2) : ?>
						<?php echo JHtml::_('grid.id', $i, $item->id);?>
						<?php endif; ?>
					</td>
					
					<td class="nowrap">
						<?php if ($item->checked_out) : ?>
							<?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'databases.', $canCheckin);?>
						<?php endif;?>
						
						<?php if ($canEdit) {?>
							<a href="<?php echo JRoute::_($link);?>">
								<?php echo $item->domain;?></a>
						<?php } else {?>
								<?php echo $item->domain;?>
						<?php }?>
					</td>
					
					<td class="nowrap center hidden-phone">
						<?php if ($item->type == 1) {
							echo JText::_('COM_MIGHTYSITES_DATABASE_ORIGIN_SITE');
						} else {
							echo JText::_('COM_MIGHTYSITES_DATABASE_ORIGIN_DATABASE');
						}?>
					</td>
					
					<td class="nowrap center">
						<?php echo @$item->db;?>
					</td>
					<td class="nowrap center">
						<?php echo @$item->dbprefix;?>
					</td>
					<td class="nowrap center">
						<?php echo $item->id;?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	
		<input type="hidden" name="delete_tables" value="" />
	
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo JHTML::_('form.token');?>
	</div>
</form> 
