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
 * Plugin administration pages are defined here.
 *
 * @package     mod_payactiviti
 * @category    admin
 * @copyright   2024 fadilaaaa <aminudinfadila@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('mod_payactiviti_settings', new lang_string('pluginname', 'mod_payactiviti'));

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        $options = [
            'sandbox' => "Sandbox",
            'production' => "Production"
        ];

        $settings->add(
            new admin_setting_configselect(
                'payactiviti/environment',
                "environment",
                "Environment",
                'sandbox',
                $options
            )
        );

        $settings->add(
            new admin_setting_configtext(
                'payactiviti/ipaymu_va',
                "iPaymu Virtual Account",
                "iPaymu Virtual Account",
                '',
                PARAM_TEXT,
                40
            )
        );

        $settings->add(
            new admin_setting_configtext(
                'payactiviti/ipaymu_apikey',
                "iPaymu API Key",
                "iPaymu API Key",
                '',
                PARAM_TEXT
            )
        );
    }
}
