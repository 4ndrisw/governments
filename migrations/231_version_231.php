<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_231 extends App_module_migration {
    public function up() {
        // Perform database upgrade here
        ALTER TABLE `tblclients` ADD `is_government` TINYINT(1) NULL DEFAULT NULL, ADD INDEX `is_government` (`is_government`);
    }
}