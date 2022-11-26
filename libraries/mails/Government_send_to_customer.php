<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Government_send_to_customer extends App_mail_template
{
    protected $for = 'customer';

    protected $government;

    protected $contact;

    public $slug = 'government-send-to-client';

    public $rel_type = 'government';

    public function __construct($government, $contact, $cc = '')
    {
        parent::__construct();

        $this->government = $government;
        $this->contact = $contact;
        $this->cc      = $cc;
    }

    public function build()
    {
        if ($this->ci->input->post('email_attachments')) {
            $_other_attachments = $this->ci->input->post('email_attachments');
            foreach ($_other_attachments as $attachment) {
                $_attachment = $this->ci->governments_model->get_attachments($this->government->id, $attachment);
                $this->add_attachment([
                                'attachment' => get_upload_path_by_type('government') . $this->government->id . '/' . $_attachment->file_name,
                                'filename'   => $_attachment->file_name,
                                'type'       => $_attachment->filetype,
                                'read'       => true,
                            ]);
            }
        }

        $this->to($this->contact->email)
        ->set_rel_id($this->government->id)
        ->set_merge_fields('client_merge_fields', $this->government->clientid, $this->contact->id)
        ->set_merge_fields('government_merge_fields', $this->government->id);
    }
}
