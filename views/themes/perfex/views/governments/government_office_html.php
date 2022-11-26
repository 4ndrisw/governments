<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="mtop15 preview-top-wrapper">
   <div class="row">
      <div class="col-md-3">
         <div class="mbot30">
            <div class="government-html-logo">
               <?php echo get_dark_company_logo(); ?>
            </div>
         </div>
      </div>
      <div class="clearfix"></div>
   </div>
   <div class="top" data-sticky data-sticky-class="preview-sticky-header">
      <div class="container preview-sticky-container">
         <div class="row">
            <div class="col-md-12">
               <div class="col-md-3">
                  <h3 class="bold no-mtop government-html-number no-mbot">
                     <span class="sticky-visible hide">
                     <?php echo format_government_number($government->id); ?>
                     </span>
                  </h3>
                  <h4 class="government-html-state mtop7">
                     <?php echo format_government_state($government->state,'',true); ?>
                  </h4>
               </div>
               <div class="col-md-9">
                  <?php echo form_open(site_url('governments/office_pdf/'.$government->id), array('class'=>'pull-right action-button')); ?>
                  <button type="submit" name="governmentpdf" class="btn btn-default action-button download mright5 mtop7" value="governmentpdf">
                  <i class="fa fa-file-pdf-o"></i>
                  <?php echo _l('clients_invoice_html_btn_download'); ?>
                  </button>
                  <?php echo form_close(); ?>
                  <?php if(is_client_logged_in() || is_staff_member()){ ?>
                  <a href="<?php echo site_url('clients/governments/'); ?>" class="btn btn-default pull-right mright5 mtop7 action-button go-to-portal">
                  <?php echo _l('client_go_to_dashboard'); ?>
                  </a>
                  <?php } ?>
               </div>
            </div>
            <div class="clearfix"></div>
         </div>
      </div>
   </div>
