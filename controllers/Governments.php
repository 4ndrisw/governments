<?php

use app\services\governments\GovernmentsPipeline;

defined('BASEPATH') or exit('No direct script access allowed');

class Governments extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('governments_model');
        $this->load->model('clients_model');
        $this->load->model('staff_model');
    }

    /* Get all governments in case user go on index page */
    public function index($id = '')
    {
        $this->list_governments($id);
    }

    /* List all governments datatables */
    public function list_governments($id = '')
    {
        if (!has_permission('governments', '', 'view') && !has_permission('governments', '', 'view_own') && get_option('allow_staff_view_governments_assigned') == '0') {
            access_denied('governments');
        }

        $isPipeline = $this->session->userdata('government_pipeline') == 'true';

        $data['government_states'] = $this->governments_model->get_states();
        if ($isPipeline && !$this->input->get('state') && !$this->input->get('filter')) {
            $data['title']           = _l('governments_pipeline');
            $data['bodyclass']       = 'governments-pipeline governments-total-manual';
            $data['switch_pipeline'] = false;

            if (is_numeric($id)) {
                $data['governmentid'] = $id;
            } else {
                $data['governmentid'] = $this->session->flashdata('governmentid');
            }

            $this->load->view('admin/governments/pipeline/manage', $data);
        } else {

            // Pipeline was initiated but user click from home page and need to show table only to filter
            if ($this->input->get('state') || $this->input->get('filter') && $isPipeline) {
                $this->pipeline(0, true);
            }
            
            $data['governmentid']            = $id;
            $data['switch_pipeline']       = true;
            $data['title']                 = _l('governments');
            $data['bodyclass']             = 'governments-total-manual';
            $data['governments_years']       = $this->governments_model->get_governments_years();
            //$data['governments_sale_agents'] = $this->governments_model->get_sale_agents();
            if($id){
                $this->load->view('admin/governments/manage_small_table', $data);

            }else{
                $this->load->view('admin/governments/manage_table', $data);

            }

        }
    }

    public function table($client_id = '')
    {
        if (!has_permission('governments', '', 'view') && !has_permission('governments', '', 'view_own') && get_option('allow_staff_view_governments_assigned') == '0') {
            ajax_access_denied();
        }
        $this->app->get_table_data(module_views_path('governments', 'admin/tables/table',[
            'client_id' => $client_id,
        ]));
    }

    /* Add new government or update existing */
    public function government($id = '')
    {
        if ($this->input->post()) {
            $government_data = $this->input->post();

            $save_and_send_later = false;
            if (isset($government_data['save_and_send_later'])) {
                unset($government_data['save_and_send_later']);
                $save_and_send_later = true;
            }

            if ($id == '') {
                if (!has_permission('governments', '', 'create')) {
                    access_denied('governments');
                }
                $government_data['is_government'] = '1';
                $next_government_number = get_option('next_government_number');
                $_format = get_option('government_number_format');
                $_prefix = get_option('government_prefix');
                
                $prefix  = isset($government->prefix) ? $government->prefix : $_prefix;
                $number_format  = isset($government->number_format) ? $government->number_format : $_format;
                $number  = isset($government->number) ? $government->number : $next_government_number;

                $government_data['prefix'] = $prefix;
                $government_data['number_format'] = $number_format;
                $date = date('Y-m-d');
                
                //$government_data['formatted_number'] = government_number_format($number, $format, $prefix, $date);
                //var_dump($government_data);
                //die();
                $id = $this->governments_model->add($government_data);

                if ($id) {
                    set_alert('success', _l('added_successfully', _l('government')));

                    $redUrl = admin_url('governments/#' . $id);

                    if ($save_and_send_later) {
                        $this->session->set_userdata('send_later', true);
                        // die(redirect($redUrl));
                    }

                    redirect(
                        !$this->set_government_pipeline_autoload($id) ? $redUrl : admin_url('governments/list_governments/')
                    );
                }
            } else {
                if (has_permission('governments', '', 'edit') || 
                   (has_permission('governments', '', 'edit_own') && is_staff_related_to_government($id))
                   ) {
                  
                    $success = $this->governments_model->update($government_data, $id);
                    if ($success) {
                        set_alert('success', _l('updated_successfully', _l('government')));
                    }
                    if ($this->set_government_pipeline_autoload($id)) {
                        redirect(admin_url('governments/list_governments/'));
                    } else {
                        redirect(admin_url('governments/#' . $id));
                    }
                }else{
                    access_denied('governments');
                }
            }
        }
        if ($id == '') {
            $title = _l('create_new_government');
        } else {
            $government = $this->governments_model->get($id);

            if (!$government || !user_can_view_government($id)) {
                blank_page(_l('government_not_found'));
            }
            $data['government'] = $government;
            $data['edit']     = true;
            $title            = _l('edit', _l('government_lowercase'));
        }

        $data['government_states'] = $this->governments_model->get_states();
        $data['title']             = $title;
        $this->load->view('admin/governments/government', $data);
    }
    
    public function clear_signature($id)
    {
        if (has_permission('governments', '', 'delete')) {
            $this->governments_model->clear_signature($id);
        }

        redirect(admin_url('governments/list_governments/' . $id));
    }

    public function update_number_settings($id)
    {
        $response = [
            'success' => false,
            'message' => '',
        ];
        if (has_permission('governments', '', 'edit')) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'governments', [
                'prefix' => $this->input->post('prefix'),
            ]);
            if ($this->db->affected_rows() > 0) {
                $response['success'] = true;
                $response['message'] = _l('updated_successfully', _l('government'));
            }
        }

        echo json_encode($response);
        die;
    }

    public function validate_government_number()
    {
        $isedit          = $this->input->post('isedit');
        $number          = $this->input->post('number');
        $date            = $this->input->post('date');
        $original_number = $this->input->post('original_number');
        $number          = trim($number);
        $number          = ltrim($number, '0');

        if ($isedit == 'true') {
            if ($number == $original_number) {
                echo json_encode(true);
                die;
            }
        }

        if (total_rows(db_prefix() . 'governments', [
            'YEAR(date)' => date('Y', strtotime(to_sql_date($date))),
            'number' => $number,
        ]) > 0) {
            echo 'false';
        } else {
            echo 'true';
        }
    }

    public function delete_attachment($id)
    {
        $file = $this->misc_model->get_file($id);
        if ($file->staffid == get_staff_user_id() || is_admin()) {
            echo $this->governments_model->delete_attachment($id);
        } else {
            header('HTTP/1.0 400 Bad error');
            echo _l('access_denied');
            die;
        }
    }

    /* Get all government data used when user click on government number in a datatable left side*/
    public function get_government_data_ajax($id, $to_return = false)
    {
        if (!has_permission('governments', '', 'view') && !has_permission('governments', '', 'view_own') && get_option('allow_staff_view_governments_assigned') == '0') {
            echo _l('access_denied');
            die;
        }

        if (!$id) {
            die('No government found');
        }

        $government = $this->governments_model->get($id);

        if (!$government || !user_can_view_government($id)) {
            echo _l('government_not_found');
            die;
        }

        // $data = prepare_mail_preview_data($template_name, $government->clientid);
        $data['title'] = 'Form add / Edit Staff';
        $data['activity']          = $this->governments_model->get_government_activity($id);
        $data['government']          = $government;
        $data['member']           = $this->staff_model->get('', ['active' => 1, 'client_id'=>$id]);
        $data['government_states'] = $this->governments_model->get_states();
        $data['totalNotes']        = total_rows(db_prefix() . 'notes', ['rel_id' => $id, 'rel_type' => 'government']);

        $data['send_later'] = false;
        if ($this->session->has_userdata('send_later')) {
            $data['send_later'] = true;
            $this->session->unset_userdata('send_later');
        }

        if ($to_return == false) {
            $this->load->view('admin/governments/government_preview_template', $data);
        } else {
            return $this->load->view('admin/governments/government_preview_template', $data, true);
        }
    }

    public function get_governments_total()
    {
        if ($this->input->post()) {
            $data['totals'] = $this->governments_model->get_governments_total($this->input->post());

            $this->load->model('currencies_model');

            if (!$this->input->post('customer_id')) {
                $multiple_currencies = call_user_func('is_using_multiple_currencies', db_prefix() . 'governments');
            } else {
                $multiple_currencies = call_user_func('is_client_using_multiple_currencies', $this->input->post('customer_id'), db_prefix() . 'governments');
            }

            if ($multiple_currencies) {
                $data['currencies'] = $this->currencies_model->get();
            }

            $data['governments_years'] = $this->governments_model->get_governments_years();

            if (
                count($data['governments_years']) >= 1
                && !\app\services\utilities\Arr::inMultidimensional($data['governments_years'], 'year', date('Y'))
            ) {
                array_unshift($data['governments_years'], ['year' => date('Y')]);
            }

            $data['_currency'] = $data['totals']['currencyid'];
            unset($data['totals']['currencyid']);
            $this->load->view('admin/governments/governments_total_template', $data);
        }
    }

    public function add_note($rel_id)
    {
        if ($this->input->post() && user_can_view_government($rel_id)) {
            $this->misc_model->add_note($this->input->post(), 'government', $rel_id);
            echo $rel_id;
        }
    }

    public function get_notes($id)
    {
        if (user_can_view_government($id)) {
            $data['notes'] = $this->misc_model->get_notes($id, 'government');
            $this->load->view('admin/includes/sales_notes_template', $data);
        }
    }

    public function mark_action_state($state, $id)
    {
        if (!has_permission('governments', '', 'edit') || !has_permission('governments', '', 'edit_own')) {
            access_denied('governments');
        }
        $success = $this->governments_model->mark_action_state($state, $id);
        if ($success) {
            set_alert('success', _l('government_state_changed_success'));
        } else {
            set_alert('danger', _l('government_state_changed_fail'));
        }
        if ($this->set_government_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('governments/list_governments/' . $id));
        }
    }

    public function send_expiry_reminder($id)
    {
        $canView = user_can_view_government($id);
        if (!$canView) {
            access_denied('Governments');
        } else {
            if (!has_permission('governments', '', 'view') && !has_permission('governments', '', 'view_own') && $canView == false) {
                access_denied('Governments');
            }
        }

        $success = $this->governments_model->send_expiry_reminder($id);
        if ($success) {
            set_alert('success', _l('sent_expiry_reminder_success'));
        } else {
            set_alert('danger', _l('sent_expiry_reminder_fail'));
        }
        if ($this->set_government_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('governments/list_governments/' . $id));
        }
    }

    /* Send government to email */
    public function send_to_email($id)
    {
        $canView = user_can_view_government($id);
        if (!$canView) {
            access_denied('governments');
        } else {
            if (!has_permission('governments', '', 'view') && !has_permission('governments', '', 'view_own') && $canView == false) {
                access_denied('governments');
            }
        }

        try {
            $success = $this->governments_model->send_government_to_client($id, '', $this->input->post('attach_pdf'), $this->input->post('cc'));
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        // In case client use another language
        load_admin_language();
        if ($success) {
            set_alert('success', _l('government_sent_to_client_success'));
        } else {
            set_alert('danger', _l('government_sent_to_client_fail'));
        }
        if ($this->set_government_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('governments/list_governments/' . $id));
        }
    }

    /* Convert government to invoice */
    public function convert_to_invoice($id)
    {
        if (!has_permission('invoices', '', 'create')) {
            access_denied('invoices');
        }
        if (!$id) {
            die('No government found');
        }
        $draft_invoice = false;
        if ($this->input->get('save_as_draft')) {
            $draft_invoice = true;
        }
        $invoiceid = $this->governments_model->convert_to_invoice($id, false, $draft_invoice);
        if ($invoiceid) {
            set_alert('success', _l('government_convert_to_invoice_successfully'));
            redirect(admin_url('invoices/list_invoices/' . $invoiceid));
        } else {
            if ($this->session->has_userdata('government_pipeline') && $this->session->userdata('government_pipeline') == 'true') {
                $this->session->set_flashdata('governmentid', $id);
            }
            if ($this->set_government_pipeline_autoload($id)) {
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                redirect(admin_url('governments/list_governments/' . $id));
            }
        }
    }

    public function copy($id)
    {
        if (!has_permission('governments', '', 'create')) {
            access_denied('governments');
        }
        if (!$id) {
            die('No government found');
        }
        $new_id = $this->governments_model->copy($id);
        if ($new_id) {
            set_alert('success', _l('government_copied_successfully'));
            if ($this->set_government_pipeline_autoload($new_id)) {
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                redirect(admin_url('governments/government/' . $new_id));
            }
        }
        set_alert('danger', _l('government_copied_fail'));
        if ($this->set_government_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('governments/government/' . $id));
        }
    }

    /* Delete government */
    public function delete($id)
    {
        if (!has_permission('governments', '', 'delete')) {
            access_denied('governments');
        }
        if (!$id) {
            redirect(admin_url('governments/list_governments'));
        }
        $success = $this->governments_model->delete($id);
        if (is_array($success)) {
            set_alert('warning', _l('is_invoiced_government_delete_error'));
        } elseif ($success == true) {
            set_alert('success', _l('deleted', _l('government')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('government_lowercase')));
        }
        redirect(admin_url('governments/list_governments'));
    }

    public function clear_acceptance_info($id)
    {
        if (is_admin()) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'governments', get_acceptance_info_array(true));
        }

        redirect(admin_url('governments/list_governments/' . $id));
    }

    /* Generates government PDF and senting to email  */
    public function pdf($id)
    {
        $canView = user_can_view_government($id);
        if (!$canView) {
            access_denied('Governments');
        } else {
            if (!has_permission('governments', '', 'view') && !has_permission('governments', '', 'view_own') && $canView == false) {
                access_denied('Governments');
            }
        }
        if (!$id) {
            redirect(admin_url('governments/list_governments'));
        }
        $government        = $this->governments_model->get($id);
        $government_number = format_government_number($government->id);

        try {
            $pdf = government_pdf($government);
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $fileNameHookData = hooks()->apply_filters('government_file_name_admin_area', [
                            'file_name' => mb_strtoupper(slug_it($government_number)) . '.pdf',
                            'government'  => $government,
                        ]);

        $pdf->Output($fileNameHookData['file_name'], $type);
    }

    // Pipeline
    public function get_pipeline()
    {
        if (has_permission('governments', '', 'view') || has_permission('governments', '', 'view_own') || get_option('allow_staff_view_governments_assigned') == '1') {
            $data['government_states'] = $this->governments_model->get_states();
            $this->load->view('admin/governments/pipeline/pipeline', $data);
        }
    }

    public function pipeline_open($id)
    {
        $canView = user_can_view_government($id);
        if (!$canView) {
            access_denied('Governments');
        } else {
            if (!has_permission('governments', '', 'view') && !has_permission('governments', '', 'view_own') && $canView == false) {
                access_denied('Governments');
            }
        }

        $data['userid']       = $id;
        $data['government'] = $this->get_government_data_ajax($id, true);
        $this->load->view('admin/governments/pipeline/government', $data);
    }

    public function update_pipeline()
    {
        if (has_permission('governments', '', 'edit') || has_permission('governments', '', 'edit_own')) {
            $this->governments_model->update_pipeline($this->input->post());
        }
    }

    public function pipeline($set = 0, $manual = false)
    {
        if ($set == 1) {
            $set = 'true';
        } else {
            $set = 'false';
        }
        $this->session->set_userdata([
            'government_pipeline' => $set,
        ]);
        if ($manual == false) {
            redirect(admin_url('governments/list_governments'));
        }
    }

    public function pipeline_load_more()
    {
        $state = $this->input->get('state');
        $page   = $this->input->get('page');

        $governments = (new GovernmentsPipeline($state))
            ->search($this->input->get('search'))
            ->sortBy(
                $this->input->get('sort_by'),
                $this->input->get('sort')
            )
            ->page($page)->get();

        foreach ($governments as $government) {
            $this->load->view('admin/governments/pipeline/_kanban_card', [
                'government' => $government,
                'state'   => $state,
            ]);
        }
    }

    public function set_government_pipeline_autoload($id)
    {
        if ($id == '') {
            return false;
        }

        if ($this->session->has_userdata('government_pipeline')
                && $this->session->userdata('government_pipeline') == 'true') {
            $this->session->set_flashdata('governmentid', $id);

            return true;
        }

        return false;
    }

    public function get_due_date()
    {
        if ($this->input->post()) {
            $date    = $this->input->post('date');
            $duedate = '';
            if (get_option('government_due_after') != 0) {
                $date    = to_sql_date($date);
                $d       = date('Y-m-d', strtotime('+' . get_option('government_due_after') . ' DAY', strtotime($date)));
                $duedate = _d($d);
                echo $duedate;
            }
        }
    }
/*
    public function get_staff($userid='')
    {
        $this->app->get_table_data(module_views_path('governments', 'admin/tables/staff'));
    }
*/
    public function table_staffs($client_id,$government = true)
    {
        if (
            !has_permission('governments', '', 'view')
            && !has_permission('governments', '', 'view_own')
            && get_option('allow_staff_view_governments_assigned') == 0
        ) {
            ajax_access_denied();
        }
        $this->app->get_table_data(module_views_path('governments', 'admin/tables/staff'), array('client_id'=>$client_id));
    }


}
