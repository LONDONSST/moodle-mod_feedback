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
 * shows an analysed view of feedback
 *
 * @copyright Shubhendra Doiphode 2018 for LSST
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_feedback
 */

require_once("../../config.php");
require_once("lib.php");

$current_tab = 'analysiscampuswise';



$id = required_param('id', PARAM_INT);  // Course module id.

$sql_campusfield = 'SELECT id, name, param1 FROM {user_info_field} WHERE shortname="campus"';
$arr_campusfield = $DB->get_record_sql($sql_campusfield);

$campusfieldid = $arr_campusfield->id;
$campusfieldname = $arr_campusfield->name;
$param1 = $arr_campusfield->param1;

$param1_pieces = explode("\n", $param1);

$url = new moodle_url('/mod/feedback/analysiscampuswise.php', array('id'=>$id));
$PAGE->set_url($url);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'feedback');
require_course_login($course, true, $cm);

$feedback = $PAGE->activityrecord;
$feedbackstructure = new mod_feedback_structure($feedback, $cm);

$context = context_module::instance($cm->id);

//echo $cm->id . " " . $feedback->id . " " . $COURSE->id;

if (!$feedbackstructure->can_view_analysis()) {
    print_error('error');
}

/// Print the page header

$PAGE->set_heading($course->fullname);
$PAGE->set_title($feedback->name);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($feedback->name));

/// print the tabs
require('tabs.php');

?>

    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['corechart']});
        //google.charts.load('current', {packages: ['corechart', 'column']});
        google.charts.setOnLoadCallback(drawChart);

        function drawChart(divname,charttitle,barlabel,bardata,barcolors) {

            if (barlabel != undefined) {

                var data = new google.visualization.DataTable();

                data.addColumn('string', 'Response');
                data.addColumn('number', 'No. of Responses');
                data.addColumn({type: 'string', role: 'style'}); // style role col.
                data.addColumn('string', '');

                var barlabeltext = JSON.parse(barlabel);
                var bardatavalue = JSON.parse(bardata);
                var barcolorscode = JSON.parse(barcolors);

                //console.log(bardata);

                //console.log(bardatavalue.toString());

                var icount = barlabeltext.length;

                data.addRows(icount);
                var row = 0;

                for (var i = 0; i < icount; i++) {
                    data.setCell(row, 0, barlabeltext[i]);
                    //var bvalue = Math.round(bardatavalue[i] * 100) / 100;
					var bvalue = bardatavalue[i];
					bvalue = bvalue*100;
					bvalue = bvalue.toFixed(2);
					
                    data.setCell(row, 1, bardatavalue[i]);
                    data.setCell(row, 2, barcolorscode[i]);
                    //data.setCell(row, 3, Math.round(bvalue * 100)/100 + "%");
					data.setCell(row, 3, bvalue + "%");
                    row++;

                    console.log(bvalue + " // " + bardatavalue[i]);
                }

                var barview = new google.visualization.DataView(data);


                var options = {
                    //title: bartitle ,
                    //width: 720,
                    height: 420,
                    bar: {groupWidth: "50%"},
                    legend: "none",
                    hAxis: {
                        maxValue: 100
                    },
                    vAxis: {
                        format: '#%',
                        maxValue: 1
                    }

                };


                barview.setColumns([0, //The "descr column"
                    1, //Downlink column
                    {
                        calc: "stringify",
                        sourceColumn: 3, // Create an annotation column with source column "1"
                        type: "string",
                        role: "annotation"
                    },
                    2, // Uplink column
                    {
                        calc: "stringify",

                        type: "string",
                        role: "annotation"
                    }]);

                var barchart = new google.visualization.ColumnChart(document.getElementById(divname));


                // Wait for the chart to finish drawing before calling the getImageURI() method.
                google.visualization.events.addListener(barchart, 'ready', function () {
                    document.getElementById(divname).innerHTML = '<img src="' + barchart.getImageURI() + '">';
                    //console.log(document.getElementById(divname).innerHTML);
                });


                barchart.draw(barview, options);
            }

        }


        google.charts.setOnLoadCallback(drawpieChart);


        function drawpieChart(divname,pietitle,pielabel,piedata,piecolors) {
            if (pielabel != undefined) {
                var pielabeltext = JSON.parse(pielabel);
                var piedatavalue = JSON.parse(piedata);
                var piecolorscode = JSON.parse(piecolors);

                var data = new google.visualization.DataTable();

                data.addColumn('string', 'Response');
                data.addColumn('number', 'No. of Responses');

                var icount = pielabeltext.length;

                //console.log(pielabeltext.length);

                data.addRows(icount);


                for (var i = 0; i < icount; i++) {
                    var newi = i+1;

                    data.setCell(i, 0, pielabeltext[newi]);
                    data.setCell(i, 1, piedatavalue[newi]);
                }

                var options = {
                    title: '',
                    is3D: true,
                    chartArea: {width: 800, height: 800},
                    legend: {
                        position: 'labeled'
                    },
                    pieSliceText: 'none'
                };


                var chart = new google.visualization.PieChart(document.getElementById(divname));

                // Wait for the chart to finish drawing before calling the getImageURI() method.
                google.visualization.events.addListener(chart, 'ready', function () {
                    document.getElementById(divname).innerHTML = '<img src="' + chart.getImageURI() + '">';
                    //console.log(document.getElementById(divname).innerHTML);
                });

                chart.draw(data, options);
            }
        }


        function pdfcontent() {
            var fullcontenthtml = document.getElementById("fullcontent").innerHTML;

            document.getElementById("htmlpdf").innerHTML = fullcontenthtml;

            document.getElementById("btnsubmit").style.display = "block";

            document.getElementById("btnpdf").style.display = "none";

            document.getElementById("pdfanalysis").submit();

        }

    </script>

