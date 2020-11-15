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
 *
 * @package    cleaner_scheduled_tasks
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('cleaner_scheduled_tasks_settings');

$PAGE->add_body_class('cleaner_scheduled_tasks');

// Grab the data that we are going to display. This is a list of all scheduled tasks.
$tasks = \core\task\manager::get_all_scheduled_tasks();

// Grab this url to redirect to.
$post = new moodle_url('/local/datacleaner/cleaner/scheduled_tasks/index.php');

// Then send this data to the form
$taskform = new \cleaner_scheduled_tasks\form\task_form($post, $tasks);

// We have created the form with the correct fields and data, but we don't want to display this one.
if ($taskform->is_cancelled()) {
    // redirect to settings page if we cancelled.
    redirect($post);
} else if ($data = $taskform->get_data()) {
    // If we submit the form, then we should look at the data here and for each record insert the data into our cleaner_scheduled_tasks table.
    global $DB;

    $taskdata = isset($data->selected) ? $data->selected : false;
    $taskdata = $taskdata ? $taskdata : (array)$data;

    // Get an associative array so we can match submitted tasks to the tasks in the task_scheduled table
    $scheduledtasks = $DB->get_records_select_menu('task_scheduled', '', [], 'id', 'classname, id');

    foreach ($taskdata as $key => $taskenabled) {

        if (!isset($scheduledtasks["\\$key"])) {
            continue;
        }

        $record = $DB->get_record('cleaner_scheduled_tasks', ['taskscheduledid' => $scheduledtasks["\\$key"]]);
        if ($record && $taskenabled == 0) {
            // We have a record in our table but haven't selected it in our form. Should be deleted.
            $DB->delete_records('cleaner_scheduled_tasks', ['taskscheduledid' => $scheduledtasks["\\$key"]]);
        } else if ($record && $taskenabled == 1) {
            // The record already exists in our table with the correct setting, no update needed
            continue;
        } else if (!$record && $taskenabled == 1) {
            // The record doesn't exist, but it should because we selected it, insert it
            $taskinsert = new stdClass;
            $taskinsert->taskscheduledid = $scheduledtasks["\\$key"];
            $taskinsert->lastmodified = time();

            $DB->insert_record('cleaner_scheduled_tasks', $taskinsert);
        }
    }
}

// If we are here, then we are just displaying the form, and haven't cancelled or submitted it on this page.
echo $OUTPUT->header();
$taskform->display();
echo $OUTPUT->footer();
