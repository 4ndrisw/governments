<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once('install/governments.php');
require_once('install/government_activity.php');
require_once('install/government_items.php');
require_once('install/government_members.php');



$CI->db->query("
INSERT INTO `tblemailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('government', 'government-send-to-client', 'english', 'Send government to Customer', 'government # {government_number} created', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">Please find the attached government <strong># {government_number}</strong></span><br /><br /><span style=\"font-size: 12pt;\"><strong>government state:</strong> {government_state}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the government on the following link: <a href=\"{government_link}\">{government_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">We look forward to your communication.</span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}<br /></span>', '{companyname} | CRM', '', 0, 1, 0),
('government', 'government-already-send', 'english', 'government Already Sent to Customer', 'government # {government_number} ', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /> <br /><span style=\"font-size: 12pt;\">Thank you for your government request.</span><br /> <br /><span style=\"font-size: 12pt;\">You can view the government on the following link: <a href=\"{government_link}\">{government_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">Please contact us for more information.</span><br /> <br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('government', 'government-declined-to-staff', 'english', 'government Declined (Sent to Staff)', 'Customer Declined government', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) declined government with number <strong># {government_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the government on the following link: <a href=\"{government_link}\">{government_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('government', 'government-accepted-to-staff', 'english', 'government Accepted (Sent to Staff)', 'Customer Accepted government', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) accepted government with number <strong># {government_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the government on the following link: <a href=\"{government_link}\">{government_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('government', 'government-thank-you-to-customer', 'english', 'Thank You Email (Sent to Customer After Accept)', 'Thank for you accepting government', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /> <br /><span style=\"font-size: 12pt;\">Thank for for accepting the government.</span><br /> <br /><span style=\"font-size: 12pt;\">We look forward to doing business with you.</span><br /> <br /><span style=\"font-size: 12pt;\">We will contact you as soon as possible.</span><br /> <br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('government', 'government-expiry-reminder', 'english', 'government Expiration Reminder', 'government Expiration Reminder', '<p><span style=\"font-size: 12pt;\">Hello {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">The government with <strong># {government_number}</strong> will expire on <strong>{government_duedate}</strong></span><br /><br /><span style=\"font-size: 12pt;\">You can view the government on the following link: <a href=\"{government_link}\">{government_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span></p>', '{companyname} | CRM', '', 0, 1, 0),
('government', 'government-send-to-client', 'english', 'Send government to Customer', 'government # {government_number} created', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">Please find the attached government <strong># {government_number}</strong></span><br /><br /><span style=\"font-size: 12pt;\"><strong>government state:</strong> {government_state}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the government on the following link: <a href=\"{government_link}\">{government_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">We look forward to your communication.</span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}<br /></span>', '{companyname} | CRM', '', 0, 1, 0),
('government', 'government-already-send', 'english', 'government Already Sent to Customer', 'government # {government_number} ', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /> <br /><span style=\"font-size: 12pt;\">Thank you for your government request.</span><br /> <br /><span style=\"font-size: 12pt;\">You can view the government on the following link: <a href=\"{government_link}\">{government_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">Please contact us for more information.</span><br /> <br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('government', 'government-declined-to-staff', 'english', 'government Declined (Sent to Staff)', 'Customer Declined government', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) declined government with number <strong># {government_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the government on the following link: <a href=\"{government_link}\">{government_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('government', 'government-accepted-to-staff', 'english', 'government Accepted (Sent to Staff)', 'Customer Accepted government', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) accepted government with number <strong># {government_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the government on the following link: <a href=\"{government_link}\">{government_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('government', 'staff-added-as-program-member', 'english', 'Staff Added as Program Member', 'New program assigned to you', '<p>Hi <br /><br />New government has been assigned to you.<br /><br />You can view the government on the following link <a href=\"{government_link}\">government__number</a><br /><br />{email_signature}</p>', '{companyname} | CRM', '', 0, 1, 0),
('government', 'government-accepted-to-staff', 'english', 'government Accepted (Sent to Staff)', 'Customer Accepted government', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) accepted government with number <strong># {government_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the government on the following link: <a href=\"{government_link}\">{government_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0);
");
/*
 *
 */

// Add options for governments
add_option('delete_only_on_last_government', 1);
add_option('government_prefix', 'GOV-');
add_option('next_government_number', 1);
add_option('default_government_assigned', 9);
add_option('government_number_decrement_on_delete', 0);
add_option('government_number_format', 4);
add_option('government_year', date('Y'));
add_option('exclude_government_from_client_area_with_draft_state', 1);
add_option('predefined_clientnote_government', '- Staf diatas untuk melakukan riksa uji pada peralatan tersebut.
- Staf diatas untuk membuat dokumentasi riksa uji sesuai kebutuhan.');
add_option('predefined_terms_government', '- Pelaksanaan riksa uji harus mengikuti prosedur yang ditetapkan perusahaan pemilik alat.
- Dilarang membuat dokumentasi tanpa seizin perusahaan pemilik alat.
- Dokumen ini diterbitkan dari sistem CRM, tidak memerlukan tanda tangan dari PT. Cipta Mas Jaya');
add_option('government_due_after', 1);
add_option('allow_staff_view_governments_assigned', 1);
add_option('show_assigned_on_governments', 1);
add_option('require_client_logged_in_to_view_government', 0);

add_option('show_program_on_government', 1);
add_option('governments_pipeline_limit', 1);
add_option('default_governments_pipeline_sort', 1);
add_option('government_accept_identity_confirmation', 1);
add_option('government_qrcode_size', '160');
add_option('government_send_telegram_message', 0);


/*

DROP TABLE `tblgovernments`;
DROP TABLE `tblgovernment_activity`, `tblgovernment_items`, `tblgovernment_members`;
delete FROM `tbloptions` WHERE `name` LIKE '%government%';
DELETE FROM `tblemailtemplates` WHERE `type` LIKE 'government';



*/