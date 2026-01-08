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
		if (task == 'site.cancel' || document.formvalidator.isValid(document.getElementById('site-form'))) {
			Joomla.submitform(task, document.getElementById('site-form'));
		}
		else {
			alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
		}
	}
	
	function checkConnection(type)
	{
		var form = document.getElementById('site-form');
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
				user: 		document.getElementById('jform_user').value,
				password: 	document.getElementById('jform_password').value,
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
		var form = document.getElementById('site-form');
		var text = '<?php echo JText::sprintf('COM_MIGHTYSITES_REALLY_CREATE_DB', '________');?>';
		if (confirm(text.replace(/________/, document.getElementById('jform_db').value))) {
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
	
	function loadTables()
	{
		document.getElementById('tablesWaiter').style.visibility = 'visible';
		new Request.HTML({
			url: '<?php echo JRoute::_('index.php'); ?>',
			update: 'tablesArea',
			noCache: true, 
			data: {
				option: 'com_mightysites',
				view: 	'site',
				layout: 'tables',
				id:		<?php echo (int) $this->item->id;?>
			},
			onSuccess: function() {
				jQuery('#tablesArea select').chosen({
					disable_search_threshold : 10,
					allow_single_deselect : true
				});
			}
		}).send();
	}
	
	function selectAllTo(scope, val) {
		if (val >= 0) {
			jQuery('#'+scope+' select').each(function(i, e) {
				jQuery(e).val(val)
				jQuery(e).trigger("liszt:updated");
			});
		}
	}

	function firstChildOf(el) {
		for (var i=0; i<el.childNodes.length; i++) {
			if (el.childNodes[i].nodeType == 1) {
				return el.childNodes[i];
			}
		}
	}
	
	function getChildNode(el, num) {
		nn=-1;
		for (var i=0; i<el.childNodes.length; i++) {
			if (el.childNodes[i].nodeType == 1) {
				nn++;
				if (nn == num) return el.childNodes[i];
			}
		}
	}

	function addSingleDomain() {
		var table = document.getElementById('singleDomainsTable').tBodies[0];
		var newTr = table.rows[table.rows.length-1].cloneNode(true);
		table.appendChild(newTr);
		
		var newTr = table.rows[table.rows.length-1];
		firstChildOf(getChildNode(newTr, 0)).value = '';
		firstChildOf(getChildNode(newTr, 1)).value = '';
		return false;
	}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_mightysites&view=site&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="site-form" id="site-form" class="form-validate">

	<fieldset class="form-horizontal">
		<ul class="nav nav-tabs">
			<li class="active"><a href="#tab-common" data-toggle="tab"><?php echo JText::_('COM_MIGHTYSITES_TAB_BASIC'); ?></a></li>
			<li><a href="#tab-database" data-toggle="tab"><?php echo JText::_('COM_MIGHTYSITES_TAB_DATABASE');?></a></li>
			<li><a href="#tab-sharing" data-toggle="tab"><?php echo JText::_('COM_MIGHTYSITES_TAB_CONTENT_SHARING');?></a></li>
			<li><a href="#tab-single" data-toggle="tab"><?php echo JText::_('COM_MIGHTYSITES_TAB_SINGLE');?></a></li>
			<li><a href="#tab-overrides" data-toggle="tab"><?php echo JText::_('COM_MIGHTYSITES_TAB_OVERRIDES');?></a></li>
			<li><a href="#tab-advanced" data-toggle="tab"><?php echo JText::_('COM_MIGHTYSITES_TAB_ADVANCED');?></a></li>
		</ul>
		
		<div class="tab-content">

			<div class="tab-pane active" id="tab-common">
				<?php echo $this->form->renderField('domain'); ?>
				<?php echo $this->form->renderField('aliases'); ?>
				
				<?php if (!$this->item->id) : ?>
				<?php echo $this->form->renderField('source_config'); ?>
				<?php echo $this->form->renderField('source_db'); ?>
				<?php endif; ?>
			</div>
			
			<div class="tab-pane" id="tab-database">
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
				<?php echo $this->form->renderField('user'); ?>
				<?php echo $this->form->renderField('password'); ?>
				<?php echo $this->form->renderField('dbtype'); ?>
				
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
			
			<div class="tab-pane" id="tab-sharing">
				<?php if (!$this->item->id) {
					echo JText::_('COM_MIGHTYSITES_APPLY_SETTINGS');
				} else {?>
					
				<?php foreach ($this->form->getFieldset('replacements') as $field) : ?>
				<div class="control-group">
					<div class="control-label"><?php echo $field->label; ?></div>
					<div class="controls"><?php echo $field->input; ?></div>
				</div>
				<?php endforeach; ?>
				
				<div class="row-fluid">
					<div class="span4" id="coreComponents">
						<legend><?php echo JText::_('COM_MIGHTYSITES_FIELDSET_CORE_COMPONENTS');?></legend>
						<div class="control-group">
							<div class="control-label"></div>
							<div class="controls">
								<?php echo MightysitesHelper::sitesList('coreComponentsSelect', -1, ' onchange="selectAllTo(\'coreComponents\', this.options[this.selectedIndex].value)"', $this->item->id, JText::_('COM_MIGHTYSITES_SELECT_ALL_TO'), false, '-1', JText::_('COM_MIGHTYSITES_OWN_DATA'), '');?>
							</div>
						</div>
						<hr/>
						
						<?php foreach ($this->synchs_core as $synch => $input) {?>
						<div class="control-group">
							<div class="control-label"><label class="hasTip" title="<?php echo JText::_('COM_MIGHTYSITES_SYNCH_LABEL_'.$synch);?>::<?php echo JText::_('COM_MIGHTYSITES_SYNCH_DESC_'.$synch);?>"><?php echo JText::_('COM_MIGHTYSITES_SYNCH_LABEL_'.$synch);?></label></div>
							<div class="controls"><?php echo $input;?></div>
						</div>
						<?php }?>
					</div>
					
					<div class="span4" id="customComponents">
						<legend><?php echo JText::_('COM_MIGHTYSITES_FIELDSET_CUSTOM_COMPONENTS');?></legend>
						<div class="control-group">
							<div class="control-label"></div>
							<div class="controls">
								<?php echo MightysitesHelper::sitesList('customComponentsSelect', -1, ' onchange="selectAllTo(\'customComponents\', this.options[this.selectedIndex].value)"', $this->item->id, JText::_('COM_MIGHTYSITES_SELECT_ALL_TO'), false, '-1', JText::_('COM_MIGHTYSITES_OWN_DATA'), '');?>
							</div>
						</div>
						<hr/>
						
						<?php foreach ($this->synchs_custom as $synch => $input) {?>
						<div class="control-group">
							<div class="control-label"><label class="hasTip" title="<?php echo JText::_('COM_MIGHTYSITES_SYNCH_LABEL_'.$synch);?>::<?php echo JText::_('COM_MIGHTYSITES_SYNCH_DESC_'.$synch);?>"><?php echo JText::_('COM_MIGHTYSITES_SYNCH_LABEL_'.$synch);?></label></div>
							<div class="controls"><?php echo $input;?></div>
						</div>
						<?php }?>
					</div>
					
					<div class="span4" id="tableComponents">
						<legend><?php echo JText::_('COM_MIGHTYSITES_FIELDSET_DATABASE_TABLES');?></legend>
						
						<div id="tablesArea">
							<input type="button" class="btn" onclick="loadTables()" value="<?php echo JText::_('COM_MIGHTYSITES_LOAD_TABLES');?>" />
							&nbsp;
							<img align="top" src="../media/system/images/mootree_loader.gif" alt="<?php echo JText::_('COM_MIGHTYSITES_LOADING');?>" id="tablesWaiter" style="visibility:hidden" />
							
							<?php // Keep current settings
							$tables = 0;
							foreach ($this->params->toArray() as $key => $value)
							{
								if (strpos($key, 'table_') !== false)
								{
									echo '<input type="hidden" name="jform[tables]['.substr($key, 6).']" value="'.$value.'" />';
									$tables++;
								}
							}?>
							<p> </p>
							<p><?php echo JText::plural('COM_MIGHTYSITES_N_TABLES_ASSIGNED', $tables); ?></p>
						</div>
					</div>	
					
				</div>
				<?php }?>
			</div>
			
			<div class="tab-pane" id="tab-single">
				<?php if (!$this->item->id) {
					echo JText::_('COM_MIGHTYSITES_APPLY_SETTINGS');
				} else {?>
				
				<?php foreach ($this->form->getFieldset('single') as $field) : ?>
				<div class="control-group">
					<div class="control-label"><?php echo $field->label; ?></div>
					<div class="controls"><?php echo $field->input; ?></div>
				</div>
				<?php endforeach; ?>
				
				<legend><?php echo JText::_('COM_MIGHTYSITES_FIELDSET_SINGLE_SITES');?></legend>
				
				<table class="table table-striped" style="width:auto" id="singleDomainsTable">
					<thead>
						<tr><th><?php echo JText::_('COM_MIGHTYSITES_DOMAIN');?></th>
							<th><?php echo JText::_('COM_MIGHTYSITES_SECRET_KEY');?></th>
						</tr>
					</thead>
					<tbody>
						<?php 
						$i = 0;
						while ($this->params->get('single_domain'.(string)$i)) {?>
							<tr><td><input type="text" class="inputbox" name="single_domain[]" value="<?php echo $this->params->get('single_domain'.(string)$i)?>" /></td>
								<td><input type="text" class="inputbox" name="single_key[]" value="<?php echo $this->params->get('single_key'.(string)$i)?>" /></td>
							</tr>
							<?php
							$i++;
						}
						if (!$i) {?>
							<tr><td><input type="text" class="inputbox" name="single_domain[]" value="" /></td>
								<td><input type="text" class="inputbox" name="single_key[]" value="" /></td>
							</tr>
						<?php }?>
					</tbody>
				</table>
					
				<p></p>
				<p><input type="button" class="btn" onClick="return addSingleDomain()" value="<?php echo JText::_('COM_MIGHTYSITES_ADD_SINGLE_SITE');?>" /></p>
				<?php }?>
			</div>
			
			<div class="tab-pane" id="tab-overrides">
				<?php if (!$this->item->id) {
					echo JText::_('COM_MIGHTYSITES_APPLY_SETTINGS');
				} else {?>
				
				<?php foreach ($this->form->getFieldset('overrides') as $field) : ?>
				<div class="control-group">
					<div class="control-label"><?php echo $field->label; ?></div>
					<div class="controls"><?php echo $field->input; ?></div>
				</div>
				<?php endforeach; ?>
				<?php }?>
			</div>

			<div class="tab-pane" id="tab-advanced">
				<?php if (!$this->item->id) {
					echo JText::_('COM_MIGHTYSITES_APPLY_SETTINGS');
				} else {?>
				
				<?php foreach ($this->form->getFieldset('advanced') as $field) : ?>
				<div class="control-group">
					<div class="control-label"><?php echo $field->label; ?></div>
					<div class="controls"><?php echo $field->input; ?></div>
				</div>
				<?php endforeach; ?>
				<?php }?>
			</div>			
		</div>
	</fieldset>
		
	<input type="hidden" name="type" value="<?php echo $this->item->type;?>" />
	<input type="hidden" name="task" value="" />
	<?php echo JHtml::_('form.token'); ?>

</form>
