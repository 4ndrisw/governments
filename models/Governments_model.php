<?php

use app\services\utilities\Arr;
use app\services\AbstractKanban;
use app\services\governments\GovernmentsPipeline;

defined('BASEPATH') or exit('No direct script access allowed');

//class Governments_model extends App_Model
class Governments_model extends Clients_Model
{
    private $states;
    private $contact_columns;

    private $shipping_fields = ['shipping_street', 'shipping_city', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country'];

    public function __construct()
    {
        parent::__construct();

        $this->states = hooks()->apply_filters('before_set_government_states', [
            3,
            4,
        ]);

        $this->load->model('clients_model');
        $this->contact_columns = hooks()->apply_filters('contact_columns', ['firstname', 'lastname', 'email', 'phonenumber', 'title', 'password', 'send_set_password_email', 'donotsendwelcomeemail', 'permissions', 'direction', 'invoice_emails', 'estimate_emails', 'credit_note_emails', 'contract_emails', 'task_emails', 'program_emails', 'ticket_emails', 'is_primary']);

        $this->load->model(['client_vault_entries_model', 'client_groups_model', 'statement_model']);
    }

    private function check_zero_columns($data)
    {
        if (!isset($data['show_primary_contact'])) {
            $data['show_primary_contact'] = 0;
        }

        if (isset($data['default_currency']) && $data['default_currency'] == '' || !isset($data['default_currency'])) {
            $data['default_currency'] = 0;
        }

        if (isset($data['country']) && $data['country'] == '' || !isset($data['country'])) {
            $data['country'] = 0;
        }

        if (isset($data['billing_country']) && $data['billing_country'] == '' || !isset($data['billing_country'])) {
            $data['billing_country'] = 0;
        }

        if (isset($data['shipping_country']) && $data['shipping_country'] == '' || !isset($data['shipping_country'])) {
            $data['shipping_country'] = 0;
        }

        return $data;
    }

    /**
     * Get unique sale agent for governments / Used for filters
     * @return array
     
    public function get_sale_agents()
    {
        return $this->db->query("SELECT DISTINCT(sale_agent) as sale_agent, CONCAT(firstname, ' ', lastname) as full_name FROM " . db_prefix() . 'governments JOIN ' . db_prefix() . 'staff on ' . db_prefix() . 'staff.staffid=' . db_prefix() . 'governments.sale_agent WHERE sale_agent != 0')->result_array();
    }
    */

    /**
     * Get client object based on passed clientid if not passed clientid return array of all clients
     * @param  mixed $id    client id
     * @param  array  $where
     * @return mixed
     */
    public function get($id = '', $where = [])
    {
        $this->db->select('*,'. db_prefix() . 'clients.userid as userid,');

        $this->db->join(db_prefix() . 'countries', '' . db_prefix() . 'countries.country_id = ' . db_prefix() . 'clients.country', 'left');
        $this->db->join(db_prefix() . 'contacts', '' . db_prefix() . 'contacts.userid = ' . db_prefix() . 'clients.userid AND is_primary = 1', 'left');

        if ((is_array($where) && count($where) > 0) || (is_string($where) && $where != '')) {
            $this->db->where($where);
        }

        if (is_numeric($id)) {

            $this->db->where(db_prefix() . 'clients.userid', $id);
            $client = $this->db->get(db_prefix() . 'clients')->row();

            if ($client && get_option('company_requires_vat_number_field') == 0) {
                $client->vat = null;
            }

            $this->load->model('email_schedule_model');
            $client->scheduled_email = $this->email_schedule_model->get($id, 'government');

            $GLOBALS['client'] = $client;

            return $client;
        }

        $this->db->order_by('company', 'asc');
        $result = $this->db->get(db_prefix() . 'clients')->result_array();
        return $result;
    }

    /**
     * Get government states
     * @return array
     */
    public function get_states()
    {
        return $this->states;
    }

    public function clear_signature($id)
    {
        $this->db->select('signature');
        $this->db->where('id', $id);
        $government = $this->db->get(db_prefix() . 'governments')->row();

        if ($government) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'governments', ['signature' => null]);

            if (!empty($government->signature)) {
                unlink(get_upload_path_by_type('government') . $id . '/' . $government->signature);
            }

            return true;
        }

