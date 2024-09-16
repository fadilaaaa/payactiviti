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
 * Library of interface functions and constants.
 *
 * @package     mod_payactiviti
 * @copyright   2024 fadilaaaa <aminudinfadila@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function payactiviti_supports($feature)
{
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
    }
}

function payactiviti_pay(
    $payactivitiName,
    $payactivitiPrice,
    $payactivitiReferenceId,
    $buyerName = '',
) {
    $environtment = get_config('payactiviti', 'environment');
    $ipaymu_apikey = get_config('payactiviti', 'ipaymu_apikey');
    $ipaymu_va = get_config('payactiviti', 'ipaymu_va');
    $method       = 'POST';
    $logo_url = new moodle_url('/theme/image.php/boost/theme/1726313550/favicon');

    if ($environtment == 'sandbox') {
        $url = 'https://sandbox.ipaymu.com/api/v2/payment';
    } else {
        $url = 'https://my.ipaymu.com/api/v2/payment';
    }
    //Request Body//
    $body['product']    = array($payactivitiName);
    $body['qty']        = array('1');
    $body['price']      = array($payactivitiPrice);
    $body['expired'] = 24;
    $body['buyerName']  = $buyerName;
    $body['notifyUrl']  = 'https://hook.amfad.engineer/ipaymu_moodle';
    $body['referenceId'] = $payactivitiReferenceId;
    //End Request Body//

    //Generate Signature
    // *Don't change this
    $jsonBody     = json_encode($body, JSON_UNESCAPED_SLASHES);
    $requestBody  = strtolower(hash('sha256', $jsonBody));
    $stringToSign = strtoupper($method) . ':' . $ipaymu_va . ':' . $requestBody . ':' . $ipaymu_apikey;
    $signature    = hash_hmac('sha256', $stringToSign, $ipaymu_apikey);
    $timestamp    = Date('YmdHis');
    //End Generate Signature

    $ch = curl_init($url);

    $headers = array(
        'Accept: application/json',
        'Content-Type: application/json',
        'va: ' . $ipaymu_va,
        'signature: ' . $signature,
        'timestamp: ' . $timestamp
    );

    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    curl_setopt($ch, CURLOPT_POST, count($body));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $err = curl_error($ch);
    $ret = curl_exec($ch);
    curl_close($ch);

    if ($err) {
        return null;
    } else {
        //Response
        $ret = json_decode($ret);
        return $ret;
        //End Response
    }
}

function payactiviti_update_grades($payactiviti, $userid = 0, $nullifnone = true)
{
    global $CFG, $DB;
    require_once($CFG->libdir . '/gradelib.php');
    // Updating user's grades is not supported at this time in the logic module.
    return;
}

function payactiviti_grade_item_update($payactiviti, $grades = null)
{
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir . '/gradelib.php');
    }

    if (property_exists($payactiviti, 'cm_id')) { //it may not be always present
        $params = array('itemname' => $payactiviti->name, 'idnumber' => $payactiviti->cm_id);
    } else {
        $params = array('itemname' => $payactiviti->name);
    }

    if ($payactiviti->mode != 'practice') {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax'] = 100;
        $params['grademin'] = 0;
    } else {
        return;
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    } else if (!empty($grades)) {
        // Need to calculate raw grade (Note: $grades has many forms)
        if (is_object($grades)) {
            $grades = array($grades->userid => $grades);
        } else if (array_key_exists('userid', $grades)) {
            $grades = array($grades['userid'] => $grades);
        }

        foreach ($grades as $key => $grade) {
            if (!is_array($grade)) {
                $grades[$key] = $grade = (array) $grade;
            }
        }
    }

    return grade_update('mod/payactiviti', $payactiviti->course, 'mod', 'payactiviti', $payactiviti->id, 0, $grades, $params);
}

/**
 * Saves a new instance of the mod_payactiviti into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_payactiviti_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function payactiviti_add_instance($moduleinstance, $mform = null)
{
    global $DB;

    $moduleinstance->timecreated = time();

    $id = $DB->insert_record('payactiviti', $moduleinstance);

    return $id;
}

/**
 * Updates an instance of the mod_payactiviti in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_payactiviti_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function payactiviti_update_instance($moduleinstance, $mform = null)
{
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('payactiviti', $moduleinstance);
}

/**
 * Removes an instance of the mod_payactiviti from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function payactiviti_delete_instance($id)
{
    global $DB;

    $exists = $DB->get_record('payactiviti', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('payactiviti', array('id' => $id));

    return true;
}