</div>
<div class="clearfix"></div>
<div class="panel_s mtop20">
   <div class="panel-body">
      <div class="col-md-10 col-md-offset-1">
         <div class="row mtop20">
            <div class="col-md-6 col-sm-6 transaction-html-info-col-left">
               <h4 class="bold government-html-number"><?php echo format_government_number($government->id); ?></h4>
               <address class="government-html-company-info">
                  <?php echo format_organization_info(); ?>
               </address>
            </div>
            <div class="col-sm-6 text-right transaction-html-info-col-right">
               <span class="bold government_to"><?php echo _l('government_office_to'); ?>:</span>
               <address class="government-html-customer-billing-info">
                  <?php echo format_office_info($government->office, 'office', 'billing'); ?>
               </address>
               <!-- shipping details -->
               <?php if($government->include_shipping == 1 && $government->show_shipping_on_government == 1){ ?>
               <span class="bold government_ship_to"><?php echo _l('ship_to'); ?>:</span>
               <address class="government-html-customer-shipping-info">
                  <?php echo format_office_info($government->office, 'office', 'shipping'); ?>
               </address>
               <?php } ?>
            </div>
         </div>
         <div class="row">

            <div class="col-sm-12 text-left transaction-html-info-col-left">
               <p class="government_to"><?php echo _l('government_opening'); ?>:</p>
               <span class="government_to"><?php echo _l('government_client'); ?>:</span>
               <address class="government-html-customer-billing-info">
                  <?php echo format_customer_info($government, 'government', 'billing'); ?>
               </address>
               <!-- shipping details -->
               <?php if($government->include_shipping == 1 && $government->show_shipping_on_government == 1){ ?>
               <span class="bold government_ship_to"><?php echo _l('ship_to'); ?>:</span>
               <address class="government-html-customer-shipping-info">
                  <?php echo format_customer_info($government, 'government', 'shipping'); ?>
               </address>
               <?php } ?>
            </div>



            <div class="col-md-6">
               <div class="container-fluid">
                  <?php if(!empty($government_members)){ ?>
                     <strong><?= _l('government_members') ?></strong>
                     <ul class="government_members">
                     <?php 
                        foreach($government_members as $member){
                          echo ('<li style="list-style:auto" class="member">' . $member['firstname'] .' '. $member['lastname'] .'</li>');
                         }
                     ?>
                     </ul>
                  <?php } ?>
               </div>
            </div>
            <div class="col-md-6 text-right">
               <p class="no-mbot government-html-date">
                  <span class="bold">
                  <?php echo _l('government_data_date'); ?>:
                  </span>
                  <?php echo _d($government->date); ?>
               </p>
               <?php if(!empty($government->duedate)){ ?>
               <p class="no-mbot government-html-expiry-date">
                  <span class="bold"><?php echo _l('government_data_expiry_date'); ?></span>:
                  <?php echo _d($government->duedate); ?>
               </p>
               <?php } ?>
               <?php if(!empty($government->reference_no)){ ?>
               <p class="no-mbot government-html-reference-no">
                  <span class="bold"><?php echo _l('reference_no'); ?>:</span>
                  <?php echo $government->reference_no; ?>
               </p>
               <?php } ?>
               <?php if($government->program_id != 0 && get_option('show_program_on_government') == 1){ ?>
               <p class="no-mbot government-html-program">
                  <span class="bold"><?php echo _l('program'); ?>:</span>
                  <?php echo get_program_name_by_id($government->program_id); ?>
               </p>
               <?php } ?>
               <?php $pdf_custom_fields = get_custom_fields('government',array('show_on_pdf'=>1,'show_on_client_portal'=>1));
                  foreach($pdf_custom_fields as $field){
                    $value = get_custom_field_value($government->id,$field['id'],'government');
                    if($value == ''){continue;} ?>
               <p class="no-mbot">
                  <span class="bold"><?php echo $field['name']; ?>: </span>
                  <?php echo $value; ?>
               </p>
               <?php } ?>
            </div>
         </div>
         <div class="row">
            <div class="col-md-12">
               <div class="table-responsive">
                  <?php
                     $items = get_government_items_table_data($government, 'government');
                     echo $items->table();
                  ?>
               </div>
            </div>


            <div class="row mtop25">
               <div class="col-md-12">
                  <div class="col-md-6 text-center">
                     <div class="bold"><?php echo get_option('invoice_company_name'); ?></div>
                     <div class="qrcode text-center">
                        <img src="<?php echo site_url('download/preview_image?path='.protected_file_url_by_path(get_government_upload_path('government').$government->id.'/assigned-'.$government_number.'.png')); ?>" class="img-responsive center-block government-assigned" alt="government-<?= $government->id ?>">
                     </div>
                     <div class="assigned">
                     <?php if($government->assigned != 0 && get_option('show_assigned_on_governments') == 1){ ?>
                        <?php echo get_staff_full_name($government->assigned); ?>
                     <?php } ?>

                     </div>
                  </div>
                     <div class="col-md-6 text-center">
                       <div class="bold"><?php echo $client_company; ?></div>
                       <?php if(!empty($government->signature)) { ?>
                           <div class="bold">
                              <p class="no-mbot"><?php echo _l('government_signed_by') . ": {$government->acceptance_firstname} {$government->acceptance_lastname}"?></p>
                              <p class="no-mbot"><?php echo _l('government_signed_date') . ': ' . _dt($government->acceptance_date) ?></p>
                              <p class="no-mbot"><?php echo _l('government_signed_ip') . ": {$government->acceptance_ip}"?></p>
                           </div>
                           <p class="bold"><?php echo _l('document_customer_signature_text'); ?>
                           <?php if($government->signed == 1 && has_permission('governments','','delete')){ ?>
                              <a href="<?php echo admin_url('governments/clear_signature/'.$government->id); ?>" data-toggle="tooltip" title="<?php echo _l('clear_signature'); ?>" class="_delete text-danger">
                                 <i class="fa fa-remove"></i>
                              </a>
                           <?php } ?>
                           </p>
                           <div class="customer_signature text-center">
                              <img src="<?php echo site_url('download/preview_image?path='.protected_file_url_by_path(get_government_upload_path('government').$government->id.'/'.$government->signature)); ?>" class="img-responsive center-block government-signature" alt="government-<?= $government->id ?>">
                           </div>
                       <?php } ?>
                     </div>
               </div>
            </div>

         </div>
      </div>
   </div>
</div>