<?php
global $COURSE;

// CURRENT USER CAMPUS

$currentuser_sql = "SELECT uii.data FROM {user} u LEFT JOIN {user_info_data} uii ON (u.id=uii.userid AND fieldid=".$campusfieldid.") WHERE u.id=" . $USER->id;

$currentuser_arr = $DB->get_record_sql($currentuser_sql);


$currentuserinstitution_str=$currentuser_arr->data;

$ddcampusvalue = "";
if (isset($_POST["ddcampus"])) {
    $ddcampusvalue = $_POST["ddcampus"];
}
$feedbacklegends = array();
$barcolor_arr = array();
$countresponse = array();
$barlabels_arr = array();
$presentation_arr = array();
$qno = -1;

$barcolor_arr = array("#DC3B30","#F19833","#9B3999","#3366CC","#4D971C","#87d9e0");

$ddcampus = "<select id='ddcampus' onchange='campusselectform.submit();' name='ddcampus'>";


/*echo "<pre>";
print_r($param1_pieces);
echo "</pre>";

exit;
*/

$ddcampus .= '<OPTION value="">SELECT</OPTION>';
foreach($param1_pieces as $key_campusdata) {

    if ($key_campusdata != "ALL CAMPUSES") {
        $ddcampus .= '<OPTION value="' . $key_campusdata . '" ';
        if ($ddcampusvalue == "$key_campusdata") {
            $ddcampus .= " selected";
        }
        $ddcampus .= ' >' . $key_campusdata . '</OPTION>';
    }
}
$ddcampus .= '</SELECT>';


if (strtoupper($currentuserinstitution_str)=="ALL CAMPUSES") {
    echo "<form name='campusselectform' action='' method='post'>";
    echo "Select Campus " . $ddcampus;
    echo "</form>";
} else {
    $_POST["ddcampus"] = $currentuserinstitution_str;
    $ddcampusvalue = $currentuserinstitution_str;
    echo '<h3 style="text-align:center;">Campus : ' . $currentuserinstitution_str . '</h3>';
}



