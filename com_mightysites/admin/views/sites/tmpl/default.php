<?php
/**
* @package		Mightysites
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
 
defined('_JEXEC') or die('Restricted access');

JHtml::_('bootstrap.tooltip');
JHtml::_('bootstrap.popover');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.modal');

// Special popover for data overload, it can have too much data, hence allow manual
JFactory::getDocument()->addScriptDeclaration('
jQuery(document).ready(function() {
jQuery(".hasMightyPopover").popover({ trigger: "manual" , html: true, animation:false})
.on("mouseenter", function () {
    var _this = this;
    jQuery(this).popover("show");
    jQuery(".popover").on("mouseleave", function () {
        jQuery(_this).popover("hide");
    });
}).on("mouseleave", function () {
    var _this = this;
    setTimeout(function () {
        if (!jQuery(".popover:hover").length) {
            jQuery(_this).popover("hide");
        }
    }, 300);
});
});
');


$user		= JFactory::getUser();
$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
$sortFields = $this->getSortFields();

$this->sidebar = JHtmlSidebar::render();
?>
<script type="text/javascript">
	Joomla.submitbutton = function(task) {
		var form = document.adminForm;
		if (task == 'sites.remove' && window.confirm('<?php echo JText::_('COM_MIGHTYSITES_REALLY_DELETE_DB', true); ?>')) {
			/* Confirm twice! */
			if (window.confirm('<?php echo JText::_('COM_MIGHTYSITES_REALLY_DELETE_DB', true); ?>')) {
				form.delete_tables.value = 'true';
			}
		}
		Joomla.submitform(task, document.getElementById('adminForm'));
	}
</script>

<style type="text/css">
.table-strapless td {
	background:none !important;
	border:none !important;
}
.table-strapless hr {
	margin:0 !important;
}
.popover {
	max-width:none !important;
}
.popover .popover-content {
	max-height:320px;
	overflow-y:auto;
}
</style>

