<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<table class="table dt-table table-governments" data-order-col="1" data-order-type="desc">
    <thead>
        <tr>
            <th><?php echo _l('government_number'); ?> #</th>
            <th><?php echo _l('government_list_program'); ?></th>
            <th><?php echo _l('government_list_date'); ?></th>
            <th><?php echo _l('government_list_state'); ?></th>

        </tr>
    </thead>
    <tbody>
        <?php foreach($governments as $government){ ?>
            <tr>
                <td><?php echo '<a href="' . site_url("governments/show/" . $government["id"] . '/' . $government["hash"]) . '">' . format_government_number($government["id"]) . '</a>'; ?></td>
                <td><?php echo $government['name']; ?></td>
                <td><?php echo _d($government['date']); ?></td>
                <td><?php echo format_government_state($government['state']); ?></td>
            </tr>
        <?php } ?>
    </tbody>
</table>