if (isset($_POST["ddcampus"]) && $_POST["ddcampus"]!="") {
    $countresponse = array();
    $feedbacknames = array();

    echo "<input style='float:right;' id='btnpdf' type='button' onclick='pdfcontent();' value='Generate PDF'>";
    echo "<input id='btnsubmit' style='display:none;float:right;' type='submit' value='Download PDF'>";

// USER
    $responseid_sql = "SELECT mfc.id FROM {feedback_completed} mfc, {user} mu, {user_info_data} uii WHERE mfc.userid = mu.id AND mfc.feedback=" . $feedback->id . " AND uii.userid=mu.id AND uii.data = '" . $ddcampusvalue . "'";

    $response = $DB->get_records_sql($responseid_sql);


//    select total user of institute start here
    $sql = "SELECT count(*) as ins_user  
FROM {user} u
JOIN {user_enrolments} ue ON ue.userid = u.id
JOIN {enrol} e ON e.id = ue.enrolid
JOIN {role_assignments} ra ON ra.userid = u.id
JOIN {context} ct ON ct.id = ra.contextid
    AND ct.contextlevel =50
JOIN {course} c ON c.id = ct.instanceid
    AND e.courseid = c.id
JOIN {role} r ON r.id = ra.roleid
    AND r.shortname =  'student'
    
left join {user_info_data} uii on u.id = uii.userid         
            
WHERE e.status =0
    AND u.suspended =0
    AND u.deleted =0
    AND ue.status =0
    AND courseid =" . $COURSE->id . " 
    AND uii.fieldid = " . $campusfieldid . " AND uii.data='".$ddcampusvalue."'";
    
//    AND u.institution='" . $ddcampusvalue . "'";

    $isn_user = $DB->get_record_sql($sql);
    $totalinsuser = $isn_user->ins_user;


//    select total user of institute end here



    $submitted_answers = count($response);

    $response_str = "";

    foreach ($response as $key_response) {
        $response_str .= $key_response->id . ",";
    }

    $response_str = rtrim($response_str,",");

    $feedback_items_sql = "SELECT id,name,position,presentation FROM {feedback_item} WHERE feedback=" . $feedback->id . " ORDER BY position";
    $feedback_items = $DB->get_records_sql($feedback_items_sql);

    $totalquestions = count($feedback_items);

    $feedback_items_id_str = "";


    if ($response_str!="") {




        foreach ($feedback_items as $key_feedback_items) {
            $feedback_items_id = $key_feedback_items->id;

            $presentation = $key_feedback_items->presentation;
            $presentation = str_replace("r>>>>>","|",$presentation);
            $presentation = str_replace("<<<<<1","",$presentation);
            $presentation_pieces = explode("|", $presentation);

            $presentation_arr[$feedback_items_id] = $presentation_pieces;
            $feedbacknames[$feedback_items_id] = $key_feedback_items->name;

            $feedback_items_id_str .= $feedback_items_id . ",";

            $feedback_value_sql = "SELECT id,value FROM {feedback_value} WHERE completed in (" . $response_str . ") AND item=" . $feedback_items_id;
            $feedback_values = $DB->get_records_sql($feedback_value_sql);


            foreach ($feedback_values as $key_feedback_values) {
                //echo $key_feedback_values->value;

                if ($key_feedback_values->value != "" && is_number($key_feedback_values->value)) {
                    $feedback_countvalue_sql = "SELECT count(*) as cntvalue FROM {feedback_value} WHERE completed in (" . $response_str . ") AND item=" . $feedback_items_id . " AND value=" . $key_feedback_values->value;
                    $feedback_countvalues = $DB->get_record_sql($feedback_countvalue_sql);

                    $countresponse[$feedback_items_id][$key_feedback_values->value] = $feedback_countvalues->cntvalue;
                }
            }

        }

        $feedback_items_id_str = rtrim($feedback_items_id_str,",");

    }

    if ($response_str!="") {
        $feedback_group_sql = "SELECT value, count(*) AS cntfbv FROM {feedback_value} WHERE completed in (" . $response_str . ") AND item in (" . $feedback_items_id_str . ") AND value in (1,2,3,4,5,6) group by value";
        $feedback_group = $DB->get_records_sql($feedback_group_sql);

        /*
        echo "<pre>";
        print_r($feedback_group);
        echo "</pre>";
        */

        $piedata_arr = array();
        $piecolor_arr = array();


        foreach ($feedback_group as $key_feedback_group) {
            $piedata_arr[$key_feedback_group->value] = $key_feedback_group->cntfbv;
            $piecolor_arr = $barcolor_arr;
            $pietitle = '';
        }
    }

    /*
    echo "<pre>";
    print_r($piedata_arr);
    echo "</pre>";
    */




    echo '<div id="fullcontent">';
    echo '<br/>';
    echo '<br/>';
    echo '<br/>';
    echo "<h3><img src='lsst_logo.jpg'></h3>";
    echo '<br/>';
    echo '<br/>';
    echo '<br/>';
    echo '<br/>';
    echo '<h3 style="text-align:center;">' . $feedback->name . '<br/>' . $COURSE->fullname . '</h3>';
    echo '<br/>';
    echo '<br/>';
    echo '<div class="feedback_info">';
    echo '<span class="feedback_info" >Campus Name: </span>' . '<span class="feedback_info_value">' . $ddcampusvalue . '</span>';
    echo '<br/>';

    echo "<span class='feedback_info' >".$submitted_answers." out of ".$totalinsuser." users from  ".$ddcampusvalue."  attempted the Feedback</span>" ;
    echo '<br/>';
    $attempted_per =0;
    if($submitted_answers) {
        $attempted_per = round($submitted_answers / ($totalinsuser / 100), 2);
    }
//    if($attempted_per =='NAN){
//        $attempted_per =0;
//    }

    echo "<span class='feedback_info' >".$attempted_per."% of the Students attempted.</span>" ;
    echo '<br/>';

//    echo '<span class="feedback_info" >Submitted answers: </span>' . '<span class="feedback_info_value">' . $submitted_answers . '</span>';
//    echo '<br/>';
    echo '<span class="feedback_info" >Questions: </span>' . '<span class="feedback_info_value">' . $totalquestions . '</span>';
    echo '</div>';
    echo '<br/>';
    echo '<br/>';

    foreach ($presentation_arr as $presentation_key=>$presentation_value) {
        $qno++;

        //echo $presentation_key . " " . $feedbacknames[$presentation_key] . "<br/>";
        $barlabels_arr = array();
        $bardata_arr_tmp = array();
        $bardata_arr = array();
        $bardata_tot = 0;
        $bardata_ind = 0;
        $feedbacklegends[0]="";

        foreach ($presentation_value as $keyof_presentation_value => $valueof_presentation_value) {
            if ($valueof_presentation_value!="") {
                $kpv = 0;

                if (isset($countresponse[$presentation_key][$keyof_presentation_value])=="") {
                    $kpv = 0;
                } else {
                    $kpv = $countresponse[$presentation_key][$keyof_presentation_value];
                }

                $barlabels_arr[] = trim($valueof_presentation_value);

                if (!is_number($valueof_presentation_value) && $valueof_presentation_value!="")
                    $feedbacklegends[$keyof_presentation_value] = trim($valueof_presentation_value);

                $bardata_arr_tmp[] = $kpv;


            }
        }


        foreach ($bardata_arr_tmp as $bardata_key_tmp=>$bardata_value_tmp) {
            $bardata_tot += $bardata_value_tmp;
        }

        if ($bardata_tot>0) {
            foreach ($bardata_arr_tmp as $bardata_key_tmp => $bardata_value_tmp) {
                $bardata_ind = ($bardata_value_tmp / ($bardata_tot)) ;
//                $bardata_arr[] = round($bardata_ind,2) ;
				$bardata_arr[] = $bardata_ind;

            }
        }

        /*
                echo "<pre>";
                print_r($bardata_arr);
                echo "</pre>";
        */

        if (isset($barlabels_arr[2])!="") {

            echo '<table class="analysis itemtype_info">';
            echo '<tbody>';
            echo '<tr>';
            echo '<th colspan="2" align="left">';
            echo '(' . $qno . ') ' . $feedbacknames[$presentation_key];
            echo '</th>';
            echo '</tr>';
            echo '<tr>';
            echo '<td colspan="2" class="">';

            //echo '<div id="itemchart_' . $presentation_key . '">' . $chartdata . '</div>';


            $barlabels = json_encode($barlabels_arr);
            $bardata = json_encode($bardata_arr);
            $barcolors = json_encode($barcolor_arr);
            $bartitle = '';

            echo '<div id="itemchart_' . $presentation_key . '" style="width: 100%; height: 400px;"></div>';
            echo "<script>
            google.charts.setOnLoadCallback(function() {
                    drawChart('itemchart_" . $presentation_key . "','" . $bartitle . "','" . $barlabels . "','" . $bardata . "', '" . $barcolors . "');
            });
            </script>";


            echo '</td>';
            echo '</tr>';
            echo '</tbody>';
            echo '</table>';
        }

    }



    $piehtml = "";
    if (!empty($piedata_arr)) {
        $piehtml .= '<table class="analysis itemtype_info">';
        $piehtml .= '<tbody>';
        $piehtml .= '<tr>';
        $piehtml .= '<th colspan="2" align="left">';
        $piehtml .= '<h3>Totals and Percentages by Score for All Questions</h3>';
        $piehtml .= '</th>';
        $piehtml .= '</tr>';
        $piehtml .= '<tr>';
        $piehtml .= '<td colspan="2" class="">';

        //echo '<div id="itemchart_' . $presentation_key . '">' . $chartdata . '</div>';

//        print_r($feedbacklegends);

        $pielabels = json_encode($feedbacklegends);
        $piedata = json_encode($piedata_arr);
        $piecolors = json_encode($barcolor_arr);
        $pietitle = '';

        $piehtml .= '<div id="piediv" style="width: 100%; height: 400px;"></div>';
        $piehtml .= "<script>
            google.charts.setOnLoadCallback(function() {
                    drawpieChart('piediv','" . $pietitle . "','" . $pielabels . "','" . $piedata . "', '" . $piecolors . "');
                    
            });
            </script>";


        $piehtml .= '</td>';
        $piehtml .= '</tr>';
        $piehtml .= '</tbody>';
        $piehtml .= '</table>';


        echo $piehtml;



    }

    echo '</div>';

} else {

}

?>

<?php

echo "<div style='width:20%; margin:0 auto;'>";
echo "<form action='analysiscampus_to_pdf.php' method='post' id='pdfanalysis' target='_blank'>";
echo "<textarea style='display:none;' id='htmlpdf' name='htmlpdf'></textarea>";
echo "</form>";
echo "</div>";

echo $OUTPUT->footer();

