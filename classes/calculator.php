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
require_once($CFG->libdir . '/gradelib.php');
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

    public function calculate($grades, $groupmark, $noncompletionpenalty = 0, $paweighting = 1, $selfgrade = false)
    {
        $memberids = array_keys($grades);
        $totalscores = [];
        $fracscores = [];
        $numsubmitted = 0;

        $instance = $this->peerwork->id;
        $key = "{$instance}_gradeitem";
        $gradeitemid = get_config('peerworkcalculator_strath', $key);

        $gradegrades = [];
        if ($gradeitemid !== false) {
            $gradeitem = \grade_item::fetch(['id' => $gradeitemid]);

//        $cm = get_coursemodule_from_instance()
            //        grade_get_grade_items_for_activity();
            $gradeitems = grade_get_grades(
                $gradeitem->courseid,
                $gradeitem->itemtype,
                $gradeitem->itemmodule,
                $gradeitem->iteminstance,
                $memberids
            );
            if (count($gradeitems->items) != 1) {
                throw new \coding_exception("too many / few grade items");
            }

            $gradeitem = $gradeitems->items[0];

            // Target activity grades in grade book indexed by user id.
            $gradegrades = $gradeitem->grades;
            $gradegrades = array_map(function ($grade) {
                return $grade->grade;
            }, $gradegrades);
        }

        // Calculate the total scores.
        foreach ($memberids as $memberid) {
            foreach ($grades as $graderid => $gradesgiven) {
                if (!isset($totalscores[$graderid])) {
                    $totalscores[$graderid] = [];
                }

                if (isset($gradesgiven[$memberid])) {
                    $sum = array_reduce($gradesgiven[$memberid], function($carry, $item) {
                        $carry += $item;
                        return $carry;
                    });

                    $totalscores[$graderid][$memberid] = $sum;
                }
            }
        }

        // Calculate the fractional scores, and record whether scores were submitted.
        foreach ($memberids as $memberid) {
            $gradesgiven = $totalscores[$memberid];
            $total = array_sum($gradesgiven);

            $fracscores[$memberid] = array_reduce(array_keys($gradesgiven), function($carry, $peerid) use ($total, $gradesgiven) {
                $grade = $gradesgiven[$peerid];
                $carry[$peerid] = $total > 0 ? $grade / $total : 0;
                return $carry;
            }, []);

            $numsubmitted += !empty($fracscores[$memberid]) ? 1 : 0;
        }

        // Initialise everyone's score at 0.
        $webpascores = array_reduce($memberids, function($carry, $memberid) {
            $carry[$memberid] = 0;
            return $carry;
        }, []);

        // Walk through the individual scores given, and sum them up.
        foreach ($fracscores as $gradesgiven) {
            foreach ($gradesgiven as $memberid => $fraction) {
                $webpascores[$memberid] += $fraction;
            }
        }

        // We change the grades so that it matches the gradebook provided grades.
        $prelimgrades = [];
        if ($gradeitemid !== false) {
            $prelimgrades = $gradegrades;
        } else {
            // Calculate the students' preliminary grade (excludes weighting and penalties).
            $prelimgrades = array_map(function($score) use ($groupmark) {
                return max(0, min(100, $score * $groupmark));
            }, $webpascores);
        }

        // Calculate penalties.
        $noncompletionpenalties = array_reduce($memberids, function($carry, $memberid) use ($fracscores, $noncompletionpenalty) {
            $ispenalised = empty($fracscores[$memberid]);
            $carry[$memberid] = $ispenalised ? $noncompletionpenalty : 0;
            return $carry;
        });

        // Fudge the Contribution score to be 0 if the member didn't submit.
        foreach($memberids as $memberid) {
            if (empty($fracscores[$memberid])) {
                $webpascores[$memberid] = 0;
            }
        }

        // Calculate the grades again, but with weighting and penalties.
        $grades = array_reduce(
            $memberids,
            function($carry, $memberid) use ($webpascores, $noncompletionpenalties, $groupmark, $paweighting, $gradegrades, $gradeitemid) {
                // Use the gradebook grade.
                if ($gradeitemid !== false) {
                    $groupmark = $gradegrades[$memberid];
                }
                $score = $webpascores[$memberid];

                $adjustedgroupmark = $groupmark * $paweighting;
                $automaticgrade = $groupmark - $adjustedgroupmark;
                $grade = max(0, min(100, $automaticgrade + ($score * $adjustedgroupmark)));

                $penaltyamount = $noncompletionpenalties[$memberid];
                if ($penaltyamount > 0) {
                    $grade = max(0, ($grade - ($penaltyamount * 100)));
                }

                $carry[$memberid] = $grade;
                return $carry;
            },
            []);



        $result = new \mod_peerwork\pa_result(
            $fracscores,
            $webpascores,
            $prelimgrades,
            $grades,
            $noncompletionpenalties
        );
//        var_dump($result);
        return $result;
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
    public function calculate2($grades, $groupmark, $noncompletionpenalty = 0, $paweighting = 1, $selfgrade = false)
    {

        $paweighting = 1;
        $memberids = array_keys($grades);
        $totalscores = [];
        $fracscores = [];
        $numsubmitted = 0;
        $nonsubmits = [];
        // Calculate the total scores.
        foreach ($memberids as $memberid) {
            foreach ($grades as $graderid => $gradesgiven) {
                if (!isset($totalscores[$graderid])) {
                    $totalscores[$graderid] = [];
                }
                // Grades given to $memberid.
                if (isset($gradesgiven[$memberid])) {
                    $sum = array_reduce($gradesgiven[$memberid], function($carry, $item) {
                        $carry += $item;
                        return $carry;
                    });

                    $totalscores[$graderid][$memberid] = $sum;
                }
            }
        }

        // Calculate the fractional scores, and record whether scores were submitted.
        foreach ($memberids as $memberid) {
            $gradesgiven = $totalscores[$memberid];
            $total = array_sum($gradesgiven);

            $fracscores[$memberid] = array_reduce(array_keys($gradesgiven), function($carry, $peerid) use ($total, $gradesgiven) {
                $grade = $gradesgiven[$peerid];
                $carry[$peerid] = $total > 0 ? $grade / $total : 0;
                return $carry;
            }, []);
            if (empty($fracscores[$memberid])) {
                $nonsubmits[] = $memberid;
            }
            $numsubmitted += !empty($fracscores[$memberid]) ? 1 : 0;
        }
        debugging("Non submitters:" . print_r($nonsubmits,1));
        // Initialise everyone's score at 0.
        $webpascores = array_reduce($memberids, function($carry, $memberid) {
            $carry[$memberid] = 0;
            return $carry;
        }, []);

        // Walk through the individual scores given, and sum them up.

        foreach ($fracscores as $key => $gradesgiven) {
            echo "<pre>$key</pre>";
            if (in_array($key, $nonsubmits)) {
                debugging("Exclude non-submitter {$key}");
                continue;
            }
            foreach ($gradesgiven as $memberid => $fraction) {
                if (in_array($memberid, $nonsubmits)) {
                    debugging("Exclude non-submitter {$memberid}");
                    continue;
                }
                debugging("Adding fraction {$fraction} to {$memberid}");
                    $webpascores[$memberid] += $fraction;
            }
        }

        // Apply the fudge factor to all scores received.
        $nummembers = count($memberids);

        // A3: Fixed fudge factor
        $fudgefactor = $numsubmitted > 0 ? $nummembers / $numsubmitted : 1;
        $fudgefactor = 1;

        $webpascores = array_map(function($grade) use ($fudgefactor) {

            return $grade * $fudgefactor;
        }, $webpascores);

        // Calculate the students' preliminary grade (excludes weighting and penalties).
        $prelimgrades = array_map(function($score) use ($groupmark) {
            return max(0, min(100, $score * $groupmark));
        }, $webpascores);

        // Calculate penalties.
        $noncompletionpenalties = array_reduce($memberids, function($carry, $memberid) use ($fracscores, $noncompletionpenalty) {
            $ispenalised = empty($fracscores[$memberid]);
            $carry[$memberid] = $ispenalised ? $noncompletionpenalty : 0;
            return $carry;
        });

        // Calculate the grades again, but with weighting and penalties.
        $grades = array_reduce(
            $memberids,
            function($carry, $memberid) use ($webpascores, $noncompletionpenalties, $groupmark, $paweighting) {
                $score = $webpascores[$memberid];

                $adjustedgroupmark = $groupmark * $paweighting;
                $automaticgrade = $groupmark - $adjustedgroupmark;
                $grade = max(0, min(100, $automaticgrade + ($score * $adjustedgroupmark)));

                $penaltyamount = $noncompletionpenalties[$memberid];
                if ($penaltyamount > 0) {
                    // A1.
                    debugging("Grade before penalty :$grade");
                    $grade = $grade - ($penaltyamount * 100);
                    //$grade *= (1 - $penaltyamount);
                    debugging("Grade after penalty :$grade");
                }

                $carry[$memberid] = $grade;
                return $carry;
            },
            []);

        return new \mod_peerwork\pa_result($fracscores, $webpascores, $prelimgrades, $grades, $noncompletionpenalties);

    }

    function get_settings($mform) {
        global $COURSE;

        $instance = $mform->getElementValue('instance');
        $key = "{$instance}_gradeitem";
        $gradeitem = get_config('peerworkcalculator_strath', $key);
        $gradeitems = \grade_item::fetch_all(['courseid'=>$COURSE->id]);

        $gradeitems = array_map(function($gradeitem) {
            return $gradeitem->get_name();
        }, $gradeitems);
//        $gradeitems = array_merge([
//            '' => get_string('manuallygraded', 'peerworkcalculator_strath')
//        ], $gradeitems);
        $gi = $mform->createElement('autocomplete',
            'gradeitem',
            get_string('gradeitem', 'peerworkcalculator_strath'),
            $gradeitems
        );
        $mform->insertElementBefore($gi,'calculatorsettings');
        $mform->setDefault('gradeitem', $gradeitem);
    }
    public function save_settings($formdata) {
        $instance = $formdata->instance;
        $key = "{$instance}_gradeitem";
        if (empty($formdata->gradeitem)) {
            unset_config($key, 'peerworkcalculator_strath');
        } else {
            set_config($key, $formdata->gradeitem, 'peerworkcalculator_strath');
        }
        return true;
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
