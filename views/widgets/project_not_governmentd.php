<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
    $CI = &get_instance();
    $CI->load->model('governments/governments_model');
    $governments = $CI->governments_model->get_program_not_scheduled(get_staff_user_id());
?>

<div class="widget" id="widget-<?php echo create_widget_id(); ?>" data-name="<?php echo _l('program_not_scheduled'); ?>">
    <?php if(staff_can('view', 'governments') || staff_can('view_own', 'governments')) { ?>
    <div class="panel_s governments-expiring">
        <div class="panel-body padding-10">
            <p class="padding-5"><?php echo _l('program_not_scheduled'); ?></p>
            <hr class="hr-panel-heading-dashboard">
            <?php if (!empty($governments)) { ?>
                <div class="table-vertical-scroll">
                    <a href="<?php echo admin_url('governments'); ?>" class="mbot20 inline-block full-width"><?php echo _l('home_widget_view_all'); ?></a>
                    <table id="widget-<?php echo create_widget_id(); ?>" class="table dt-table" data-order-col="2" data-order-type="desc">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th class="<?php echo (isset($client) ? 'not_visible' : ''); ?>"><?php echo _l('government_list_program'); ?></th>
                                <th><?php echo _l('government_list_client'); ?></th>
                                <th><?php echo _l('government_list_date'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; ?>
                            <?php foreach ($governments as $government) { ?>
                                <tr>
                                    <td> <?php echo $i; ?>
                                    </td>
                                    <td>
                                        <?php //echo $government['name']; ?>
                                        <?php echo '<a href="' . admin_url("programs/view/" . $government["id"]) . '">' . $government['name'] . '</a>'; ?>
                                    </td>
                                    <td>
                                        <?php echo '<a href="' . admin_url("clients/client/" . $government["userid"]) . '">' . $government["company"] . '</a>'; ?>
                                    </td>
                                    <td>
                                        <?php echo _d($government['start_date']); ?>
                                    </td>
                                </tr>
                            <?php $i++; ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <div class="text-center padding-5">
                    <i class="fa fa-check fa-5x" aria-hidden="true"></i>
                    <h4><?php echo _l('no_program_not_scheduled',["7"]) ; ?> </h4>
                </div>
            <?php } ?>
        </div>
    </div>
    <?php } ?>
</div>
