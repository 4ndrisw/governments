<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(LIBSPATH . 'pdf/App_pdf.php');

class Government_office_pdf extends App_pdf
{
    protected $government;

    private $government_number;

    public function __construct($government, $tag = '')
    {
        $this->load_language($government->clientid);

        $government                = hooks()->apply_filters('government_html_pdf_data', $government);
        $GLOBALS['government_pdf'] = $government;

        parent::__construct();

        $this->tag             = $tag;
        $this->government        = $government;
        $this->government_number = format_government_number($this->government->id);

        $this->SetTitle(str_replace("GOV", "GOV-UPT", $this->government_number));
    }

    public function prepare()
    {

        $this->set_view_vars([
            'state'          => $this->government->state,
            'government_number' => str_replace("GOV", "GOV-UPT", $this->government_number),
            'government'        => $this->government,
        ]);

        return $this->build();
    }

    protected function type()
    {
        return 'government';
    }

    protected function file_path()
    {
        $customPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/my_government_office_pdf.php';
        $actualPath = module_views_path('governments','themes/' . active_clients_theme() . '/views/governments/government_office_pdf.php');

        if (file_exists($customPath)) {
            $actualPath = $customPath;
        }

        return $actualPath;
    }
}
