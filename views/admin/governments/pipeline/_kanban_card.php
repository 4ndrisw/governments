<?php defined('BASEPATH') or exit('No direct script access allowed');
   if ($government['state'] == $state) { ?>
<li data-government-id="<?php echo $government['id']; ?>" class="<?php if($government['invoiceid'] != NULL){echo 'not-sortable';} ?>">
   <div class="panel-body">
      <div class="row">
         <div class="col-md-12">
            <h4 class="bold pipeline-heading"><a href="<?php echo admin_url('governments/list_governments/'.$government['id']); ?>" onclick="government_pipeline_open(<?php echo $government['id']; ?>); return false;"><?php echo format_government_number($government['id']); ?></a>
               <?php if(has_permission('governments','','edit')){ ?>
               <a href="<?php echo admin_url('governments/government/'.$government['id']); ?>" target="_blank" class="pull-right"><small><i class="fa fa-pencil-square-o" aria-hidden="true"></i></small></a>
               <?php } ?>
            </h4>
            <span class="inline-block full-width mbot10">
            <a href="<?php echo admin_url('clients/client/'.$government['clientid']); ?>" target="_blank">
            <?php echo $government['company']; ?>
            </a>
            </span>
         </div>
         <div class="col-md-12">
            <div class="row">
               <div class="col-md-8">
                  <span class="bold">
                  <?php echo _l('government_total') . ':' . app_format_money($government['total'], $government['currency_name']); ?>
                  </span>
                  <br />
                  <?php echo _l('government_data_date') . ': ' . _d($government['date']); ?>
                  <?php if(is_date($government['duedate']) || !empty($government['duedate'])){
                     echo '<br />';
                     echo _l('government_data_expiry_date') . ': ' . _d($government['duedate']);
                     } ?>
               </div>
               <div class="col-md-4 text-right">
                  <small><i class="fa fa-paperclip"></i> <?php echo _l('government_notes'); ?>: <?php echo total_rows(db_prefix().'notes', array(
                     'rel_id' => $government['id'],
                     'rel_type' => 'government',
                     )); ?></small>
               </div>
               <?php $tags = get_tags_in($government['id'],'government');
                  if(count($tags) > 0){ ?>
               <div class="col-md-12">
                  <div class="mtop5 kanban-tags">
                     <?php echo render_tags($tags); ?>
                  </div>
               </div>
               <?php } ?>
            </div>
         </div>
      </div>
   </div>
</li>
<?php } ?>
