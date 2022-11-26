<?php

defined('BASEPATH') or exit('No direct script access allowed');

$route['governments/government/(:num)/(:any)'] = 'government/index/$1/$2';

/**
 * @since 2.0.0
 */
$route['governments/list'] = 'mygovernment/list';
$route['governments/show/(:num)/(:any)'] = 'mygovernment/show/$1/$2';
$route['governments/office/(:num)/(:any)'] = 'mygovernment/office/$1/$2';
$route['governments/pdf/(:num)'] = 'mygovernment/pdf/$1';
$route['governments/office_pdf/(:num)'] = 'mygovernment/office_pdf/$1';
