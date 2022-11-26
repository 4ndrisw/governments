<?php defined('BASEPATH') or exit('No direct script access allowed');

class Mygovernment extends ClientsController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('governments_model');
        $this->load->model('clients_model');
    }

    /* Get all governments in case user go on index page */
    public function list($id = '')
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('governments', 'admin/tables/table'));
        }
        $contact_id = get_contact_user_id();
        $user_id = get_user_id_by_contact_id($contact_id);
        $client = $this->clients_model->get($user_id);
        $data['governments'] = $this->governments_model->get_client_governments($client);
        $data['governmentid']            = $id;
        $data['title']                 = _l('governments_tracking');

        $data['bodyclass'] = 'governments';
        $this->data($data);
        $this->view('themes/'. active_clients_theme() .'/views/governments/governments');
        $this->layout();
    }

    public function show($id, $hash)
    {
        check_government_restrictions($id, $hash);
        $government = $this->governments_model->get($id);

        if (!is_client_logged_in()) {
            load_client_language($government->clientid);
        }

        $identity_confirmation_enabled = get_option('government_accept_identity_confirmation');

        if ($this->input->post('government_action')) {
            $action = $this->input->post('government_action');

            // Only decline and accept allowed
            if ($action == 4 || $action == 3) {
                $success = $this->governments_model->mark_action_state($action, $id, true);

                $redURL   = $this->uri->uri_string();
                $accepted = false;

                if (is_array($success)) {
                    if ($action == 4) {
                        $accepted = true;
                        set_alert('success', _l('clients_government_accepted_not_invoiced'));
                    } else {
                        set_alert('success', _l('clients_government_declined'));
                    }
                } else {
                    set_alert('warning', _l('clients_government_failed_action'));
                }
                if ($action == 4 && $accepted = true) {
                    process_digital_signature_image($this->input->post('signature', false), GOVEDULE_ATTACHMENTS_FOLDER . $id);

                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'governments', get_acceptance_info_array());
                }
            }
            redirect($redURL);
        }
        // Handle Government PDF generator

        $government_number = format_government_number($government->id);
        /*
        if ($this->input->post('governmentpdf')) {
            try {
                $pdf = government_pdf($government);
            } catch (Exception $e) {
                echo $e->getMessage();
                die;
            }

            //$government_number = format_government_number($government->id);
            $companyname     = get_option('company_name');
            if ($companyname != '') {
                $government_number .= '-' . mb_strtoupper(slug_it($companyname), 'UTF-8');
            }

            $filename = hooks()->apply_filters('customers_area_download_government_filename', mb_strtoupper(slug_it($government_number), 'UTF-8') . '.pdf', $government);

            $pdf->Output($filename, 'D');
            die();
        }
        */

        $data['title'] = $government_number;
        $this->disableNavigation();
        $this->disableSubMenu();

        $data['government_number']              = $government_number;
        $data['hash']                          = $hash;
        $data['can_be_accepted']               = false;
        $data['government']                     = hooks()->apply_filters('government_html_pdf_data', $government);
        $data['bodyclass']                     = 'viewgovernment';
        $data['client_company']                = $this->clients_model->get($government->clientid)->company;
        $setSize = get_option('government_qrcode_size');

        $data['identity_confirmation_enabled'] = $identity_confirmation_enabled;
        if ($identity_confirmation_enabled == '1') {
            $data['bodyclass'] .= ' identity-confirmation';
        }
        $data['government_members']  = $this->governments_model->get_government_members($government->id,true);

        $qrcode_data  = '';
        $qrcode_data .= _l('government_number') . ' : ' . $government_number ."\r\n";
        $qrcode_data .= _l('government_date') . ' : ' . $government->date ."\r\n";
        $qrcode_data .= _l('government_datesend') . ' : ' . $government->datesend ."\r\n";
        //$qrcode_data .= _l('government_assigned_string') . ' : ' . get_staff_full_name($government->assigned) ."\r\n";
        //$qrcode_data .= _l('government_url') . ' : ' . site_url('governments/show/'. $government->id .'/'.$government->hash) ."\r\n";


        $government_path = get_upload_path_by_type('governments') . $government->id . '/';
        _maybe_create_upload_path('uploads/governments');
        _maybe_create_upload_path('uploads/governments/'.$government_path);

        $params['data'] = $qrcode_data;
        $params['writer'] = 'png';
        $params['setSize'] = isset($setSize) ? $setSize : 160;
        $params['encoding'] = 'UTF-8';
        $params['setMargin'] = 0;
        $params['setForegroundColor'] = ['r'=>0,'g'=>0,'b'=>0];
        $params['setBackgroundColor'] = ['r'=>255,'g'=>255,'b'=>255];

        $params['crateLogo'] = true;
        $params['logo'] = './uploads/company/favicon.png';
        $params['setResizeToWidth'] = 60;

        $params['crateLabel'] = false;
        $params['label'] = $government_number;
        $params['setTextColor'] = ['r'=>255,'g'=>0,'b'=>0];
        $params['ErrorCorrectionLevel'] = 'hight';

        $params['saveToFile'] = FCPATH.'uploads/governments/'.$government_path .'assigned-'.$government_number.'.'.$params['writer'];

        $this->load->library('endroid_qrcode');
        $this->endroid_qrcode->generate($params);

        $this->data($data);
        $this->app_scripts->theme('sticky-js', 'assets/plugins/sticky/sticky.js');
        $this->view('themes/'. active_clients_theme() .'/views/governments/governmenthtml');
        add_views_tracking('government', $id);
        hooks()->do_action('government_html_viewed', $id);
        no_index_customers_area();
        $this->layout();
    }


    public function office($id, $hash)
    {
        check_government_restrictions($id, $hash);
        $government = $this->governments_model->get($id);

        if (!is_client_logged_in()) {
            load_client_language($government->clientid);
        }

        $identity_confirmation_enabled = get_option('government_accept_identity_confirmation');

        if ($this->input->post('government_action')) {
            $action = $this->input->post('government_action');

            // Only decline and accept allowed
            if ($action == 4 || $action == 3) {
                $success = $this->governments_model->mark_action_state($action, $id, true);

                $redURL   = $this->uri->uri_string();
                $accepted = false;

                if (is_array($success)) {
                    if ($action == 4) {
                        $accepted = true;
                        set_alert('success', _l('clients_government_accepted_not_invoiced'));
                    } else {
                        set_alert('success', _l('clients_government_declined'));
                    }
                } else {
                    set_alert('warning', _l('clients_government_failed_action'));
                }
                if ($action == 4 && $accepted = true) {
                    process_digital_signature_image($this->input->post('signature', false), GOVEDULE_ATTACHMENTS_FOLDER . $id);

                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'governments', get_acceptance_info_array());
                }
            }
            redirect($redURL);
        }
        // Handle Government PDF generator

        $government_number = format_government_number($government->id);
        /*
        if ($this->input->post('governmentpdf')) {
            try {
                $pdf = government_pdf($government);
            } catch (Exception $e) {
                echo $e->getMessage();
                die;
            }

            //$government_number = format_government_number($government->id);
            $companyname     = get_option('company_name');
            if ($companyname != '') {
                $government_number .= '-' . mb_strtoupper(slug_it($companyname), 'UTF-8');
            }

            $filename = hooks()->apply_filters('customers_area_download_government_filename', mb_strtoupper(slug_it($government_number), 'UTF-8') . '.pdf', $government);

            $pdf->Output($filename, 'D');
            die();
        }
        */

        $data['title'] = $government_number;
        $this->disableNavigation();
        $this->disableSubMenu();

        $data['government_number']              = $government_number;
        $data['hash']                          = $hash;
        $data['can_be_accepted']               = false;
        $data['government']                     = hooks()->apply_filters('government_html_pdf_data', $government);
        $data['bodyclass']                     = 'viewgovernment';
        $data['client_company']                = $this->clients_model->get($government->clientid)->company;
        $setSize = get_option('government_qrcode_size');

        $data['identity_confirmation_enabled'] = $identity_confirmation_enabled;
        if ($identity_confirmation_enabled == '1') {
            $data['bodyclass'] .= ' identity-confirmation';
        }
        $data['government_members']  = $this->governments_model->get_government_members($government->id,true);

        $qrcode_data  = '';
        $qrcode_data .= _l('government_number') . ' : ' . $government_number ."\r\n";
        $qrcode_data .= _l('government_date') . ' : ' . $government->date ."\r\n";
        $qrcode_data .= _l('government_datesend') . ' : ' . $government->datesend ."\r\n";
        //$qrcode_data .= _l('government_assigned_string') . ' : ' . get_staff_full_name($government->assigned) ."\r\n";
        //$qrcode_data .= _l('government_url') . ' : ' . site_url('governments/show/'. $government->id .'/'.$government->hash) ."\r\n";


        $government_path = get_upload_path_by_type('governments') . $government->id . '/';
        _maybe_create_upload_path('uploads/governments');
        _maybe_create_upload_path('uploads/governments/'.$government_path);

        $params['data'] = $qrcode_data;
        $params['writer'] = 'png';
        $params['setSize'] = isset($setSize) ? $setSize : 160;
        $params['encoding'] = 'UTF-8';
        $params['setMargin'] = 0;
        $params['setForegroundColor'] = ['r'=>0,'g'=>0,'b'=>0];
        $params['setBackgroundColor'] = ['r'=>255,'g'=>255,'b'=>255];

        $params['crateLogo'] = true;
        $params['logo'] = './uploads/company/favicon.png';
        $params['setResizeToWidth'] = 60;

        $params['crateLabel'] = false;
        $params['label'] = $government_number;
        $params['setTextColor'] = ['r'=>255,'g'=>0,'b'=>0];
        $params['ErrorCorrectionLevel'] = 'hight';

        $params['saveToFile'] = FCPATH.'uploads/governments/'.$government_path .'assigned-'.$government_number.'.'.$params['writer'];

        $this->load->library('endroid_qrcode');
        $this->endroid_qrcode->generate($params);

        $this->data($data);
        $this->app_scripts->theme('sticky-js', 'assets/plugins/sticky/sticky.js');
        $this->view('themes/'. active_clients_theme() .'/views/governments/government_office_html');
        add_views_tracking('government', $id);
        hooks()->do_action('government_html_viewed', $id);
        no_index_customers_area();
        $this->layout();
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
            redirect(admin_url('governments'));
        }
        $government        = $this->governments_model->get($id);
        $government_number = format_government_number($government->id);
        
        $government->assigned_path = FCPATH . get_government_upload_path('government').$government->id.'/assigned-'.$government_number.'.png';
        $government->acceptance_path = FCPATH . get_government_upload_path('government').$government->id .'/'.$government->signature;
        
        $government->client_company = $this->clients_model->get($government->clientid)->company;
        $government->acceptance_date_string = _dt($government->acceptance_date);


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

    /* Generates government PDF and senting to email  */
    public function office_pdf($id)
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
            redirect(admin_url('governments'));
        }
        $government        = $this->governments_model->get($id);
        $government_number = format_government_number($government->id);
        
        $government->assigned_path = FCPATH . get_government_upload_path('government').$government->id.'/assigned-'.$government_number.'.png';
        $government->acceptance_path = FCPATH . get_government_upload_path('government').$government->id .'/'.$government->signature;
        
        $government->client_company = $this->clients_model->get($government->clientid)->company;
        $government->acceptance_date_string = _dt($government->acceptance_date);


        try {
            $pdf = government_office_pdf($government);
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
                            'file_name' => str_replace("GOV", "GOV-UPT", mb_strtoupper(slug_it($government_number)) . '.pdf'),
                            'government'  => $government,
                        ]);

        $pdf->Output($fileNameHookData['file_name'], $type);
    }
}
