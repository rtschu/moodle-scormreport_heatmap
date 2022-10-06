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
 * Core Report class of question reporting plugin
 *
 * @package    scormreport_question
 * @copyright  2021 Robin Tschudi
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace scormreport_question;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->dirroot/mod/scorm/locallib.php");
require_once("$CFG->dirroot/mod/scorm/report/question/classes/scormdata_provider.php");

use context_module;
use core\chart_series;


/**
 * Main class to control the question reporting
 *
 * @package    scormreport_question
 * @copyright  2021 Robin Tschudi
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report extends \mod_scorm\report {

    /**
     * Displays the full report.
     *
     * @param \stdClass $scorm full SCORM object
     * @param \stdClass $cm - full course_module object
     * @param \stdClass $course - full course object
     * @param string $download - type of download being requested
     * @return void
     */
    public function display($scorm, $cm, $course, $download) {
        global $OUTPUT, $PAGE;
        // Create a new dataprovider instance.
        $provider = new scormdata_provider($scorm->id);
        // Retrieve user and questiondata from said instance.
        // If you want to understand the flow of this plugin you should look  into those functions.
        $questiondata = $provider->get_sco_questiondata();
        $scoredata = $provider->get_sco_userscores();
        // If there is no data available for all the questions in this SCORM packet
        // ...either the SCORM packet is missconfigured or there are now answers yet.
        // We want to avoid deviding by zero when trying to find the average in those scenarios.
        if (count($scoredata) == 0) {
            $average = 0;
            $showdashboard = 0;
        } else {
            // Calculate the average score based on the scoredata provided.
            $average = number_format(array_sum(array_filter($scoredata)) / count($scoredata), 2);
            $showdashboard = 1;
        }
        // To have the text fit inside the final html elements the average is rounded to two decimal points.
        $roundedaverage = max(0, min(100, round($average)));
        // Render the dashboard. The dashboard displays average grade aswell as the projected passingquota.
        echo $OUTPUT->render_from_template('scormreport_question/report', [
            'averagepercentage' => $average,
            'roundedaverage' => $roundedaverage,
            'showdashboard' => $showdashboard
        ]);
        // Load the javascript required to calculate new passingquotas based on new minnimum scores.
        $PAGE->requires->js_call_amd('scormreport_question/dashboard_passingquota', 'init', array($scoredata));
        // Load the javascript that injects the visualized results for the questions.
        $PAGE->requires->js_call_amd('scormreport_question/report_view', 'init', array($questiondata));
    }
}