        return false;
    }

    /**
     * Copy government
     * @param mixed $id government id to copy
     * @return mixed
     */
    public function copy($id)
    {
        $_government                       = $this->get($id);
        $new_government_data               = [];
        $new_government_data['clientid']   = $_government->clientid;
        $new_government_data['program_id'] = $_government->program_id;
        $new_government_data['number']     = get_option('next_government_number');
        $new_government_data['date']       = _d(date('Y-m-d'));
        $new_government_data['duedate'] = null;

        if ($_government->duedate && get_option('government_due_after') != 0) {
            $new_government_data['duedate'] = _d(date('Y-m-d', strtotime('+' . get_option('government_due_after') . ' DAY', strtotime(date('Y-m-d')))));
        }

        $new_government_data['show_quantity_as'] = $_government->show_quantity_as;
        $new_government_data['currency']         = $_government->currency;
        $new_government_data['subtotal']         = $_government->subtotal;
        $new_government_data['total']            = $_government->total;
        $new_government_data['adminnote']        = $_government->adminnote;
        $new_government_data['adjustment']       = $_government->adjustment;
        $new_government_data['discount_percent'] = $_government->discount_percent;
        $new_government_data['discount_total']   = $_government->discount_total;
        $new_government_data['discount_type']    = $_government->discount_type;
        $new_government_data['terms']            = $_government->terms;
        $new_government_data['sale_agent']       = $_government->sale_agent;
        $new_government_data['reference_no']     = $_government->reference_no;
        // Since version 1.0.6
        $new_government_data['billing_street']   = clear_textarea_breaks($_government->billing_street);
        $new_government_data['billing_city']     = $_government->billing_city;
        $new_government_data['billing_state']    = $_government->billing_state;
        $new_government_data['billing_zip']      = $_government->billing_zip;
        $new_government_data['billing_country']  = $_government->billing_country;
        $new_government_data['shipping_street']  = clear_textarea_breaks($_government->shipping_street);
        $new_government_data['shipping_city']    = $_government->shipping_city;
        $new_government_data['shipping_state']   = $_government->shipping_state;
        $new_government_data['shipping_zip']     = $_government->shipping_zip;
        $new_government_data['shipping_country'] = $_government->shipping_country;
        if ($_government->include_shipping == 1) {
            $new_government_data['include_shipping'] = $_government->include_shipping;
        }
        $new_government_data['show_shipping_on_government'] = $_government->show_shipping_on_government;
        // Set to unpaid state automatically
        $new_government_data['state']     = 1;
        $new_government_data['clientnote'] = $_government->clientnote;
        $new_government_data['adminnote']  = '';
        $new_government_data['newitems']   = [];
        $custom_fields_items             = get_custom_fields('items');
        $key                             = 1;
        foreach ($_government->items as $item) {
            $new_government_data['newitems'][$key]['description']      = $item['description'];
            $new_government_data['newitems'][$key]['long_description'] = clear_textarea_breaks($item['long_description']);
            $new_government_data['newitems'][$key]['qty']              = $item['qty'];
            $new_government_data['newitems'][$key]['unit']             = $item['unit'];
            $new_government_data['newitems'][$key]['taxname']          = [];
            $taxes                                                   = get_government_item_taxes($item['id']);
            foreach ($taxes as $tax) {
                // tax name is in format TAX1|10.00
                array_push($new_government_data['newitems'][$key]['taxname'], $tax['taxname']);
            }
            $new_government_data['newitems'][$key]['rate']  = $item['rate'];
            $new_government_data['newitems'][$key]['order'] = $item['item_order'];
            foreach ($custom_fields_items as $cf) {
                $new_government_data['newitems'][$key]['custom_fields']['items'][$cf['id']] = get_custom_field_value($item['id'], $cf['id'], 'items', false);

                if (!defined('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST')) {
                    define('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST', true);
                }
            }
            $key++;
        }
        $id = $this->add($new_government_data);
        if ($id) {
            $custom_fields = get_custom_fields('government');
            foreach ($custom_fields as $field) {
                $value = get_custom_field_value($_government->id, $field['id'], 'government', false);
                if ($value == '') {
                    continue;
                }

                $this->db->insert(db_prefix() . 'customfieldsvalues', [
                    'relid'   => $id,
                    'fieldid' => $field['id'],
                    'fieldto' => 'government',
                    'value'   => $value,
                ]);
            }

            $tags = get_tags_in($_government->id, 'government');
            handle_tags_save($tags, $id, 'government');

            log_activity('Copied government ' . format_government_number($_government->id));

            return $id;
        }

        return false;
    }

    /**
     * Performs governments totals state
     * @param array $data
     * @return array
     */
    public function get_governments_total($data)
    {
        $states            = $this->get_states();
        $has_permission_view = has_permission('governments', '', 'view');
        $this->load->model('currencies_model');

        $sql = 'SELECT';
        foreach ($states as $government_state) {
            $sql .= '(SELECT SUM(total) FROM ' . db_prefix() . 'governments WHERE state=' . $government_state;
            //$sql .= ' AND currency =' . $this->db->escape_str($currencyid);
            if (isset($data['years']) && count($data['years']) > 0) {
                $sql .= ' AND YEAR(date) IN (' . implode(', ', array_map(function ($year) {
                    return get_instance()->db->escape_str($year);
                }, $data['years'])) . ')';
            } else {
                $sql .= ' AND YEAR(date) = ' . date('Y');
            }
            $sql .= $where;
            $sql .= ') as "' . $government_state . '",';
        }

        $sql     = substr($sql, 0, -1);
        $result  = $this->db->query($sql)->result_array();
        $_result = [];
        $i       = 1;
        foreach ($result as $key => $val) {
            foreach ($val as $state => $total) {
                $_result[$i]['total']         = $total;
                $_result[$i]['symbol']        = $currency->symbol;
                $_result[$i]['currency_name'] = $currency->name;
                $_result[$i]['state']        = $state;
                $i++;
            }
        }
        $_result['currencyid'] = $currencyid;

        return $_result;
    }

    /**
     * @param array $_POST data
     * @param client_request is this request from the customer area
     * @return integer Insert ID
     * Add new client to database
     */
    public function add($data, $client_or_lead_convert_request = false)
    {
        $contact_data = [];

        foreach ($this->contact_columns as $field) {
            if (isset($data[$field])) {
                $contact_data[$field] = $data[$field];
                // Phonenumber is also used for the company profile
                if ($field != 'phonenumber') {
                    unset($data[$field]);
                }
            }
        }

        if (isset($data['groups_in'])) {
            $groups_in = $data['groups_in'];
            unset($data['groups_in']);
        }

        $data['datecreated'] = date('Y-m-d H:i:s');
        $data['hash'] = app_generate_hash();

        if (is_staff_logged_in()) {
            $data['addedfrom'] = get_staff_user_id();
        }

        // New filter action
        $data = hooks()->apply_filters('before_government_added', $data);

        //trigger exception in a "try" block
        try {
            $company_name_exist = $this->check_government_name_exist($data['company']);
            if($company_name_exist){
                return;
            }
            $this->db->insert(db_prefix() . 'clients', $data);
        }

        //catch exception
        catch(Exception $e) {
          echo 'Message: ' .$e->getMessage();
        }


        $userid = $this->db->insert_id();
        if ($userid) {
            // Update next government number in settings
            $this->db->where('name', 'next_government_number');
            $this->db->set('value', 'value+1', false);
            $this->db->update(db_prefix() . 'options');

            $log = 'ID: ' . $userid;

            if ($log == '' && isset($contact_id)) {
                $log = get_contact_full_name($contact_id);
            }

            $isStaff = null;
            if (!is_client_logged_in() && is_staff_logged_in()) {
                $log .= ', From Staff: ' . get_staff_user_id();
                $isStaff = get_staff_user_id();
            }
            $government = $this->get($userid);
            if ($government->assigned != 0) {
                if ($government->assigned != get_staff_user_id()) {
                    $notified = add_notification([
                        'description'     => 'not_government_already_created',
                        'touserid'        => get_staff_user_id(),
                        'fromuserid'      => get_staff_user_id(),
                        'link'            => 'government/list_government/' . $insert_id .'#' . $insert_id,
                        'additional_data' => serialize([
                            $government->subject,
                        ]),
                    ]);
                    if ($notified) {
                        pusher_trigger_notification([get_staff_user_id()]);
                    }
                }
            }
            hooks()->do_action('after_government_added', $userid);

            log_activity('New government Created [' . $log . ']', $isStaff);
        }

        return $userid;
    }

    /**
     * Get government surveyors id
     * @param mixed $id item id
     * @return object
     */

    public function get_government_surveyors($id ='')
    {
        if($id){
            $this->db->where('surveyor_id', $id);
        }

        return $this->db->get(db_prefix() . 'government_surveyors')->row();
    }

    public function get_government_companies($id ='')
    {
        if($id){
            $this->db->where('company_id', $id);
        }

        return $this->db->get(db_prefix() . 'government_companies')->row();
    }

    public function check_government_name_exist($company){
        $this->db->select('company');
        $this->db->where('company', $company);
        $result = $this->db->get(db_prefix(). 'clients')->num_rows();
        if($result>0){
            return TRUE;
        }
        return FALSE;
    }

    /**
     * @param  array $_POST data
     * @param  integer ID
     * @return boolean
     * Update client informations
     */

    public function update($data, $id, $client_request = false)
    {
        $updated = false;
        $data    = $this->check_zero_columns($data);
        $origin = $this->get($id);

        $data = hooks()->apply_filters('before_client_updated', $data, $id);

        $groups_in                     = Arr::pull($data, 'groups_in') ?? false;

        //trigger exception in a "try" block
        try {
            $company_name_exist = $this->check_government_name_exist($data['company']);
            if($company_name_exist && ($origin->company!=$data['company'])){
                return;
            }
            $this->db->where('userid', $id);
            $this->db->update(db_prefix() . 'clients', $data);
        }

        //catch exception
        catch(Exception $e) {
          echo 'Message: ' .$e->getMessage();
        }


        if ($this->db->affected_rows() > 0) {
            $updated = true;
            $government = $this->get($id);

            $fields = array('company', 'vat','siup', 'bpjs_kesehatan', 'bpjs_ketenagakerjaan', 'phonenumber');
            $custom_data = '';
            foreach ($fields as $field) {
                if ($origin->$field != $government->$field) {
                    $custom_data .= str_replace('_', ' ', $field) .' '. $origin->$field . ' to ' .$government->$field .'<br />';
                }
            }
            $this->log_government_activity($origin->userid, 'government_activity_changed', false, serialize([
                '<custom_data>'. $custom_data .'</custom_data>',
            ]));
        }

        if ($this->client_groups_model->sync_customer_groups($id, $groups_in)) {
            $updated = true;
        }

        hooks()->do_action('client_updated', [
            'id'                            => $id,
            'data'                          => $data,
            'update_all_other_transactions' => $update_all_other_transactions,
            'groups_in'                     => $groups_in,
            'updated'                       => &$updated,
        ]);

        if ($updated) {
            log_activity('Customer Info Updated [ID: ' . $id . ']');
        }

        return $government;
    }

    public function mark_action_state($action, $id, $client = false)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'governments', [
            'state' => $action,
        ]);

        $notifiedUsers = [];

        if ($this->db->affected_rows() > 0) {
            $government = $this->get($id);
            if ($client == true) {
                $this->db->where('staffid', $government->addedfrom);
                $this->db->or_where('staffid', $government->sale_agent);
                $staff_government = $this->db->get(db_prefix() . 'staff')->result_array();

                $invoiceid = false;
                $invoiced  = false;

                $contact_id = !is_client_logged_in()
                    ? get_primary_contact_user_id($government->clientid)
                    : get_contact_user_id();

                if ($action == 4) {
                    if (get_option('government_auto_convert_to_invoice_on_client_accept') == 1) {
                        $invoiceid = $this->convert_to_invoice($id, true);
                        $this->load->model('invoices_model');
                        if ($invoiceid) {
                            $invoiced = true;
                            $invoice  = $this->invoices_model->get($invoiceid);
                            $this->log_government_activity($id, 'government_activity_client_accepted_and_converted', true, serialize([
                                '<a href="' . admin_url('invoices/list_invoices/' . $invoiceid) . '">' . format_invoice_number($invoice->id) . '</a>',
                            ]));
                        }
                    } else {
                        $this->log_government_activity($id, 'government_activity_client_accepted', true);
                    }

                    // Send thank you email to all contacts with permission governments
                    $contacts = $this->clients_model->get_contacts($government->clientid, ['active' => 1, 'government_emails' => 1]);

                    foreach ($contacts as $contact) {
                        send_mail_template('government_accepted_to_customer', $government, $contact);
                    }

                    foreach ($staff_government as $member) {
                        $notified = add_notification([
                            'fromcompany'     => true,
                            'touserid'        => $member['staffid'],
                            'description'     => 'not_government_customer_accepted',
                            'link'            => 'governments/list_governments/' . $id,
                            'additional_data' => serialize([
                                format_government_number($government->id),
                            ]),
                        ]);

                        if ($notified) {
                            array_push($notifiedUsers, $member['staffid']);
                        }

                        send_mail_template('government_accepted_to_staff', $government, $member['email'], $contact_id);
                    }

                    pusher_trigger_notification($notifiedUsers);
                    hooks()->do_action('government_accepted', $id);

                    return [
                        'invoiced'  => $invoiced,
                        'invoiceid' => $invoiceid,
                    ];
                } elseif ($action == 3) {
                    foreach ($staff_government as $member) {
                        $notified = add_notification([
                            'fromcompany'     => true,
                            'touserid'        => $member['staffid'],
                            'description'     => 'not_government_customer_declined',
                            'link'            => 'governments/list_governments/' . $id,
                            'additional_data' => serialize([
                                format_government_number($government->id),
                            ]),
                        ]);

                        if ($notified) {
                            array_push($notifiedUsers, $member['staffid']);
                        }
                        // Send staff email notification that customer declined government
                        send_mail_template('government_declined_to_staff', $government, $member['email'], $contact_id);
                    }

                    pusher_trigger_notification($notifiedUsers);
                    $this->log_government_activity($id, 'government_activity_client_declined', true);
                    hooks()->do_action('government_declined', $id);

                    return [
                        'invoiced'  => $invoiced,
                        'invoiceid' => $invoiceid,
                    ];
                }
            } else {
                if ($action == 2) {
                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'governments', ['sent' => 1, 'datesend' => date('Y-m-d H:i:s')]);
                }
                // Admin marked government
                $this->log_government_activity($id, 'government_activity_marked', false, serialize([
                    '<state>' . $action . '</state>',
                ]));

                return true;
            }
        }

        return false;
    }

    /**
     * Get government attachments
     * @param mixed $government_id
     * @param string $id attachment id
     * @return mixed
     */
    public function get_attachments($government_id, $id = '')
    {
        // If is passed id get return only 1 attachment
        if (is_numeric($id)) {
            $this->db->where('id', $id);
        } else {
            $this->db->where('rel_id', $government_id);
        }
        $this->db->where('rel_type', 'government');
        $result = $this->db->get(db_prefix() . 'files');
        if (is_numeric($id)) {
            return $result->row();
        }

        return $result->result_array();
    }

    /**
     *  Delete government attachment
     * @param mixed $id attachmentid
     * @return  boolean
     */
    public function delete_attachment($id)
    {
        $attachment = $this->get_attachments('', $id);
        $deleted    = false;
        if ($attachment) {
            if (empty($attachment->external)) {
                unlink(get_upload_path_by_type('government') . $attachment->rel_id . '/' . $attachment->file_name);
            }
            $this->db->where('id', $attachment->id);
            $this->db->delete(db_prefix() . 'files');
            if ($this->db->affected_rows() > 0) {
                $deleted = true;
                log_activity('government Attachment Deleted [governmentID: ' . $attachment->rel_id . ']');
            }

            if (is_dir(get_upload_path_by_type('government') . $attachment->rel_id)) {
                // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files(get_upload_path_by_type('government') . $attachment->rel_id);
                if (count($other_attachments) == 0) {
                    // okey only index.html so we can delete the folder also
                    delete_dir(get_upload_path_by_type('government') . $attachment->rel_id);
                }
            }
        }

        return $deleted;
    }

    /**
     * Delete government items and all connections
     * @param mixed $id governmentid
     * @return boolean
     */
    public function delete($id, $simpleDelete = false)
    {
        if (get_option('delete_only_on_last_government') == 1 && $simpleDelete == false) {
            if (!is_last_government($id)) {
                return false;
            }
        }
        $government = $this->get($id);
        if (!is_null($government->invoiceid) && $simpleDelete == false) {
            return [
                'is_invoiced_government_delete_error' => true,
            ];
        }
        hooks()->do_action('before_government_deleted', $id);

        $number = format_government_number($id);

        $this->clear_signature($id);

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'governments');

        if ($this->db->affected_rows() > 0) {
            if (!is_null($government->short_link)) {
                app_archive_short_link($government->short_link);
            }

            if (get_option('government_number_decrement_on_delete') == 1 && $simpleDelete == false) {
                $current_next_government_number = get_option('next_government_number');
                if ($current_next_government_number > 1) {
                    // Decrement next government number to
                    $this->db->where('name', 'next_government_number');
                    $this->db->set('value', 'value-1', false);
                    $this->db->update(db_prefix() . 'options');
                }
            }

            if (total_rows(db_prefix() . 'proposals', [
                    'government_id' => $id,
                ]) > 0) {
                $this->db->where('government_id', $id);
                $government = $this->db->get(db_prefix() . 'proposals')->row();
                $this->db->where('id', $government->id);
                $this->db->update(db_prefix() . 'proposals', [
                    'government_id'    => null,
                    'date_converted' => null,
                ]);
            }

            delete_tracked_emails($id, 'government');

            $this->db->where('relid IN (SELECT id from ' . db_prefix() . 'itemable WHERE rel_type="government" AND rel_id="' . $this->db->escape_str($id) . '")');
            $this->db->where('fieldto', 'items');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'government');
            $this->db->delete(db_prefix() . 'notes');

            $this->db->where('rel_type', 'government');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'views_tracking');

            $this->db->where('rel_type', 'government');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'reminders');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'government');
            $this->db->delete(db_prefix() . 'government_activity');

            $attachments = $this->get_attachments($id);
            foreach ($attachments as $attachment) {
                $this->delete_attachment($attachment['id']);
            }

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'government');
            $this->db->delete('scheduled_emails');

            // Get related tasks
            $this->db->where('rel_type', 'government');
            $this->db->where('rel_id', $id);
            $tasks = $this->db->get(db_prefix() . 'tasks')->result_array();
            foreach ($tasks as $task) {
                $this->tasks_model->delete_task($task['id']);
            }
            if ($simpleDelete == false) {
                log_activity('governments Deleted [Number: ' . $number . ']');
            }

            return true;
        }

        return false;
    }

    /**
     * Set government to sent when email is successfuly sended to client
     * @param mixed $id governmentid
     */
    public function set_government_sent($id, $emails_sent = [])
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'governments', [
            'sent'     => 1,
            'datesend' => date('Y-m-d H:i:s'),
        ]);

        $this->log_government_activity($id, 'invoice_government_activity_sent_to_client', false, serialize([
            '<custom_data>' . implode(', ', $emails_sent) . '</custom_data>',
        ]));

        // Update government state to sent
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'governments', [
            'state' => 2,
        ]);

        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'government');
        $this->db->delete('scheduled_emails');
    }

    /**
     * Send expiration reminder to customer
     * @param mixed $id government id
     * @return boolean
     */
    public function send_expiry_reminder($id)
    {
        $government        = $this->get($id);
        $government_number = format_government_number($government->id);
        set_mailing_constant();
        $pdf              = government_pdf($government);
        $attach           = $pdf->Output($government_number . '.pdf', 'S');
        $emails_sent      = [];
        $sms_sent         = false;
        $sms_reminder_log = [];

        // For all cases update this to prevent sending multiple reminders eq on fail
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'governments', [
            'is_expiry_notified' => 1,
        ]);

        $contacts = $this->clients_model->get_contacts($government->clientid, ['active' => 1, 'government_emails' => 1]);

        foreach ($contacts as $contact) {
            $template = mail_template('government_expiration_reminder', $government, $contact);

            $merge_fields = $template->get_merge_fields();

            $template->add_attachment([
                'attachment' => $attach,
                'filename'   => str_replace('/', '-', $government_number . '.pdf'),
                'type'       => 'application/pdf',
            ]);

            if ($template->send()) {
                array_push($emails_sent, $contact['email']);
            }

            if (can_send_sms_based_on_creation_date($government->datecreated)
                && $this->app_sms->trigger(SMS_TRIGGER_ESTIMATE_EXP_REMINDER, $contact['phonenumber'], $merge_fields)) {
                $sms_sent = true;
                array_push($sms_reminder_log, $contact['firstname'] . ' (' . $contact['phonenumber'] . ')');
            }
        }

        if (count($emails_sent) > 0 || $sms_sent) {
            if (count($emails_sent) > 0) {
                $this->log_government_activity($id, 'not_expiry_reminder_sent', false, serialize([
                    '<custom_data>' . implode(', ', $emails_sent) . '</custom_data>',
                ]));
            }

            if ($sms_sent) {
                $this->log_government_activity($id, 'sms_reminder_sent_to', false, serialize([
                    implode(', ', $sms_reminder_log),
                ]));
            }

            return true;
        }

        return false;
    }

    /**
     * Send government to client
     * @param mixed $id governmentid
     * @param string $template email template to sent
     * @param boolean $attachpdf attach government pdf or not
     * @return boolean
     */
    public function send_government_to_client($id, $template_name = '', $attachpdf = true, $cc = '', $manually = false)
    {
        $government = $this->get($id);

        if ($template_name == '') {
            $template_name = $government->sent == 0 ?
                'government_send_to_customer' :
                'government_send_to_customer_already_sent';
        }

        $government_number = format_government_number($government->id);

        $emails_sent = [];
        $send_to     = [];

        // Manually is used when sending the government via add/edit area button Save & Send
        if (!DEFINED('CRON') && $manually === false) {
            $send_to = $this->input->post('sent_to');
        } elseif (isset($GLOBALS['scheduled_email_contacts'])) {
            $send_to = $GLOBALS['scheduled_email_contacts'];
        } else {
            $contacts = $this->clients_model->get_contacts(
                $government->clientid,
                ['active' => 1, 'government_emails' => 1]
            );

            foreach ($contacts as $contact) {
                array_push($send_to, $contact['id']);
            }
        }

        $state_auto_updated = false;
        $state_now          = $government->state;

        if (is_array($send_to) && count($send_to) > 0) {
            $i = 0;

            // Auto update state to sent in case when user sends the government is with state draft
            if ($state_now == 1) {
                $this->db->where('id', $government->id);
                $this->db->update(db_prefix() . 'governments', [
                    'state' => 2,
                ]);
                $state_auto_updated = true;
            }

            if ($attachpdf) {
                $_pdf_government = $this->get($government->id);
                set_mailing_constant();
                $pdf = government_pdf($_pdf_government);

                $attach = $pdf->Output($government_number . '.pdf', 'S');
            }

            foreach ($send_to as $contact_id) {
                if ($contact_id != '') {
                    // Send cc only for the first contact
                    if (!empty($cc) && $i > 0) {
                        $cc = '';
                    }

                    $contact = $this->clients_model->get_contact($contact_id);

                    if (!$contact) {
                        continue;
                    }

                    $template = mail_template($template_name, $government, $contact, $cc);

                    if ($attachpdf) {
                        $hook = hooks()->apply_filters('send_government_to_customer_file_name', [
                            'file_name' => str_replace('/', '-', $government_number . '.pdf'),
                            'government'  => $_pdf_government,
                        ]);

                        $template->add_attachment([
                            'attachment' => $attach,
                            'filename'   => $hook['file_name'],
                            'type'       => 'application/pdf',
                        ]);
                    }

                    if ($template->send()) {
                        array_push($emails_sent, $contact->email);
                    }
                }
                $i++;
            }
        } else {
            return false;
        }

        if (count($emails_sent) > 0) {
            $this->set_government_sent($id, $emails_sent);
            hooks()->do_action('government_sent', $id);

            return true;
        }

        if ($state_auto_updated) {
            // government not send to customer but the state was previously updated to sent now we need to revert back to draft
            $this->db->where('id', $government->id);
            $this->db->update(db_prefix() . 'governments', [
                'state' => 1,
            ]);
        }

        return false;
    }

    /**
     * All government activity
     * @param mixed $id governmentid
     * @return array
     */
    public function get_government_activity($id)
    {
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'government');
        $this->db->order_by('date', 'desc');

        return $this->db->get(db_prefix() . 'government_activity')->result_array();
    }

    /**
     * Log government activity to database
     * @param mixed $id governmentid
     * @param string $description activity description
     */
    public function log_government_activity($id, $description = '', $client = false, $additional_data = '')
    {
        $staffid   = get_staff_user_id();
        $full_name = get_staff_full_name(get_staff_user_id());
        if (DEFINED('CRON')) {
            $staffid   = '[CRON]';
            $full_name = '[CRON]';
        } elseif ($client == true) {
            $staffid   = null;
            $full_name = '';
        }

        $this->db->insert(db_prefix() . 'government_activity', [
            'description'     => $description,
            'date'            => date('Y-m-d H:i:s'),
            'rel_id'          => $id,
            'rel_type'        => 'government',
            'staffid'         => $staffid,
            'full_name'       => $full_name,
            'additional_data' => $additional_data,
        ]);
    }

    /**
     * Updates pipeline order when drag and drop
     * @param mixe $data $_POST data
     * @return void
     */
    public function update_pipeline($data)
    {
        $this->mark_action_state($data['state'], $data['governmentid']);
        AbstractKanban::updateOrder($data['order'], 'pipeline_order', 'governments', $data['state']);
    }

    /**
     * Get government unique year for filtering
     * @return array
     */
    public function get_governments_years()
    {
        return $this->db->query('SELECT DISTINCT(YEAR(date)) as year FROM ' . db_prefix() . 'governments ORDER BY year DESC')->result_array();
    }

    private function map_shipping_columns($data)
    {
        if (!isset($data['include_shipping'])) {
            foreach ($this->shipping_fields as $_s_field) {
                if (isset($data[$_s_field])) {
                    $data[$_s_field] = null;
                }
            }
            $data['show_shipping_on_government'] = 1;
            $data['include_shipping']          = 0;
        } else {
            $data['include_shipping'] = 1;
            // set by default for the next time to be checked
            if (isset($data['show_shipping_on_government']) && ($data['show_shipping_on_government'] == 1 || $data['show_shipping_on_government'] == 'on')) {
                $data['show_shipping_on_government'] = 1;
            } else {
                $data['show_shipping_on_government'] = 0;
            }
        }

        return $data;
    }

    public function do_kanban_query($state, $search = '', $page = 1, $sort = [], $count = false)
    {
        _deprecated_function('governments_model::do_kanban_query', '2.9.2', 'governmentsPipeline class');

        $kanBan = (new governmentsPipeline($state))
            ->search($search)
            ->page($page)
            ->sortBy($sort['sort'] ?? null, $sort['sort_by'] ?? null);

        if ($count) {
            return $kanBan->countAll();
        }

        return $kanBan->get();
    }
}
