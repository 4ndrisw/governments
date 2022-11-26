<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Governments
Description: Default module for defining governments
Version: 1.0.1
Requires at least: 2.3.*
*/

define('GOVERNMENTS_MODULE_NAME', 'governments');
define('GOVERNMENT_ATTACHMENTS_FOLDER', 'uploads/governments/');

hooks()->add_filter('before_government_updated', '_format_data_government_feature');
hooks()->add_filter('before_government_added', '_format_data_government_feature');

hooks()->add_action('after_cron_run', 'governments_notification');
hooks()->add_action('admin_init', 'governments_module_init_menu_items');
hooks()->add_action('admin_init', 'governments_permissions');
hooks()->add_action('admin_init', 'governments_settings_tab');
hooks()->add_action('clients_init', 'governments_clients_area_menu_items');
hooks()->add_filter('get_contact_permissions', 'governments_contact_permission',10,1);

hooks()->add_action('staff_member_deleted', 'governments_staff_member_deleted');

hooks()->add_filter('migration_tables_to_replace_old_links', 'governments_migration_tables_to_replace_old_links');
hooks()->add_filter('global_search_result_query', 'governments_global_search_result_query', 10, 3);
hooks()->add_filter('global_search_result_output', 'governments_global_search_result_output', 10, 2);
hooks()->add_filter('get_dashboard_widgets', 'governments_add_dashboard_widget');
hooks()->add_filter('module_governments_action_links', 'module_governments_action_links');


function governments_add_dashboard_widget($widgets)
{
    /*
    $widgets[] = [
        'path'      => 'governments/widgets/government_this_week',
        'container' => 'left-8',
    ];
    $widgets[] = [
        'path'      => 'governments/widgets/program_not_scheduled',
        'container' => 'left-8',
    ];
    */

    return $widgets;
}


function governments_staff_member_deleted($data)
{
    $CI = &get_instance();
    $CI->db->where('staff_id', $data['id']);
    $CI->db->update(db_prefix() . 'governments', [
            'staff_id' => $data['transfer_data_to'],
        ]);
}

function governments_global_search_result_output($output, $data)
{
    if ($data['type'] == 'governments') {
        $output = '<a href="' . admin_url('governments/government/' . $data['result']['id']) . '">' . format_government_number($data['result']['id']) . '</a>';
    }

    return $output;
}

function governments_global_search_result_query($result, $q, $limit)
{
    $CI = &get_instance();
    if (has_permission('governments', '', 'view')) {

        // governments
        $CI->db->select()
           ->from(db_prefix() . 'governments')
           ->like(db_prefix() . 'governments.formatted_number', $q)->limit($limit);
        
        $result[] = [
                'result'         => $CI->db->get()->result_array(),
                'type'           => 'governments',
                'search_heading' => _l('governments'),
            ];
        
        if(isset($result[0]['result'][0]['id'])){
            return $result;
        }

        // governments
        $CI->db->select()->from(db_prefix() . 'governments')->like(db_prefix() . 'clients.company', $q)->or_like(db_prefix() . 'governments.formatted_number', $q)->limit($limit);
        $CI->db->join(db_prefix() . 'clients',db_prefix() . 'governments.clientid='.db_prefix() .'clients.userid', 'left');
        $CI->db->order_by(db_prefix() . 'clients.company', 'ASC');

        $result[] = [
                'result'         => $CI->db->get()->result_array(),
                'type'           => 'governments',
                'search_heading' => _l('governments'),
            ];
    }

    return $result;
}

function governments_migration_tables_to_replace_old_links($tables)
{
    $tables[] = [
                'table' => db_prefix() . 'governments',
                'field' => 'description',
            ];

    return $tables;
}

function governments_contact_permission($permissions){
        $item = array(
            'id'         => 7,
            'name'       => _l('governments'),
            'short_name' => 'governments',
        );
        $permissions[] = $item;
      return $permissions;

}

