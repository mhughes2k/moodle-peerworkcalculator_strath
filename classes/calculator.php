<?php
// This file is part of a 3rd party created module for Moodle - http://moodle.org/
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
 * Strathclyde calculator.
 *
 * @package    peerworkcalculator_strath
 * @copyright  2025 Michael Hughes
 * @author     Michael Hughes < michael@phoenixproductions.org.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace peerworkcalculator_strath;

use mod_peerwork\pa_result;
use mod_peerwork\peerworkcalculator_plugin;

/**
 * Strathclyde calculator.
 *
 * @package    peerworkcalculator_strath
 * @copyright  2025 Michael Hughes
 * @author     Michael Hughes <michael@phoenixproductions.org.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class calculator extends peerworkcalculator_plugin
{

    /**
     * Get the name of the webPA calculator plugin
     *
     * @return string
     */
    public function get_name()
    {
        return get_string('webpa', 'peerworkcalculator_strath');
    }

    /**
     * Calculate.
     *

     *
     * @param array $grades The list of marks given.
     * @param int $groupmark The mark given to the group.
     * @param int $noncompletionpenalty The penalty to be applied.
     * @param int $paweighting The weighting to be applied.
     * @param bool $selfgrade If self grading is enabled.
     * @return pa_result.
     */
    public function calculate($grades, $groupmark, $noncompletionpenalty = 0, $paweighting = 1, $selfgrade = false)
    {
        $memberids = array_keys($grades);
        $totalscores = [];
        $fracscores = [];
        $numsubmitted = 0;
        echo '<pre>';
        var_dump($memberids);
        // $grades is an array of arrays, each array is a student's 
        // grades for the other students, for each criteria.
        var_dump($grades);
        echo '</pre>';
        $pascores = [];
        $prelimgrades = [];
        $noncompletionpenalties = [];
        // TODO - This needs implemented
exit();
        return new pa_result($fracscores, $pascores, $prelimgrades, $grades, $noncompletionpenalties);
    }

    /**
     * Function to return if calculation uses paweighting.
     *
     * @return bool
     */
    public static function usespaweighting()
    {
        return false;
    }
}
