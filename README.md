# Strathclyde Calculator

## Algorithm

## Historic "simple" model
The historic implementation of StrathPA, allowed for just 1 evaluation critera, so the "score" was simply taken as the "mean" of the scores awarded to a student by their peers.

Mod-peerwork allows for multiple, arbitrary criteria, so a more complex model that puts these together is necessary.

WebPA "simply" combines the values in the calculation, but we may not wish to.

We may elect to utilise the same algorithm but with an adjustment so that non-submission of reviews is handled so that the non-submitting user's score is not "improved" by this.

This simply could be to exclude them from the Web PA calculation.

## Modified Model
The modified Strathclyde calculator makes the following changes:

* If a member does not submit a peer review:
  * they are counted as a "non-submit", and they are not included in the 
    calculation of the of the scores. This prevents the issue where a 
    student's grade is boosted by *not responding* (https://github.com/amandadoughty/moodle-mod_peerwork/issues/15)
  * Their contribution used to appear as "1", this now is limited to "0".
* The "group grade out of 100" is *ignored* on the Tutor grading screen. 
  Instead the settings for the calculator allows the selection of a target 
  activity / gradebook item to use as the score.
* The grade awarded is reduced *not* by a percentage, but actually by 
  *percentage points". 
   