<form action="<?php echo JRoute::_('index.php?option=com_mightysites&view=sites'); ?>" method="post" name="adminForm" id="adminForm">
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
						<?php echo JText::_('#'); ?>
					</th>
					<th width="1%" class="center">
						<?php echo JHtml::_('grid.checkall'); ?>
					</th>
					<th class="nowrap" width="1%">
						<?php echo JText::_('COM_MIGHTYSITES_HEADING_ONLINE'); ?>
					</th>
					<th class="nowrap">
						<?php echo JHTML::_('searchtools.sort',  'COM_MIGHTYSITES_HEADING_SITE', 'a.domain', $listDirn, $listOrder); ?>
					</th>
					<th class="nowrap center" width="25%">
						<?php echo JText::_('COM_MIGHTYSITES_HEADING_ACTIONS'); ?>
					</th>
					<th class="nowrap center hidden-phone" width="1%" colspan="3">
						<?php echo JText::_('COM_MIGHTYSITES_HEADING_INFORMATION'); ?>
					</th>
					<th class="nowrap hidden-phone" width="5%">
						<?php echo JText::_('COM_MIGHTYSITES_HEADING_LANGUAGE'); ?>
					</th>
					<th class="nowrap hidden-phone" width="5%">
						<?php echo JHTML::_('searchtools.sort',  'COM_MIGHTYSITES_HEADING_DATABASE_NAME', 'a.db', $listDirn, $listOrder);?>
					</th>
					<th class="nowrap hidden-phone" width="5%">
						<?php echo JText::_('COM_MIGHTYSITES_HEADING_DATABASE_USER'); ?>
					</th>
					<th class="nowrap hidden-phone" width="5%">
						<?php echo JHTML::_('searchtools.sort',  'COM_MIGHTYSITES_HEADING_DATABASE_PREFIX', 'a.dbprefix', $listDirn, $listOrder);?>
					</th>
					<th class="nowrap" width="1%">
						<?php echo JHTML::_('searchtools.sort',  'ID', 'a.id', $listDirn, $listOrder); ?>
					</th>
				</tr>
			</thead>
			
			<tfoot>
				<tr>
					<td colspan="14">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
			
			<tbody>
			<?php foreach ($this->items as $i => $item) {
				$canEdit	= $user->authorise('core.edit',			'com_mightysites');
				$canCheckin	= $user->authorise('core.manage',		'com_checkin') || $item->checked_out==$user->get('id') || $item->checked_out==0; 
				$canChange	= $user->authorise('core.edit.state',	'com_mightysites') && $canCheckin; 
			
				?>
				<tr class="row<?php echo $i % 2; ?>"> 
					
					<td class="nowrap center hidden-phone"><?php echo $this->pagination->getRowOffset($i); ?></td>
					
					<td class="center"><?php echo JHtml::_('grid.id', $i, $item->id); ?></td>

					<td class="center"><?php echo JHtml::_('jgrid.published', !@$item->offline, $i, 'sites.', $canChange, 'cb'); ?></td>
					
					<td class="nowrap">
						<?php if ($item->checked_out) : ?>
							<?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'sites.', $canCheckin); ?>
						<?php endif; ?>
						
						<?php if ($canEdit) {?>
							<a href="<?php echo JRoute::_('index.php?option=com_mightysites&task=site.edit&id='.(int) $item->id); ?>">
								<?php echo $item->title; ?></a>
						<?php } else {?>
								<?php echo $item->title; ?>
						<?php }?>
						<div class="small"><?php echo $this->escape(@$item->sitename); ?></div>
					</td>
					
					<td class="">
						<?php if (JFactory::getUser()->authorise('core.edit', 'com_mightysites')) : ?>
						<?php /*<a class="nowrap modal" href="<?php echo $item->link; ?>" rel="{handler: 'iframe', size: {x: 1200, y: 550}, marginInner: {x: 10, y: 0}}">*/?>
						<a class="nowrap" href="#" onclick="document.siteFormConfig<?php echo $item->id ;?>.submit(); return false">
							<i class="icon-cog"></i> <?php echo JText::_('COM_MIGHTYSITES_ACTION_CONFIG'); ?>
						</a>
						&nbsp;&nbsp;
						
						<a class="nowrap" href="#" onclick="document.siteFormAdmin<?php echo $item->id ;?>.submit(); return false">
							<i class="icon-share-alt"></i> <?php echo JText::_('COM_MIGHTYSITES_ACTION_ADMIN'); ?>
						</a>
						&nbsp;&nbsp;
						<?php endif; ?>
						
						<a class="nowrap" href="http://<?php echo $item->domain; ?>" target="_blank">
							<i class="icon-eye"></i> <?php echo JText::_('COM_MIGHTYSITES_ACTION_PREVIEW'); ?>
						</a>
					</td>
					
					<td class="center hidden-phone" width="1%">
						<?php if (!empty($item->contentTip)) : ?>
							<?php if (!empty($item->mighty_enable)) : ?>
							<span class="badge badge-success hasMightyPopover" data-placement="right" data-content="<?php echo $item->contentTip; ?>" title="<?php echo JText::_('COM_MIGHTYSITES_INFO_CONTENT_SHARING'), ': ', JText::_('JENABLED'); ?>">
								<i class="icon-shuffle"<?php /*?> title="<?php echo JText::_('COM_MIGHTYSITES_INFO_CONTENT_SHARING'); ?>"<?php */?>></i>
							</span>
							<?php else : ?>
							<span class="badge badge-important hasMightyPopover" data-placement="right" data-content="<?php echo $item->contentTip; ?>" title="<?php echo JText::_('COM_MIGHTYSITES_INFO_CONTENT_SHARING'), ': ', JText::_('JDISABLED'); ?>">
								<i class="icon-shuffle"<?php /*?> title="<?php echo JText::_('COM_MIGHTYSITES_INFO_CONTENT_SHARING'); ?>"<?php */?>></i>
							</span>
							<?php endif; ?>
						<?php endif; ?>
					</td>
						
					<td class="center hidden-phone" width="1%">
						<?php if ($item->singleTip) : ?>
						<span class="badge badge-<?php echo (!empty($item->mighty_slogin) || !empty($item->mighty_slogout)) ? 'success' : 'important'; ?> hasPopover" data-placement="right" data-content="<?php echo $item->singleTip; ?>" title="<?php echo JText::_('COM_MIGHTYSITES_INFO_SINGLE'); ?>">
							<i class="icon-users"<?php /*?> title="<?php echo JText::_('COM_MIGHTYSITES_INFO_SINGLE'); ?>"<?php */?>></i>
						</span>
						<?php endif; ?>
					</td>

					<td class="center hidden-phone" width="1%">
						<?php if ($item->aliases) : ?>
						<span class="badge hasPopover" data-placement="right" data-content="<?php echo nl2br($item->aliases); ?>" title="<?php echo JText::_('COM_MIGHTYSITES_INFO_ALIASES'); ?>">
							<i class="icon-copy"<?php /*?> title="<?php echo JText::_('COM_MIGHTYSITES_INFO_ALIASES'); ?>"<?php */?>></i>
						</span>
						<?php endif; ?>
					</td>
			
					<td class="small center hidden-phone"><?php echo (isset($item->mighty_language) && $item->mighty_language) ? $item->mighty_language : JText::_('JDEFAULT'); ?></td>
					<td class="small center hidden-phone"><?php echo @$item->db; ?></td>
					<td class="small center hidden-phone"><?php echo @$item->user; ?></td>
					<td class="small center hidden-phone"><?php echo @$item->dbprefix; ?></td>
					<td class="center"><?php echo $item->id; ?></td>
				</tr>
			<?php }?>
			</tbody>
		</table>
		<?php endif; ?>

		<input type="hidden" name="delete_tables" value="" />

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo JHTML::_('form.token'); ?>
	</div>
</form>

<?php foreach ($this->items as $i => $item) : ?>
	<?php echo $this->showForm($item->link, 'siteFormConfig'.$item->id); ?>
	<?php echo $this->showForm($item->link2, 'siteFormAdmin'.$item->id); ?>
<?php endforeach; ?>

