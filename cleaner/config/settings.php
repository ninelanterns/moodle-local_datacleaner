<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    cleaner_courses
 * @copyright  2015 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_configtextarea(
        'cleaner_config/names',
        new lang_string('names', 'cleaner_config'),
        new lang_string('namesdesc', 'cleaner_config'),
        "siteidentifier\n%salt%", PARAM_RAW, 60, 5));

    $table = new html_table();
    $table->data = array();
    $table->head = array(
        get_string('plugin'),
        get_string('name', 'cleaner_config'),
        get_string('value', 'cleaner_config'),
    );

    $config = get_config('cleaner_config');

    $where = '';
    $names = explode("\n", $config->names);
    foreach ($names as $name) {
        $name = trim($name);
        if (empty($name)) {
            continue;
        }
        if ($where) {
            $where .= " OR ";
        }
        $where .= " name LIKE '$name'"; // SQL injection vector but we're admin so OK.
    }
    $values = explode("\n", $config->vals);
    foreach ($values as $val) {
        $val = trim($val);
        if (empty($val)) {
            continue;
        }
        if ($where) {
            $where .= " OR ";
        }
        $where .= " value LIKE '$val'"; // SQL injection vector but we're admin so OK.
    }

    if ($where) {
        $itemstoremove = $DB->get_records_sql("SELECT *
                                                 FROM {config}
                                                WHERE $where
                                             ORDER BY name ");
        foreach ($itemstoremove as $r) {
            $table->data[] = array('core', $r->name, $r->value);
        }

        $itemstoremove = $DB->get_records_sql("SELECT *
                                                 FROM {config_plugins}
                                                WHERE ($where)
                                             ORDER BY plugin, name");
        foreach ($itemstoremove as $r) {
            $table->data[] = array($r->plugin, $r->name, $r->value);
        }
    }

    $settings->add(new admin_setting_configtextarea(
        'cleaner_config/vals',
        new lang_string('vals', 'cleaner_config'),
        new lang_string('valsdesc', 'cleaner_config') . "<br>\n" . html_writer::table($table),
        'test', PARAM_RAW, 60, 5));

}

