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
global $DB, $OUTPUT, $PAGE, $USER;
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

$pay = optional_param('pay', 0, PARAM_INT);
$cek = optional_param('check', 0, PARAM_INT);

if ($pay) {

  $referenceId = array(
    'user_id' => $USER->id,
    'module_id' => $moduleinstance->id,
  );
  $enctReferenceId = base64_encode(serialize($referenceId));
  $result = payactiviti_pay(
    $moduleinstance->name,
    $moduleinstance->cost,
    $enctReferenceId,
    $USER->firstname . ' ' . $USER->lastname,
  );

  if ($result != null && $result->Status == 200) {
    $DB->insert_record('payactiviti_student', array(
      'payactivitiid' => $moduleinstance->id,
      'userid' => $USER->id,
      'sessionid' => $result->Data->SessionID,
      'url' => $result->Data->Url,
      'referenceid' => $enctReferenceId,
      'timeexpired' => time() + 24 * 60 * 60,
      'timecreated' => time(),
    ));
    header('Location: ' . $result->Data->Url);
  } else {
    echo '<pre>';
    print_r($result);
    echo '</pre>';
  }
  die();
}

$modulecontext = context_module::instance($cm->id);
$payactiviti_student = $DB->get_record_sql(
  'SELECT * FROM {payactiviti_student} 
  WHERE userid = ? AND payactivitiid = ? AND timeexpired > ?',
  [$USER->id, $moduleinstance->id, time()]
);

if ($cek) {
  if (!$payactiviti_student) {
    redirect(
      new moodle_url('/mod/payactiviti/view.php', ['id' => $cm->id]),
      'Payment Failed',
      2
    );
  }
  $response = file_get_contents('https://hook.amfad.engineer/ipaymu_moodle/?sid=' . $payactiviti_student->sessionid);
  $response = json_decode($response);
  if ($response->status == 200) {
    if ($response->data->status_code = 1) {
      global $CFG;
      if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir . '/gradelib.php');
      }
      $grades = array(
        'userid' => $USER->id,
        'rawgrade' => 100,
      );
      $result = grade_update(
        'mod/payactiviti',
        $course->id,
        'mod',
        'payactiviti',
        $moduleinstance->id,
        0,
        $grades,
        $params
      );
      if ($result) {
        redirect(
          new moodle_url('/mod/payactiviti/view.php', ['id' => $cm->id]),
          'Payment Success',
          2
        );
      } else {
        redirect(
          new moodle_url('/mod/payactiviti/view.php', ['id' => $cm->id]),
          'Payment Failed',
          2
        );
      }
    } else {
      redirect(
        new moodle_url('/mod/payactiviti/view.php', ['id' => $cm->id]),
        'Payment Failed',
        2
      );
    }
  } else {
    redirect(
      new moodle_url('/mod/payactiviti/view.php', ['id' => $cm->id]),
      'Payment Failed',
      2
    );
  }
}

$PAGE->set_url('/mod/payactiviti/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();
$img_url = new moodle_url('/mod/payactiviti/pix/monologo.jpeg');

echo '
<div align="center">
  <p>
    This Activity requires a payment for pass.  
  </p>
  <p>';
echo $OUTPUT->heading(format_string($moduleinstance->name), 2);
echo '
  </p>
    <b>Cost: IDR ';
echo number_format($moduleinstance->cost, 0, ',', '.');
echo '
    </b>
  </p>
  <p>
    <img alt="iPaymu payments accepted" src="' . $img_url . '" style="width: 75px">
  </p>';
if ($payactiviti_student) {
  echo '<p>You have paid for this activity</p>';
  echo '
  <form method="POST">
    <input type="hidden" name="check" value="1">
    <input type="submit" value="Check Payment">
  </form>
  </div>';
} else {
  echo '<form target="_blank" method="post">
    <input type="hidden" name="instance_id" value="' . $moduleinstance->id . '">
    <input type="hidden" name="course_id" value="' . $USER->id . '">
    <input type="hidden" name="pay" value="1">
    <input type="submit" value="Pay via iPaymu">
  </form>
</div>
';
}
echo $OUTPUT->footer();
