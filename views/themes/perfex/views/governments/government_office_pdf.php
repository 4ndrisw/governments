<?php

defined('BASEPATH') or exit('No direct script access allowed');

$dimensions = $pdf->getPageDimensions();

$info_right_column = '';
$info_left_column  = '';

$info_right_column .= '<span style="font-weight:bold;font-size:27px;">' . _l('government_office_pdf_heading') . '</span><br />';
$info_right_column .= '<b style="color:#4e4e4e;"># ' . str_replace("GOV","GOV-UPT",$government_number) . '</b>';

if (get_option('show_state_on_pdf_ei') == 1) {
    $info_right_column .= '<br /><span style="color:rgb(' . government_state_color_pdf($state) . ');text-transform:uppercase;">' . format_government_state($state, '', false) . '</span>';
}

// Add logo
$info_left_column .= pdf_logo_url();
// Write top left logo and right column info/text
pdf_multi_row($info_left_column, $info_right_column, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

$pdf->ln(8);

$organization_info = '<div style="color:#424242;">';
    $organization_info .= format_organization_info();
$organization_info .= '</div>';

// Government to
$government_info = '<b>' . _l('government_office_to') . '</b>';
$government_info .= '<div style="color:#424242;">';
$government_info .= format_office_info($government->office, 'government', 'billing');
$government_info .= '</div>';

$left_info  = $swap == '1' ? $government_info : $organization_info;
$right_info = $swap == '1' ? $organization_info : $government_info;

pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

// Government to
$left_info ='<p>' . _l('government_opening') . ',</p>';
$left_info .= _l('government_client');
$left_info .= '<div style="color:#424242;">';
$left_info .= format_customer_info($government, 'government', 'billing');
$left_info .= '</div>';

$right_info = '';

$pdf->ln(4);
pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 1) - $dimensions['lm']);

$organization_info = '<strong>'. _l('government_members') . ': </strong><br />';

$CI = &get_instance();
$CI->load->model('governments_model');
$government_members = $CI->governments_model->get_government_members($government->id,true);
$i=1;
foreach($government_members as $member){
  $organization_info .=  $i.'. ' .$member['firstname'] .' '. $member['lastname']. '<br />';
  $i++;
}

$government_info = '<br />' . _l('government_data_date') . ': ' . _d($government->date) . '<br />';


if ($government->program_id != 0 && get_option('show_program_on_government') == 1) {
    $government_info .= _l('program') . ': ' . get_program_name_by_id($government->program_id) . '<br />';
}


$left_info  = $swap == '1' ? $government_info : $organization_info;
$right_info = $swap == '1' ? $organization_info : $government_info;

$pdf->ln(4);
pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

// The Table
$pdf->Ln(hooks()->apply_filters('pdf_info_and_table_separator', 6));

// The items table
$items = get_government_items_table_data($government, 'government', 'pdf');

$tblhtml = $items->table();

$pdf->writeHTML($tblhtml, true, false, false, false, '');

$pdf->SetFont($font_name, '', $font_size);

$assigned_path = <<<EOF
        <img width="150" height="150" src="$government->assigned_path">
    EOF;    
$assigned_info = '<div style="text-align:center;">';
    $assigned_info .= get_option('invoice_company_name') . '<br />';
    $assigned_info .= $assigned_path . '<br />';

if ($government->assigned != 0 && get_option('show_assigned_on_governments') == 1) {
    $assigned_info .= get_staff_full_name($government->assigned);
}
$assigned_info .= '</div>';

$acceptance_path = <<<EOF
    <img src="$government->acceptance_path">
EOF;
$client_info = '<div style="text-align:center;">';
    $client_info .= $government->client_company .'<br />';

if ($government->signed != 0) {
    $client_info .= _l('government_signed_by') . ": {$government->acceptance_firstname} {$government->acceptance_lastname}" . '<br />';
    $client_info .= _l('government_signed_date') . ': ' . _dt($government->acceptance_date_string) . '<br />';
    $client_info .= _l('government_signed_ip') . ": {$government->acceptance_ip}" . '<br />';

    $client_info .= $acceptance_path;
    $client_info .= '<br />';
}
$client_info .= '</div>';


$left_info  = $swap == '1' ? $client_info : $assigned_info;
$right_info = $swap == '1' ? $assigned_info : $client_info;
pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

$pdf->ln(2);   
$companyname = get_option('companyname');
$pdf->writeHTMLCell('', '', '', '', _l('government_crm_info', $companyname), 0, 1, false, true, 'L', true);
