<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if(isset($client)){ ?>
	<h4 class="customer-profile-group-heading"><?php echo _l('governments'); ?></h4>
	<?php if(has_permission('governments','','create')){ ?>
		<a href="<?php echo admin_url('governments/government?customer_id='.$client->userid); ?>" class="btn btn-info mbot15<?php if($client->active == 0){echo ' disabled';} ?>"><?php echo _l('create_new_government'); ?></a>
	<?php } ?>
	<?php if(has_permission('governments','','view') || has_permission('governments','','view_own') || get_option('allow_staff_view_governments_assigned') == '1'){ ?>
		<a href="#" class="btn btn-info mbot15" data-toggle="modal" data-target="#client_zip_governments"><?php echo _l('zip_governments'); ?></a>
	<?php } ?>
	<div id="governments_total"></div>
	<?php
	$this->load->view('admin/governments/table_html', array('class'=>'governments-single-client'));
	//$this->load->view('admin/clients/modals/zip_governments');
	?>
<?php } ?>