function governments_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
            'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
            'create' => _l('permission_create'),
            'edit'   => _l('permission_edit'),
            'edit_own'   => _l('permission_edit_own'),
            'delete' => _l('permission_delete'),
    ];

    register_staff_capabilities('governments', $capabilities, _l('governments'));
}


/**
* Register activation module hook
*/
register_activation_hook(GOVERNMENTS_MODULE_NAME, 'governments_module_activation_hook');

function governments_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
* Register deactivation module hook
*/
register_deactivation_hook(GOVERNMENTS_MODULE_NAME, 'governments_module_deactivation_hook');

function governments_module_deactivation_hook()
{

     log_activity( 'Hello, world! . governments_module_deactivation_hook ' );
}

//hooks()->add_action('deactivate_' . $module . '_module', $function);

/**
* Register language files, must be registered if the module is using languages
*/
register_language_files(GOVERNMENTS_MODULE_NAME, [GOVERNMENTS_MODULE_NAME]);

/**
 * Init governments module menu items in setup in admin_init hook
 * @return null
 */
function governments_module_init_menu_items()
{
    $CI = &get_instance();

    $CI->app->add_quick_actions_link([
            'name'       => _l('government'),
            'url'        => 'governments',
            'permission' => 'governments',
            'position'   => 57,
            ]);

    if (has_permission('governments', '', 'delete')) {
        $CI->app_menu->add_sidebar_menu_item('governments', [
                'slug'     => 'governments-tracking',
                'name'     => _l('governments'),
                'icon'     => 'fa-solid fa-building-columns',
                'href'     => admin_url('governments'),
                'position' => 12,
        ]);
    }
}

function module_governments_action_links($actions)
{
    $actions[] = '<a href="' . admin_url('settings?group=governments') . '">' . _l('settings') . '</a>';

    return $actions;
}

function governments_clients_area_menu_items()
{
    // Show menu item only if client is logged in
    if (is_client_logged_in() && has_contact_permission('governments')) {
        add_theme_menu_item('governments', [
                    'name'     => _l('governments'),
                    'href'     => site_url('governments/list'),
                    'position' => 15,
                    'icon'     => 'fa-solid fa-building-columns',
        ]);
    }
}

/**
 * [perfex_dark_theme_settings_tab net menu item in setup->settings]
 * @return void
 */
function governments_settings_tab()
{
    $CI = &get_instance();
    $CI->app_tabs->add_settings_tab('governments', [
        'name'     => _l('settings_group_governments'),
        //'view'     => module_views_path(GOVERNMENTS_MODULE_NAME, 'admin/settings/includes/governments'),
        'view'     => 'governments/governments_settings',
        'position' => 51,
        'icon'     => 'fa-solid fa-building-columns',
    ]);
}

$CI = &get_instance();
$CI->load->helper(GOVERNMENTS_MODULE_NAME . '/governments');
if(($CI->uri->segment(1)=='admin' && $CI->uri->segment(2)=='governments') || $CI->uri->segment(1)=='governments'){
    $CI->app_css->add(GOVERNMENTS_MODULE_NAME.'-css', base_url('modules/'.GOVERNMENTS_MODULE_NAME.'/assets/css/'.GOVERNMENTS_MODULE_NAME.'.css'));
    $CI->app_scripts->add(GOVERNMENTS_MODULE_NAME.'-js', base_url('modules/'.GOVERNMENTS_MODULE_NAME.'/assets/js/'.GOVERNMENTS_MODULE_NAME.'.js'));
}

if(($CI->uri->segment(1)=='admin' && $CI->uri->segment(2)=='staff') && $CI->uri->segment(3)=='edit_provile'){
    $CI->app_css->add(GOVERNMENTS_MODULE_NAME.'-css', base_url('modules/'.GOVERNMENTS_MODULE_NAME.'/assets/css/'.GOVERNMENTS_MODULE_NAME.'.css'));
}

