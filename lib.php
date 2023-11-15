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

require_once($CFG->dirroot . '/repository/lib.php');

/**
 * Main repository_txttoimg class.
 *
 * @package    repository_txttoimg
 * @copyright  2022
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_txttoimg extends repository {

    /**
     * Get Listing function
     *
     * This function is for init of repository.
     * @param string $path
     * @param int $page
     *
     * @return array
     */
    public function get_listing($path = '', $page = '') {
        return array('list' => array());
    }

    /**
     * Search function
     *
     * This is the function that do the search in txttoimg and return an array of images.
     * @param string $searchtext
     * @param int $page
     *
     * @return array
     */
    public function search($searchtext, $page = 0) {
        global $SESSION, $CFG, $USER;
        $perpage = 10;

        if (($searchtext == "") && (isset($SESSION->txttoimgsearch))) {
            $q = $SESSION->txttoimgsearch;
        } else {
            $q = $searchtext;
            $SESSION->txttoimgsearch = $q;
        }
        if (!$page) {
            $page = 1;
        }

        $key = get_config('txttoimg', 'key');
        $images = get_config('txttoimg', 'images');
        $size = get_config('txttoimg', 'size');
        switch ($size) {
            case '0':
                $size = 256;
                break;
            case '1':
                $size = 512;
                break;
            case '2':
                $size = 1024;
                break;
            default:
                $size = 512;
        }
        $size = $size . 'x' . $size;
        $model = get_config('txttoimg', 'version');
        switch ($model) {
            case '0':
                $model = 'dall-e-2';
                break;
            case '1':
                $model = 'dall-e-3';
                break;
            default:
                $model = 'dall-e-2';
        }
        if ($model == 'dall-e-3') {
            $sizever3 = get_config('txttoimg', 'sizever3');
            switch ($sizever3) {
                case '0':
                    $size = '1024x1024';
                    break;
                case '1':
                    $size = '1024x1792';
                    break;
                case '2':
                    $size = '1792x1024';
                    break;
                default:
                    $size = '1024x1024';
            }
            // Currently Dall-e 3 API force creating one image.
            $images = 1;
        }

        $url = 'https://api.openai.com/v1/images/generations';

        $authorization = "Authorization: Bearer " . $key;

        $data = "{
            \"model\": \"$model\",
            \"prompt\": \"$q\",
            \"n\": $images,
            \"size\": \"$size\"}";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $result = json_decode(curl_exec($ch));
        curl_close($ch);

        if (isset($result->error)) {
            $title = 'Error.png';
            $list[] = array(
                    'shorttitle' => $result->error->code,
                    'thumbnail_title' => $result->error->message,
                    'title' => $result->error->code,
                    'description' => $result->error->message,
                    'thumbnail' => $CFG->wwwroot . '/repository/txttoimg/pix/error.png',
                    'thumbnail_width' => 150,
                    'thumbnail_height' => 100,
                    'size' => 10000,
                    'author' => $USER->firstname . ' ' . $USER->lastname,
                    'source' => $CFG->wwwroot . '/repository/txttoimg/pix/error.png',
                    'license' => 'public'
            );
        } else {
            $arresult = $result->data;
            for ($imagecounter = 0; $imagecounter < $images; $imagecounter++) {
                $title = $q . '-' . $imagecounter . '.png';
                $list[] = array(
                        'shorttitle' => $title,
                        'thumbnail_title' => $title,
                        'title' => $title,
                        'description' => $title,
                        'thumbnail' => $arresult[$imagecounter]->url,
                        'thumbnail_width' => 150,
                        'thumbnail_height' => 100,
                        'size' => 10000,
                        'author' => $USER->firstname . ' ' . $USER->lastname,
                        'source' => $arresult[$imagecounter]->url,
                        'license' => 'public'
                );
            }
        }
        $ret  = array();
        $ret['nologin'] = false;
        $ret['page'] = (int)$page;
        if ($ret['page'] < 1) {
            $ret['page'] = 1;
        }
        $start = 1;
        $max = 10;
        $ret['list'] = $list;
        $ret['norefresh'] = true;
        $ret['nosearch'] = false;
        // If the number of results is smaller than $max, it means we reached the last page.
        $ret['pages'] = (count($ret['list']) < $max) ? $ret['page'] : -1;
        return $ret;
    }

    /**
     * get type option name function
     *
     * This function is for module settings.
     * @return array
     */
    public static function get_type_option_names() {
        return array_merge(parent::get_type_option_names(), ['images', 'size', 'key', 'version', 'sizever3']);
    }

    /**
     * get type config form function
     *
     * This function is the form of module settings.
     *
     * @param object $mform
     * @param string $classname
     *
     * @return none
     */
    public static function type_config_form($mform, $classname = 'repository') {
        parent::type_config_form($mform);

        $version = get_config('repository_txttoimg', 'version');
        $select = $mform->addElement('select', 'version', get_string('version', 'repository_txttoimg'),
            [ 'Dall-e 2',
              'Dall-e 3' ]);
        $select->setSelected($version);

        $size = get_config('repository_txttoimg', 'size');
        $select = $mform->addElement('select', 'size', get_string('size', 'repository_txttoimg'), ['256', '512', '1024']);
        $select->setSelected($size);

        $size = get_config('repository_txttoimg', 'sizever3');
        // On Dall-e 3 the sizes are 1024x1024, 1024x1792 or 1792x1024.
        $select = $mform->addElement('select', 'sizever3', get_string('sizever3', 'repository_txttoimg'),
            [ get_string('square', 'repository_txttoimg'),
              get_string('portrait', 'repository_txttoimg'),
              get_string('landscape', 'repository_txttoimg') ]);
        $select->setSelected($sizever3);

        $key = get_config('repository_txttoimg', 'key');
        $mform->addElement('password', 'key', get_string('api', 'repository_txttoimg') . " ("
                            . get_string('api_description', 'repository_txttoimg') . ")" , array('size' => '60'));
        $mform->setDefault('key', $key);
        $mform->setType('key', PARAM_RAW_TRIMMED);

        $images = get_config('repository_txttoimg', 'images');
        $select = $mform->addElement('select', 'images', get_string('images', 'repository_txttoimg'),
                                    [1 => '1', 2 => '2', 3 => '3' , 4 => '4']);
        $select->setSelected($images);

    }

    /**
     * check login function
     *
     * This function help showing the search form.
     * @return bool
     */
    public function check_login() {
        return !empty($this->keyword);
    }

    /**
     * print login function
     *
     * This function generates the search form.
     * @param bool $ajax
     *
     * @return array
     */
    public function print_login($ajax = true) {
        $ret = array();
        $check = get_config('txttoimg', 'key');
        if (trim($check) == "") {
            $warning = "<p class='errorbox'>" . get_string('warning', 'repository_txttoimg') . "</p>";
        } else {
            $warning = "";
        }
        $search = new stdClass();
        $search->type = 'text';
        $search->id   = 'txttoimg_search';
        $search->name = 's';
        $search->label = $warning . get_string('search', 'repository_txttoimg').': ';

        $ret['login'] = array($search);
        $ret['login_btn_label'] = get_string('search');
        $ret['login_btn_action'] = 'search';
        $ret['allowcaching'] = true; // Indicates that login form can be cached in filepicker.js.
        return $ret;
    }

    /**
     * supported returntype function
     *
     * plugin only return internal links, according to txttoimg term of use.
     * @return int
     */
    public function supported_returntypes() {
        return FILE_INTERNAL;
    }

    /**
     * Is this repository accessing private data?
     *
     * @return bool
     */
    public function contains_private_data() {
        return false;
    }

}
