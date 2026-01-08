<?php
/**
* @package		Mightysites
* @copyright	Copyright (C) 2009-2017 AlterBrains.com. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*/

defined('_JEXEC') or die;

JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.tabstate');

?>

<script language="javascript" type="text/javascript">
	Joomla.submitbutton = function(task)
	{
		if (task == 'database.cancel' || document.formvalidator.isValid(document.getElementById('database-form'))) {
			Joomla.submitform(task, document.getElementById('database-form'));
		}
		else {
			alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
		}
	}
	
	function checkConnection(type)
	{
		var form = document.getElementById('database-form');
		document.getElementById('checkWaiter').style.visibility = 'visible';
		document.getElementById('checkButton').disabled = true;
		document.getElementById('createInfo') ? document.getElementById('createInfo').innerHTML = '' : null;
		
		new Request.HTML({
			url: '<?php echo JRoute::_('index.php'); ?>',
			update: 'checkInfo',
			noCache: true, 
			data: {
				option: 	'com_mightysites',
				task: 		'database.check',
				db: 		document.getElementById('jform_db').value,
				dbprefix: 	document.getElementById('jform_dbprefix').value,
				id: 		<?php echo (int)$this->item->id;?>,
				type: 		type,
				'<?php echo JSession::getFormToken();?>': '1'
			},
			onSuccess: function() { 
				document.getElementById('checkWaiter').style.visibility = 'hidden';
				document.getElementById('checkButton').disabled = false;
			}
		}).send();
	}
	
	function createDatabase()
	{
		var form = document.getElementById('database-form');
		var text = '<?php echo JText::sprintf('COM_MIGHTYSITES_REALLY_CREATE_DB', '________');?>';
		
		if (confirm(text.replace(/________/, document.getElementById('jform_db').value)))
		{
			document.getElementById('createWaiter').style.visibility = 'visible';
			document.getElementById('createButton').disabled = true;
			document.getElementById('createInfo').innerHTML = '';
			new Request.HTML({
				url: '<?php echo JRoute::_('index.php'); ?>',
				update: 'createInfo',
				noCache: true, 
				data: {
					option: 'com_mightysites',
					task: 	'database.create',
					db: 	document.getElementById('jform_db').value,
					'<?php echo JSession::getFormToken();?>': '1'
				},
				onSuccess: function() { 
					document.getElementById('createWaiter').style.visibility = 'hidden';
					document.getElementById('createButton').disabled = false;
				}
			}).send();
		}
	}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_mightysites&view=database&layout=edit&id='.(int) $this->item->id); ?>" class="form-validate" method="post" name="database-form" id="database-form">

	<fieldset class="form-horizontal">
		<ul class="nav nav-tabs">
			<li class="active"><a href="#tab-common" data-toggle="tab"><?php echo JText::_('COM_MIGHTYSITES_TAB_SETTINGS'); ?></a></li>
		</ul>
		
		<div class="tab-content">

			<div class="tab-pane active" id="tab-common">
				<?php echo $this->form->renderField('domain'); ?>
				
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('db'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('db'); ?>
						<?php if (!$this->item->id) {?>
						<input class="btn" type="button" id="createButton" value="<?php echo JText::_('COM_MIGHTYSITES_CREATE_DATABASE');?>" onclick="createDatabase()" />
						&nbsp;
						<img align="top" src="../media/system/images/mootree_loader.gif" alt="<?php echo JText::_('COM_MIGHTYSITES_LOADING');?>" id="createWaiter" style="visibility:hidden" />
						<span id="createInfo"></span>
						<?php }?>
					
					</div>
				</div>
				
				<?php echo $this->form->renderField('dbprefix'); ?>

				<?php if (!$this->item->id) : ?>
				<?php echo $this->form->renderField('source_db'); ?>
				<?php endif; ?>
				
				<div class="control-group">
					<div class="control-label"></div>
					<div class="controls">
						<input class="btn" type="button" id="checkButton" value="<?php echo JText::_('COM_MIGHTYSITES_CHECK_CONNECTION');?>" onclick="checkConnection(1)" />
						&nbsp;
						<img align="top" src="../media/system/images/mootree_loader.gif" alt="<?php echo JText::_('COM_MIGHTYSITES_LOADING');?>" id="checkWaiter" style="visibility:hidden" />
						<span id="checkInfo"></span>
					</div>
				</div>
			</div>
		
		</div>
	</fieldset>
	
	<input type="hidden" name="type" value="<?php echo $this->item->type;?>" />
	<input type="hidden" name="task" value="" />
	<?php echo JHtml::_('form.token'); ?>

</form>
