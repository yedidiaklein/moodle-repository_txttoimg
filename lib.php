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
        return ['list' => []];
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

        $list = [];

        if (($searchtext == "") && (isset($SESSION->txttoimgsearch))) {
            $q = $SESSION->txttoimgsearch;
        } else {
            $q = $searchtext;
            $SESSION->txttoimgsearch = $q;
        }
        if (!$page) {
            $page = 1;
        }

        if (trim($q) === '') {
            $ret = [];
            $ret['nologin'] = false;
            $ret['page'] = (int)$page;
            if ($ret['page'] < 1) {
                $ret['page'] = 1;
            }
            $ret['list'] = [];
            $ret['norefresh'] = true;
            $ret['nosearch'] = false;
            $ret['pages'] = $ret['page'];
            return $ret;
        }

        $manager = \core\di::get(\core_ai\manager::class);
        if (!$manager->is_action_available(\core_ai\aiactions\generate_image::class)) {
            $title = 'Error.png';
            $message = get_string('warning', 'repository_txttoimg');
            $list[] = [ 'shorttitle' => $title,
                        'thumbnail_title' => $message,
                        'title' => $title,
                        'description' => $message,
                        'thumbnail' => $CFG->wwwroot . '/repository/txttoimg/pix/error.png',
                        'thumbnail_width' => 150,
                        'thumbnail_height' => 100,
                        'size' => 10000,
                        'author' => $USER->firstname . ' ' . $USER->lastname,
                        'source' => $CFG->wwwroot . '/repository/txttoimg/pix/error.png',
                        'license' => 'public',
                      ];
        } else {
            try {
                $contextid = $this->context->id;
                $action = new \core_ai\aiactions\generate_image(
                    contextid: $contextid,
                    userid: $USER->id,
                    prompttext: $q,
                    quality: 'standard',
                    aspectratio: 'square',
                    numimages: 1,
                    style: 'natural',
                );

                $response = $manager->process_action($action);

                if ($response->get_success()) {
                    $draftfile = $response->get_response_data()['draftfile'];
                    $drafturl = \moodle_url::make_draftfile_url(
                        $draftfile->get_itemid(),
                        $draftfile->get_filepath(),
                        $draftfile->get_filename(),
                        false,
                    )->out(false);
                    $source = base64_encode(json_encode([
                        'contextid' => $draftfile->get_contextid(),
                        'component' => $draftfile->get_component(),
                        'filearea' => $draftfile->get_filearea(),
                        'itemid' => $draftfile->get_itemid(),
                        'filepath' => $draftfile->get_filepath(),
                        'filename' => $draftfile->get_filename(),
                    ]));

                    $title = $q . '.png';
                    $list[] = [ 'shorttitle' => $title,
                                'thumbnail_title' => $title,
                                'title' => $title,
                                'description' => $title,
                                'thumbnail' => $drafturl,
                                'thumbnail_width' => 150,
                                'thumbnail_height' => 100,
                                'size' => 10000,
                                'author' => $USER->firstname . ' ' . $USER->lastname,
                                                                'source' => $source,
                                'license' => 'public',
                              ];
                } else {
                    $title = 'Error.png';
                    $description = (string)$response->get_errormessage();
                    if ($description === '') {
                        $description = get_string('warning', 'repository_txttoimg');
                    }
                    $list[] = [ 'shorttitle' => $title,
                                'thumbnail_title' => $description,
                                'title' => $title,
                                'description' => $description,
                                'thumbnail' => $CFG->wwwroot . '/repository/txttoimg/pix/error.png',
                                'thumbnail_width' => 150,
                                'thumbnail_height' => 100,
                                'size' => 10000,
                                'author' => $USER->firstname . ' ' . $USER->lastname,
                                'source' => $CFG->wwwroot . '/repository/txttoimg/pix/error.png',
                                'license' => 'public',
                              ];
                }
            } catch (\Throwable $e) {
                $title = 'Error.png';
                $list[] = [ 'shorttitle' => $title,
                            'thumbnail_title' => $e->getMessage(),
                            'title' => $title,
                            'description' => $e->getMessage(),
                            'thumbnail' => $CFG->wwwroot . '/repository/txttoimg/pix/error.png',
                            'thumbnail_width' => 150,
                            'thumbnail_height' => 100,
                            'size' => 10000,
                            'author' => $USER->firstname . ' ' . $USER->lastname,
                            'source' => $CFG->wwwroot . '/repository/txttoimg/pix/error.png',
                            'license' => 'public',
                          ];
            }
        }
        $ret  = [];
        $ret['nologin'] = false;
        $ret['page'] = (int)$page;
        if ($ret['page'] < 1) {
            $ret['page'] = 1;
        }
        $max = 10;
        $ret['list'] = $list;
        $ret['norefresh'] = true;
        $ret['nosearch'] = false;
        // If the number of results is smaller than $max, it means we reached the last page.
        $ret['pages'] = (count($ret['list']) < $max) ? $ret['page'] : -1;
        return $ret;
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

        $mform->addElement('html', '<div class="alert alert-info">' .
            get_string('aisettings', 'repository_txttoimg') .
            '</div>');
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
        $ret = [];

        $manager = \core\di::get(\core_ai\manager::class);
        if (!$manager->is_action_available(\core_ai\aiactions\generate_image::class)) {
            $warning = "<p class='errorbox'>" . get_string('warning', 'repository_txttoimg') . "</p>";
        } else {
            $warning = "";
        }
        $search = new stdClass();
        $search->type = 'text';
        $search->id   = 'txttoimg_search';
        $search->name = 's';
        $search->label = $warning . get_string('search', 'repository_txttoimg').': ';

        $ret['login'] = [$search];
        $ret['login_btn_label'] = get_string('search');
        $ret['login_btn_action'] = 'search';
        $ret['allowcaching'] = false; // Avoid stale cached form fields when options change.
        return $ret;
    }

    /**
     * Does this repository browse Moodle-managed files?
     *
     * @return bool
     */
    public function has_moodle_files() {
        return true;
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
