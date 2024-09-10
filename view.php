<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Prints an instance of mod_payactiviti.
 *
 * @package     mod_payactiviti
 * @copyright   2024 fadilaaaa <aminudinfadila@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$p = optional_param('p', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('payactiviti', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('payactiviti', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('payactiviti', array('id' => $p), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('payactiviti', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);


// $event = \mod_payactiviti\event\course_module_viewed::create(array(
//     'objectid' => $moduleinstance->id,
//     'context' => $modulecontext
// ));
// $event->add_record_snapshot('course', $course);
// $event->add_record_snapshot('payactiviti', $moduleinstance);
// $event->trigger();

$PAGE->set_url('/mod/payactiviti/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();
echo '
<div align="center">
  <p>
    This Activity requires a payment for pass.  </p>
  <p>
    <b>
      Bayar dulu Boss!   </b>
  </p>
  <p>
    <b>
      Cost: IDR 10000000.00    </b>
  </p>
  <p>
    <img alt="iPaymu payments accepted" src="https://ssf.amfad.engineer/moodle30/theme/image.php/boost/enrol_ipaymu/1725813317/ipaymuw" style="width: 75px">
  </p>
  <p>
    Use the button below to pay and be enrolled within minutes!  </p>

  <form action="https://sandbox-payment.ipaymu.com/#/2528267c-cc3b-4b1e-8bee-28854a83ce02" method="get">
    <input type="hidden" name="id" value="2">
    <input type="hidden" name="instance" value="4">
    <input type="submit" value="Pay via iPaymu">
  </form>
</div>
';
echo $OUTPUT->footer();
