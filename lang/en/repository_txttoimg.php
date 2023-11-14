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
 * Version details.
 *
 * @package   repository_txttoimg
 * @copyright 2022 OpenApp By Yedidia Klein http://openapp.co.il
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = "AI Text to Image";
$string['configplugin'] = "AI Text to Image Repository Settings";
$string['txttoimg:view'] = 'View AI Text to Image repository';
$string['api'] = "OpenAI API";
$string['api_description'] = 'OpenAI API from <a target="_new" href="https://platform.openai.com/account/api-keys">https://platform.openai.com/account/api-keys</a>';
$string['search'] = "Describe the Image You Wish to Generate";
$string['warning'] = "API isn't set !! You must set it in AI Text to Image Repository settings.";
$string['user'] = 'Use a global key or a key from user profile (Future feature not yet implemented)';
$string['size'] = 'Image Size (Dall-e 2)';
$string['images'] = 'Number of generated images';
$string['sizever3'] = 'Image Size (Dall-e 3)';
$string['square'] = 'Square';
$string['portrait'] = 'Portrait';
$string['landscape'] = 'Landscape';
$string['version'] = 'Dall-E Version';
