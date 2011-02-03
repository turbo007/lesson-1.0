<?php
	/*****************************************************************
	 * teacher/report/class_modify.php  (c) 2008 Jonathan Dieter
	 *
	 * Show subject conduct, effort, average and comment for report
	 * Change class conduct, effort, average and comments
	 *****************************************************************/

	/* Get variables */
	if(!isset($_GET['next'])) $_GET['next'] = dbfuncString2Int($backLink);
	$class            = dbfuncInt2String($_GET['keyname']);
	$student_name     = dbfuncInt2String($_GET['keyname2']);
	$title            = "Report for " . $student_name;
	$classtermindex   = safe(dbfuncInt2String($_GET['key']));
	$student_username = safe(dbfuncInt2String($_GET['key2']));

	$link =	"index.php?location=" . dbfuncString2Int("teacher/report/class_modify_action.php") .
			"&amp;key=" .               $_GET['key'] .
			"&amp;key2=" .              $_GET['key2'] .
			"&amp;keyname=" .           $_GET['keyname'] .
			"&amp;keyname2=" .          $_GET['keyname2'] .
			"&amp;next=" .              $_GET['next'];
	if(isset($_GET['key3'])) $link .= "&amp;key3=" . $_GET['key3'];

	$extra_js      = "class_report.js";

	include "core/settermandyear.php";
	if(isset($_GET['key3'])) $termindex = safe(dbfuncInt2String($_GET['key3']));
	include "header.php";                                      // Show header

	/* Check whether subject is open for report editing */
	$query =	"SELECT classterm.AverageType, classterm.EffortType, classterm.ConductType, " .
				"       classterm.AverageTypeIndex, classterm.EffortTypeIndex, " .
				"       classterm.ConductTypeIndex, classterm.CTCommentType, " .
				"       classterm.HODCommentType, classterm.PrincipalCommentType, " .
				"       classterm.CanDoReport, classterm.AbsenceType, " .
				"       MIN(classlist.ReportDone) AS ReportDone " .
				"       FROM classterm, classlist " .
				"WHERE classterm.ClassTermIndex = $classtermindex " .
				"AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
				"GROUP BY classterm.ClassIndex";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if(!$row =& $res->fetchRow(DB_FETCHMODE_ASSOC) or (!$row['CanDoReport'] and !$row['ReportDone'])) {
		/* Print error message */
		$noJS          = true;
		$noHeaderLinks = true;
		include "header.php";                                      // Show header

		echo "      <p>Reports for this class aren't open.</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
		log_event($LOG_LEVEL_ERROR, "teacher/report/class_modify.php", $LOG_DENIED_ACCESS,
					"Tried to modify report for $subject.");

		include "footer.php";
		exit(0);
	}

	$average_type       = $row['AverageType'];
	$absence_type       = $row['AbsenceType'];
	$effort_type        = $row['EffortType'];
	$conduct_type       = $row['ConductType'];
	$ct_comment_type    = $row['CTCommentType'];
	$hod_comment_type   = $row['HODCommentType'];
	$pr_comment_type    = $row['PrincipalCommentType'];
	$can_do_report      = $row['CanDoReport'];
	$average_type_index = $row['AverageTypeIndex'];
	$effort_type_index  = $row['EffortTypeIndex'];
	$conduct_type_index = $row['ConductTypeIndex'];
	$proof_username     = $row['ProofreaderUsername'];

	/* Check whether current user is principal */
	$res =&  $db->query("SELECT Username FROM principal " .
						"WHERE Username=\"$username\" AND Level=1");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_principal = true;
	} else {
		$is_principal = false;
	}

	/* Check whether current user is a hod */
	$res =&  $db->query("SELECT hod.Username FROM hod, class, classterm " .
						"WHERE hod.Username        = '$username' " .
						"AND   hod.DepartmentIndex = class.DepartmentIndex " .
						"AND   class.ClassIndex    = classterm.ClassIndex " .
						"AND   classterm.ClassTermIndex = $classtermindex");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_hod = true;
	} else {
		$is_hod = false;
	}

	/* Check whether user is authorized to change scores */
	$res =& $db->query("SELECT class.ClassIndex FROM class, classterm " .
					   "WHERE classterm.ClassTermIndex  = $classtermindex " .
					   "AND   classterm.ClassIndex = class.ClassIndex " .
					   "AND   class.ClassTeacherUsername = '$username'");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
	
	if($res->numRows() > 0) {
		$is_ct = true;
	} else {
		$is_ct = false;
	}

	/* Check whether user is proofreader */
	if($proof_username == $username) {
		$is_proofreader = true;
	} else {
		$is_proofreader = false;
	}

	if(!$is_ct and !$is_hod and !$is_principal and !$is_admin and !$is_proofreader) {
		/* Print error message */
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
		log_event($LOG_LEVEL_ERROR, "teacher/report/class_modify.php", $LOG_DENIED_ACCESS,
					"Tried to modify class report for $student_name.");

		include "footer.php";
		exit(0);
	}

	/*update_classterm($classindex, $termindex);
	update_conduct_input($classindex, $termindex);*/

	$query =	"SELECT user.Gender, user.FirstName, user.Surname, user.Username, " .
				"       user.User1, user.User2, user.User3, " .
				"       classlist.Average, classlist.Conduct, classlist.Effort, " .
				"       classlist.Rank, classlist.CTComment, classlist.HODComment, " .
				"       classlist.CTCommentDone, classlist.HODCommentDone, " .
				"       classlist.PrincipalComment, classlist.PrincipalCommentDone, " .
				"       classlist.PrincipalUsername, classlist.HODUsername, " .
				"       classlist.ReportDone, classlist.ReportProofread, " .
				"       classlist.ReportPrinted, classlist.Absences, " .
				"       classlist.ReportProofDone, classterm.Average AS ClassAverage, " .
				"       classterm.Conduct AS ClassConduct, classterm.Effort AS ClassEffort, " .
				"       average_index.Display AS AverageDisplay, " .
				"       effort_index.Display AS EffortDisplay, " .
				"       conduct_index.Display AS ConductDisplay " .
				"       FROM user, classterm, classlist " .
				"       LEFT OUTER JOIN nonmark_index AS average_index ON " .
				"            classlist.Average = average_index.NonmarkIndex " .
				"       LEFT OUTER JOIN nonmark_index AS effort_index ON " .
				"            classlist.Effort = effort_index.NonmarkIndex " .
				"       LEFT OUTER JOIN nonmark_index AS conduct_index ON " .
				"            classlist.Conduct = conduct_index.NonmarkIndex " .
				"WHERE user.Username            = classlist.Username " .
				"AND   classlist.ClassTermIndex = $classtermindex " .
				"AND   classterm.ClassTermIndex = classlist.ClassTermIndex " .
				"AND   classlist.Username       = '$student_username' " .
				"ORDER BY user.FirstName, user.Surname, user.Username";
			
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

	if(!$row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		/* Print error message */
		echo "      <p>$student_name is not in $class.</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";

		include "footer.php";
		exit(0);
	}
	
	$student_info = $row;

	$prev_uname = "";
	$next_uname = "";

	$query = "";
	if($is_proofreader) {
		$query .=	"(SELECT user.Username, user.FirstName, user.Surname, class.Grade, " .
					"        class.ClassName, term.TermNumber " .
					"        FROM department, user, classlist, class, classterm, term " .
					" WHERE user.Username                  = classlist.Username " .
					" AND   classlist.ClassTermIndex       = classterm.ClassTermIndex " .
					" AND   classlist.ReportProofread      = 1 " .
					" AND   classlist.ReportProofDone      = 0 " .
					" AND   classterm.CanDoReport          = 1 " .
					" AND   class.ClassIndex               = classterm.ClassIndex " .
					" AND   term.TermIndex                 = classterm.TermIndex " .
					" AND   department.DepartmentIndex     = class.DepartmentIndex " .
					" AND   department.ProofreaderUsername = '$username') ";
	}
	if($is_proofreader and ($is_ct or $is_hod or $is_principal or $is_admin)) {
		$query .=	"UNION ";
	}
	if($is_ct or $is_hod or $is_principal or $is_admin) {
		$query =	"(SELECT user.Username, user.FirstName, user.Surname, class.Grade, " .
					"         class.ClassName, term.TermNumber " .
					"         FROM classterm, classlist, class, term, user " .
					" WHERE classlist.ClassTermIndex = $classtermindex " .
					" AND   classterm.ClassTermIndex = classlist.ClassTermIndex " .
					" AND   class.ClassIndex         = classterm.ClassIndex " .
					" AND   user.Username            = classlist.Username " .
					" AND   term.TermIndex           = classterm.TermIndex) ";
	}
	$query .=		"ORDER BY TermNumber, Grade, ClassName, FirstName, Surname, Username";

	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

	while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		if($row['Username'] == $student_username) {
			if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$next_uname = $row['Username'];
			}
			break;
		}
		$prev_uname = $row['Username'];
	}

	if(!is_null($average_type_index)) {
		$query =	"SELECT Input, Display FROM nonmark_index " .
					"WHERE  NonmarkTypeIndex=$average_type_index ";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$input = strtoupper($row['Input']);
			$ainput_array   = "'$input'";
			$adisplay_array = "'{$row['Display']}'";
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$input = strtoupper($row['Input']);
				$ainput_array   .= ", '$input'";
				$adisplay_array .= ", '{$row['Display']}'";
			}
		}
	} else {
		$ainput_array   = "";
		$adisplay_array = "";
	}
	if(!is_null($effort_type_index)) {
		$query =	"SELECT Input, Display FROM nonmark_index " .
					"WHERE  NonmarkTypeIndex=$effort_type_index ";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$input = strtoupper($row['Input']);
			$einput_array   = "'$input'";
			$edisplay_array = "'{$row['Display']}'";
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$einput_array   .= ", '{$row['Input']}'";
				$edisplay_array .= ", '{$row['Display']}'";
			}
		}
	} else {
		$einput_array   = "";
		$edisplay_array = "";
	}
	if(!is_null($conduct_type_index)) {
		$query =	"SELECT Input, Display FROM nonmark_index " .
					"WHERE  NonmarkTypeIndex=$conduct_type_index ";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$input = strtoupper($row['Input']);
			$cinput_array   = "'$input'";
			$cdisplay_array = "'{$row['Display']}'";
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$cinput_array   .= ", '{$row['Input']}'";
				$cdisplay_array .= ", '{$row['Display']}'";
			}
		}
	} else {
		$cinput_array   = "";
		$cdisplay_array = "";
	}

	if($ct_comment_type == $COMMENT_TYPE_MANDATORY or $ct_comment_type == $COMMENT_TYPE_OPTIONAL or
	   $hod_comment_type == $COMMENT_TYPE_MANDATORY or $hod_comment_type == $COMMENT_TYPE_OPTIONAL or
	   $pr_comment_type == $COMMENT_TYPE_MANDATORY or $pr_comment_type == $COMMENT_TYPE_OPTIONAL) {
		$query = "SELECT CommentIndex, Comment, Strength FROM comment ORDER BY CommentIndex";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		$count = 0;
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$comment = str_replace("'", "\'", $row['Comment']);
			$comment = str_replace("\"", "\\\"", $comment);
			if($row['CommentIndex'] == $count) {
				$comment_array = "'$comment'";
				$cval_array    = "'{$row['Strength']}'";
			} else {
				$comment_array = "'($count)'";
				$cval_array    = "''";
				$count += 1;
				while($row['CommentIndex'] > $count) {
					$comment_array .= ", '($count)'";
					$cval_array    .= ", ''";
					$count += 1;
				}
				$comment_array .= ", '$comment'";
				$cval_array    .= ", '{$row['Strength']}'";
			}
			while($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$comment = str_replace("'", "\'", $row['Comment']);
				$comment = str_replace("\"", "\\\"", $comment);
				$count += 1;
				while($row['CommentIndex'] > $count) {
					$comment_array .= ", '($count)'";
					$cval_array    .= ", ''";
					$count += 1;
				}
				$comment_array .= ", '$comment'";
				$cval_array    .= ", '{$row['Strength']}'";
			}
		}
	}

	$query =	"SELECT class.ClassName, class.Grade FROM class, classterm, classlist " .
				"WHERE classlist.Username       = '$student_username' " .
				"AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
				"AND   classterm.TermIndex      = $termindex " .
				"AND   classterm.ClassIndex     = class.ClassIndex " .
				"AND   class.YearIndex          = $yearindex ";
				
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$grade     = $row['Grade'];
		$classname = $row['ClassName'];
	} else {
		$grade     = -1;
		$classname = "";
	}

	$rpt_sentence = "";
	$query =	"SELECT Grade, ClassCount FROM " .
				"  (SELECT class.Grade, COUNT(DISTINCT class.YearIndex) AS ClassCount " .
				"          FROM class, classterm, classlist " .
				"   WHERE classlist.Username = '$student_username' " .
				"   AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
				"   AND   classterm.ClassIndex     = class.ClassIndex " .
				"   GROUP BY Grade) AS classcount " .
				"WHERE ClassCount > 1";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$rpt_sentence = "<p class='error' align='center'>{$student_info['FirstName']} has repeated class {$row['Grade']}";
		while($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$rpt_sentence .= " and {$row['Grade']}";
		}
		$rpt_sentence .= ".</p>";
	}

	$new_sentence = "";
	if($student_info['User1'] == 1) {
		$new_sentence = "<p align='center'>{$student_info['FirstName']} is a new student.";
	}
	
	$query =	"SELECT MAX(subject.AverageType) AS MaxAverage, " .
				"       MAX(subject.ConductType) AS MaxConduct, " .
				"       MAX(subject.CommentType) AS MaxComment, " .
				"       MAX(subject.EffortType) AS MaxEffort,  " .
				"       AVG(subjectstudent.CommentValue) AS CommentAverage, " .
				"       MIN(subjectstudent.ReportDone) AS ReportDone " .
				"       FROM subject, subjectstudent " .
				"WHERE subjectstudent.Username      = '$student_username' " .
				"AND   subjectstudent.SubjectIndex  = subject.SubjectIndex " .
				"AND   subject.TermIndex            = $termindex " .
				"AND   subject.YearIndex            = $yearindex " .
				"GROUP BY subjectstudent.Username";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

	if(!$row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		if($can_do_report) {
			echo "      <form action='$link' method='post' name='report'>\n";        // Form method
			echo "         <p align='center'>\n";
			if($prev_uname != "") {
				echo "            <input type='submit' name='student_$prev_uname' value='&lt;&lt;'>&nbsp; \n";
			}
			if($next_uname != "") {
				echo "            <input type='submit' name='student_$next_uname' value='&gt;&gt;'>&nbsp; \n";
			}
		
			echo "         </p>\n";
		} else {
			$nochangeyt = true;
			include "core/titletermyear.php";
		}

		echo "         <p align='center'>Student isn't in any subjects.</p>\n";
		if($can_do_report)
			echo "       </form>\n";
		include "footer.php";
		exit(0);
	}

	$subject_average_type = $row['MaxAverage'];
	$subject_conduct_type = $row['MaxConduct'];
	$subject_effort_type  = $row['MaxEffort'];
	$subject_comment_type = $row['MaxComment'];
	$subject_comment_avg  = $row['CommentAverage'];
	$subject_report_done  = $row['ReportDone'];

	$query =	"SELECT COUNT(TermIndex) AS TermCount, MIN(TermIndex) AS LowTerm FROM (" .
				" SELECT subject.TermIndex, 1 AS TGroup FROM " .
				"        subject, subjectstudent, term, term AS depterm " .
				" WHERE  subjectstudent.Username = '$student_username' " .
				" AND    subject.SubjectIndex    = subjectstudent.SubjectIndex " .
				" AND    subject.YearIndex       = $yearindex " .
				" AND    subject.TermIndex       = term.TermIndex " .
				" AND    subject.ShowInList      = 1 " .
				" AND   (subject.AverageType != $AVG_TYPE_NONE OR subject.EffortType != $EFFORT_TYPE_NONE OR subject.ConductType != $CONDUCT_TYPE_NONE OR subject.CommentType != $COMMENT_TYPE_NONE) " .
				" AND    term.DepartmentIndex    = depterm.DepartmentIndex " .
				" AND    term.TermIndex         <= $termindex " .
				" AND    depterm.TermIndex       = $termindex " .
				" GROUP BY subject.TermIndex) AS SubList " .
				"GROUP BY TGroup";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$termcount = $row['TermCount'];
		$lowtermindex = $row['LowTerm'];
	} else {
		$termcount = 0;
	}
	
				
	$query =	"SELECT subject.Name AS SubjectName, subject.SubjectIndex, " .
				"       subjectstudent.Average, subjectstudent.Effort, subjectstudent.Conduct, " .
				"       average_index.Display AS AverageDisplay, " .
				"       effort_index.Display AS EffortDisplay, " .
				"       conduct_index.Display AS ConductDisplay, " .
				"       subject.Average AS SubjectAverage, " .
				"       subject.AverageType, subject.EffortType, subject.ConductType, " .
				"       subject.AverageTypeIndex, subject.EffortTypeIndex, " .
				"       subject.ConductTypeIndex, subject.CommentType, " .
				"       subjectstudent.Comment, subjectstudent.CommentValue, " .
				"       subjectstudent.ReportDone, " .
				"       get_weight(subject.SubjectIndex, CURDATE()) AS SubjectWeight " .
				"       FROM subject, subjecttype, subjectstudent " .
				"       LEFT OUTER JOIN nonmark_index AS average_index ON " .
				"            subjectstudent.Average = average_index.NonmarkIndex " .
				"       LEFT OUTER JOIN nonmark_index AS effort_index ON " .
				"            subjectstudent.Effort = effort_index.NonmarkIndex " .
				"       LEFT OUTER JOIN nonmark_index AS conduct_index ON " .
				"            subjectstudent.Conduct = conduct_index.NonmarkIndex " .
				"WHERE subjectstudent.Username      = '$student_username' " .
				"AND   subjectstudent.SubjectIndex  = subject.SubjectIndex " .
				"AND   subject.TermIndex            = $termindex " .
				"AND   subject.YearIndex            = $yearindex " .
				"AND   subject.ShowInList           = 1 " .
				"AND   (subject.AverageType != $AVG_TYPE_NONE OR subject.EffortType != $EFFORT_TYPE_NONE OR subject.ConductType != $CONDUCT_TYPE_NONE OR subject.CommentType != $COMMENT_TYPE_NONE) " .
				"AND   subjecttype.SubjectTypeIndex = subject.SubjectTypeIndex " .
				"ORDER BY subjecttype.HighPriority DESC, get_weight(subject.SubjectIndex, CURDATE()) DESC, " .
				"         subjecttype.Title, subject.Name, subject.TermIndex DESC, subject.SubjectIndex ";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

	$gender = strtolower($student_info['Gender']);

	if($can_do_report) {
		echo "      <script language='JavaScript' type='text/javascript'>\n";
		echo "         var CONDUCT_TYPE_NONE      = $CONDUCT_TYPE_NONE;\n";
		echo "         var CONDUCT_TYPE_PERCENT   = $CONDUCT_TYPE_PERCENT;\n";
		echo "         var CONDUCT_TYPE_INDEX     = $CONDUCT_TYPE_INDEX;\n";
		echo "         var EFFORT_TYPE_NONE       = $EFFORT_TYPE_NONE;\n";
		echo "         var EFFORT_TYPE_PERCENT    = $EFFORT_TYPE_PERCENT;\n";
		echo "         var EFFORT_TYPE_INDEX      = $EFFORT_TYPE_INDEX;\n";
		echo "         var COMMENT_TYPE_NONE      = $COMMENT_TYPE_NONE;\n";
		echo "         var COMMENT_TYPE_MANDATORY = $COMMENT_TYPE_MANDATORY;\n";
		echo "         var COMMENT_TYPE_OPTIONAL  = $COMMENT_TYPE_OPTIONAL;\n";
		echo "         var ABSENCE_TYPE_NONE      = $ABSENCE_TYPE_NONE;\n";
		echo "         var ABSENCE_TYPE_NUM       = $ABSENCE_TYPE_NUM;\n";
		echo "         var ABSENCE_TYPE_CALC      = $ABSENCE_TYPE_CALC;\n";
		echo "         var AVERAGE_TYPE_NONE      = $AVG_TYPE_NONE;\n";
		echo "         var AVERAGE_TYPE_PERCENT   = $AVG_TYPE_PERCENT;\n";
		echo "         var AVERAGE_TYPE_INDEX     = $AVG_TYPE_INDEX;\n";
		echo "         var AVERAGE_TYPE_GRADE     = $AVG_TYPE_GRADE;\n";
		echo "\n";
		echo "         var average_type           = $average_type;\n";
		if($average_type == $AVG_TYPE_INDEX) {
			echo "         var average_input_array    = new Array($ainput_array);\n";
			echo "         var average_display_array  = new Array($adisplay_array);\n";
		}
		echo "\n";
		echo "         var effort_type            = $effort_type;\n";
		if($effort_type == $EFFORT_TYPE_INDEX) {
			echo "         var effort_input_array     = new Array($einput_array);\n";
			echo "         var effort_display_array   = new Array($edisplay_array);\n";
		}
		echo "\n";
		echo "         var conduct_type           = $conduct_type;\n";
		if($conduct_type == $CONDUCT_TYPE_INDEX) {
			echo "         var conduct_input_array    = new Array($cinput_array);\n";
			echo "         var conduct_display_array  = new Array($cdisplay_array);\n";
		}
		echo "\n";
		echo "         var absence_type           = $absence_type;\n";
		echo "\n";
		echo "         var ct_comment_type        = $ct_comment_type;\n";
		echo "         var hod_comment_type       = $hod_comment_type;\n";
		echo "         var pr_comment_type        = $pr_comment_type;\n";
		if($ct_comment_type == $COMMENT_TYPE_MANDATORY or $ct_comment_type == $COMMENT_TYPE_OPTIONAL or $hod_comment_type == $COMMENT_TYPE_MANDATORY or $hod_comment_type == $COMMENT_TYPE_OPTIONAL or $pr_comment_type == $COMMENT_TYPE_MANDATORY or $pr_comment_type == $COMMENT_TYPE_OPTIONAL) {
			echo "         var comment_array          = new Array($comment_array);\n";
		}
		echo "         var gender                  = '{$student_info['Gender']}';\n";
		echo "         var firstname               = '{$student_info['FirstName']}';\n";
		echo "         var fullname                = '{$student_info['FirstName']} {$student_info['Surname']}';\n";
		echo "         var grade                   = '$grade';\n";
		echo "      </script>\n";

		echo "      <form action='$link' method='post' name='report'>\n";        // Form method
		
		echo "         <p align='center'>\n";
		if($prev_uname != "") {
			echo "            <input type='hidden' name='studentprev' value='$prev_uname'>\n";
			echo "            <input type='submit' name='student_$prev_uname' value='&lt;&lt;'>&nbsp; \n";
		}
		if(!$student_info['ReportDone']) {
			echo "            <input type='submit' name='action' value='Update'>&nbsp; \n";
		}
		if((($is_hod       and $hod_comment_type != $COMMENT_TYPE_NONE
						   and !$student_info['HODCommentDone']) or
			($is_principal and $pr_comment_type  != $COMMENT_TYPE_NONE
						   and !$student_info['PrincipalCommentDone']) or
			($is_ct        and $ct_comment_type  != $COMMENT_TYPE_NONE
						   and !$student_info['CTCommentDone'])) and
			!$student_info['ReportDone']) {
			echo "            <input type='submit' name='action' value='Finished with comments'>&nbsp; \n";
		}
		if(($student_info['CTCommentDone'] or
			($student_info['HODCommentDone'] and ($is_admin or $is_hod or $is_principal or $is_proofreader)) or
			($student_info['PrincipalCommentDone'] and ($is_admin or $is_principal or $is_proofreader))) and
		!$student_info['ReportDone']) {
			echo "            <input type='submit' name='action' value='Edit comments'>&nbsp; \n";
		}
		if(($is_hod or $is_principal or $is_admin) and !$student_info['ReportDone']) {
			echo "            <input type='submit' name='action' value='Close report'>&nbsp; \n";
		}
		if($student_info['ReportDone'] and ($is_admin or $is_principal)) {
			echo "            <input type='submit' name='action' value='Open report'>&nbsp; \n";
		}
		if($is_proofreader) {
			echo "            <input type='submit' name='action' value='Done with report'>&nbsp; \n";
		}
		echo "            <input type='submit' name='action' value='Cancel'>\n";
		if($next_uname != "") {
			echo "            <input type='submit' name='student_$next_uname' value='&gt;&gt;'>&nbsp; \n";
			echo "            <input type='hidden' name='studentnext' value='$next_uname'>\n";
		}
	
		echo "         </p>\n";
	} else {
		$nochangeyt = true;
		include "core/titletermyear.php";
	}

	echo $rpt_sentence;
	echo $new_sentence;
	echo "         <table align='center' border='1'>\n"; // Table headers
	echo "            <tr>\n";
	echo "               <th>Subject</th>\n";
	if($is_ct or $is_admin or $is_principal or $is_hod) {
		if($subject_average_type != $AVG_TYPE_NONE) {
			echo "               <th>Weight</th>\n";
			$query =	"SELECT TermName FROM term " .
						"WHERE TermIndex >= $lowtermindex " .
						"AND   TermIndex <= $termindex ".
						"ORDER BY term.TermNumber ASC";
			$nres =&  $db->query($query);
			if(DB::isError($nres)) die($nres->getDebugInfo());           // Check for errors in query
			while($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
				$name = htmlentities($nrow['TermName']);
				$pname = htmlentities(substr($nrow['TermName'], 0, 1));
				echo "               <th><a title='$name'>$pname</a></th>\n";
			}
			echo "               <th><a title='Average'>A</a></th>\n";
		}
		if($subject_effort_type != $EFFORT_TYPE_NONE) {
			echo "               <th>Effort</th>\n";
		}
		if($subject_conduct_type != $CONDUCT_TYPE_NONE) {
			echo "               <th>Conduct</th>\n";
		}
	}
	if($subject_comment_type != $COMMENT_TYPE_NONE) {
		echo "               <th>Comment</th>\n";
		echo "               <th>Tone</th>\n";
	}
	echo "               <th>Finished</th>\n";
	if(!$student_info['ReportDone']) {
		echo "               <th>&nbsp;</th>\n";
	}

	echo "            </tr>\n";
	
	/* For each student, print a row with the student's name and score on each report*/
	$alt_count   = 0;
	
	while($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {		
		$alt_count   += 1;
		
		if($alt_count % 2 == 0) {
			$alt = " class='alt'";
		} else {
			$alt = " class='std'";
		}
	
	
		echo "            <tr$alt id='row_{$row['SubjectIndex']}'>\n";
		echo "               <td nowrap>{$row['SubjectName']}</td>\n";

		if($subject_average_type != $AVG_TYPE_NONE) {
			echo "               <td nowrap>{$row['SubjectWeight']}</td>\n";
		}

		if($is_admin or $is_ct or $is_hod or $is_principal) {
			if($subject_average_type != $AVG_TYPE_NONE) {
				$subject_name = safe($row['SubjectName']);
				$query =	"SELECT subject.AverageType, subject.AverageTypeIndex, subjectstudent.Average, " .
							"       subject.EffortType, subject.EffortTypeIndex, subjectstudent.Effort, " .
							"       subject.ConductType, subject.ConductTypeIndex, subjectstudent.Conduct, " .
							"       subject.CommentType, subjectstudent.Comment, subject.Average AS SubjectAverage, " .
							"       average_index.Display AS AverageDisplay, " .
							"       effort_index.Display AS EffortDisplay, " .
							"       conduct_index.Display AS ConductDisplay, " .
							"		subject.TermIndex " .
							" FROM " .
							" (term INNER JOIN term AS depterm " .
							"       ON  term.DepartmentIndex = depterm.DepartmentIndex" .
							"       AND depterm.TermIndex = $termindex" .
							"       AND term.TermIndex <= $termindex) " .
							" LEFT OUTER JOIN " .
							" (subjectstudent INNER JOIN subject " .
							"       ON  subjectstudent.Username = '$student_username' " .
							"       AND subjectstudent.SubjectIndex = subject.SubjectIndex " .
							"       AND subject.YearIndex = $yearindex " .
							"       AND subject.Name = '$subject_name' " .
							"       AND subject.ShowInList = 1 " .
							"       AND (subject.AverageType != $AVG_TYPE_NONE OR subject.EffortType != $EFFORT_TYPE_NONE OR subject.ConductType != $CONDUCT_TYPE_NONE OR subject.CommentType != $COMMENT_TYPE_NONE)) " .
							" ON term.TermIndex = subject.TermIndex " .
							" LEFT OUTER JOIN nonmark_index AS average_index ON " .
							"       subjectstudent.Average = average_index.NonmarkIndex " .
							" LEFT OUTER JOIN nonmark_index AS effort_index ON " .
							"       subjectstudent.Effort = effort_index.NonmarkIndex " .
							" LEFT OUTER JOIN nonmark_index AS conduct_index ON " .
							"       subjectstudent.Conduct = conduct_index.NonmarkIndex " .
							"ORDER BY term.TermNumber ASC";
				$dres =&  $db->query($query);
				if(DB::isError($dres)) die($dres->getDebugInfo());           // Check for errors in query
				
				$average = 0;
				$average_max = 0;
				$subj_average = 0;
				$subj_average_max = 0;
				
				while($drow =& $dres->fetchRow(DB_FETCHMODE_ASSOC)) {
					if($drow['AverageType'] == $AVG_TYPE_NONE) {
						$score = "N/A";
					} elseif($drow['AverageType'] == $AVG_TYPE_PERCENT) {
						if($drow['Average'] == -1 or is_null($drow['Average'])) {
							$score = "-";
						} else {
							$scorestr     = round($drow['Average']);
							$average     += $scorestr;
							$average_max += 100;
							
							if($scorestr < 60) {
								$color = "#CC0000";
							} elseif($scorestr < 75) {
								$color = "#666600";
							} elseif($scorestr < 90) {
								$color = "#000000";
							} else {
								$color = "#339900";
							}
							$score = "<span style='color: $color'>$scorestr</span>";
						}
						if($drow['SubjectAverage'] != -1) {
							$subjscore         = round($drow['SubjectAverage']);
							$subj_average     += $subjscore;
							$subj_average_max += 100;
							
							$score ="<b>$score</b> ($subjscore)";
						}
					} elseif($drow['AverageType'] == $AVG_TYPE_INDEX or $drow['AverageType'] == $AVG_TYPE_GRADE) {
						if(is_null($drow['AverageDisplay'])) {
							$score = "-";
						} else {
							$score = $drow['AverageDisplay'];
						}
					} else {
						$score = "N/A";
					}
					if ($drow['TermIndex'] != $termindex) {
						$score = str_replace("<b>", "", $score);
						$score = str_replace("</b>", "", $score);
					}
					echo "               <td nowrap>$score</td>\n";
				}
				if($average_max > 0) {
					$scorestr = round($average * 100 / $average_max);
					if($scorestr < 60) {
						$color = "#CC0000";
					} elseif($scorestr < 75) {
						$color = "#666600";
					} elseif($scorestr < 90) {
						$color = "#000000";
					} else {
						$color = "#339900";
					}
					$score = "<span style='color: $color'>$scorestr</span>";
				} else {
					$score = "-";
				}
				if($subj_average_max > 0) {
					$subjscore = round($subj_average * 100 / $subj_average_max);
					$score ="<i>$score</i> ($subjscore)";
				} else {
					$score = "<i>$score</i>";
				}
				echo "               <td nowrap>$score</td>\n";
			}
	
			if($subject_effort_type != $EFFORT_TYPE_NONE) {
				if($row['EffortType'] == $EFFORT_TYPE_NONE) {
					$score = "N/A";
				} elseif($row['EffortType'] == $EFFORT_TYPE_PERCENT) {
					if($row['Effort'] == -1) {
						$score = "-";
					} else {
						$score = round($row['Effort']);
						$score = "$score%";
					}
				} elseif($row['EffortType'] == $EFFORT_TYPE_INDEX) {
					if(is_null($row['EffortDisplay'])) {
						$score = "-";
					} else {
						$score = $row['EffortDisplay'];
					}
				} else {
					$score = "N/A";
				}
				echo "               <td nowrap>$score</td>\n";
			}
	
			if($subject_conduct_type != $CONDUCT_TYPE_NONE) {
				if($row['ConductType'] == $CONDUCT_TYPE_NONE) {
					$score = "N/A";
				} elseif($row['ConductType'] == $CONDUCT_TYPE_PERCENT) {
					if($row['Conduct'] == -1) {
						$score = "-";
					} else {
						$score = round($row['Conduct']);
						$score = "$score%";
					}
				} elseif($row['ConductType'] == $CONDUCT_TYPE_INDEX) {
					if(is_null($row['ConductDisplay'])) {
						$score = "-";
					} else {
						$score = $row['ConductDisplay'];
					}
				} else {
					$score = "N/A";
				}
				echo "               <td nowrap>$score</td>\n";
			}
		}

		if($subject_comment_type != $COMMENT_TYPE_NONE) {
			if($row['CommentType'] == $COMMENT_TYPE_MANDATORY or $row['CommentType'] == $COMMENT_TYPE_OPTIONAL) {
				if(!is_null($row['Comment'])) {
					$commentstr = htmlspecialchars($row['Comment'], ENT_QUOTES);
				} else {
					$commentstr = "";
				}
				$cshow = "&nbsp;";
				if(!is_null($row['CommentValue'])) {
					$cval = round($row['CommentValue']);
					if($cval == 1) {
						$cshow = "-";
					} elseif($cval == 2) {
						$cshow = "=";
					} elseif($cval == 3) {
						$cshow = "+";
					}
				}
				echo "               <td>$commentstr</td>\n";
				echo "               <td>$cshow</td>\n";
			} else {
				echo "               <td colspan='2'>N/A</td>\n";
			}
		}
		if($row['ReportDone'] == 0) {
			echo "               <td nowrap><b>No</b></td>\n";
		} else {
			echo "               <td nowrap><i>Yes</i></td>\n";
		}
		if(!$student_info['ReportDone']) {
			echo "               <td nowrap><input type='submit' name='edit_{$row['SubjectIndex']}' value='Change'></td>\n";
		}
		echo "            </tr>\n";
	}
	echo "         </table>\n";               // End of table



	echo "         <table class='transparent' align='center' width=600px>\n";
	if($is_admin or $is_ct or $is_hod or $is_principal) {
		if($average_type != $CLASS_AVG_TYPE_NONE) {
			echo "            <tr>\n";
			echo "               <td>Average:</td>\n";
	
			/* Check for type of average mark and put in appropriate information */
			if($average_type != $CLASS_AVG_TYPE_NONE) {
				if($average_type == $CLASS_AVG_TYPE_PERCENT) {
					if(isset($_POST["average"])) {
						$scorestr = $_POST["average"];
						if(strval(intval($scorestr)) != $scorestr) {
							$score = "N/A";
						} elseif(intval($scorestr) > 100) {
							$score = "100%";
						} elseif(intval($scorestr) < 0) {
							$score = "0%";
						} else {
							$score = "$scorestr%";
						}
					} else {
						if($student_info['Average'] == -1) {
							$scorestr = "";
							$score = "N/A";
						} else {
							$scorestr = round($student_info['Average']);
							$score = "$scorestr%";
						}
					}
				} elseif($average_type == $CLASS_AVG_TYPE_INDEX) {
					if(isset($_POST["average"])) {
						$scorestr = safe($_POST["average"]);
						$query =	"SELECT Display FROM nonmark_index " .
									"WHERE Input='$scorestr' " .
									"AND   NonmarkTypeIndex=$average_type_index";
						$nres =& $db->query($query);
						if(DB::isError($nres)) die($nres->getDebugInfo());
		
						if($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
							$score = $nrow['Display'];
						} else {
							$score = "N/A";
						}
					} else {
						$scoreindex = $student_info['Average'];
						$query =	"SELECT Input, Display FROM nonmark_index " .
									"WHERE NonmarkIndex=$scoreindex";
						$nres =& $db->query($query);
						if(DB::isError($nres)) die($nres->getDebugInfo());
		
						if($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
							$scorestr = $nrow['Input'];
							$score    = $nrow['Display'];
						} else {
							$scorestr = "";
							$score    = "N/A";
						}
					}
				} elseif($average_type == $CLASS_AVG_TYPE_CALC) {
					if($student_info['Average'] == -1) {
						$score = "N/A";
					} else {
						$scorestr = round($student_info['Average']);
						if($scorestr < 60) {
							$color = "#CC0000";
						} elseif($scorestr < 75) {
							$color = "#666600";
						} elseif($scorestr < 90) {
							$color = "#000000";
						} else {
							$color = "#339900";
						}
						$score = "<span style='color: $color'>$scorestr%</span>";
					}
					if($student_info['ClassAverage'] == -1) {
						$score = "<b>$score</b> (N/A)";
					} else {
						$scorestr = round($student_info['ClassAverage']);
						$score = "<b>$score</b> ($scorestr%)";
					}
					
				} else {
					$score = "N/A";
				}
				if(($average_type == $CLASS_AVG_TYPE_INDEX or $average_type == $CLASS_AVG_TYPE_PERCENT) and !$student_info['ReportDone']) {
					echo "               <td><input type='text' name='average' " .
										"id='average' value='$scorestr' size='4' onChange='recalc_avg();'> = <label name='aavg' id='aavg' for='average'>$score</label</td>\n";
				} else {
					echo "               <td>$score</td>\n";
				}
			}
			echo "            </tr>\n";
		}
		if($average_type == $CLASS_AVG_TYPE_CALC) {
			echo "            <tr>\n";
			echo "               <td>Rank:</td>\n";
			if($student_info['Rank'] == -1) {
				$rank = "N/A";
			} else {
				$rank = htmlspecialchars($student_info['Rank']);
			}
			echo "               <td><b>$rank</b></td>\n";
			echo "            </tr>\n";
		}
				
		if($effort_type != $CLASS_EFFORT_TYPE_NONE) {
			echo "            <tr>\n";
			echo "               <td>Effort:</td>\n";
	
			/* Check for type of effort mark and put in appropriate information */
			if($effort_type != $CLASS_EFFORT_TYPE_NONE) {
				if($effort_type == $CLASS_EFFORT_TYPE_PERCENT) {
					if(isset($_POST["effort"])) {
						$scorestr = $_POST["effort"];
						if(strval(intval($scorestr)) != $scorestr) {
							$score = "N/A";
						} elseif(intval($scorestr) > 100) {
							$score = "100%";
						} elseif(intval($scorestr) < 0) {
							$score = "0%";
						} else {
							$score = "$scorestr%";
						}
					} else {
						if($student_info['Effort'] == -1) {
							$scorestr = "";
							$score = "N/A";
						} else {
							$scorestr = round($student_info['Effort']);
							$score = "$scorestr%";
						}
					}
				} elseif($effort_type == $CLASS_EFFORT_TYPE_INDEX) {
					if(isset($_POST["effort"])) {
						$scorestr = safe($_POST["effort"]);
						$query =	"SELECT Display FROM nonmark_index " .
									"WHERE Input='$scorestr' " .
									"AND   NonmarkTypeIndex=$effort_type_index";
						$nres =& $db->query($query);
						if(DB::isError($nres)) die($nres->getDebugInfo());
		
						if($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
							$score = $nrow['Display'];
						} else {
							$score = "N/A";
						}
					} else {
						$scoreindex = $student_info['Effort'];
						$query =	"SELECT Input, Display FROM nonmark_index " .
									"WHERE NonmarkIndex=$scoreindex";
						$nres =& $db->query($query);
						if(DB::isError($nres)) die($nres->getDebugInfo());
		
						if($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
							$scorestr = $nrow['Input'];
							$score    = $nrow['Display'];
						} else {
							$scorestr = "";
							$score    = "N/A";
						}
					}
				} elseif($effort_type == $CLASS_EFFORT_TYPE_CALC) {
					if($student_info['Effort'] == -1) {
						$score = "N/A";
					} else {
						$scorestr = round($student_info['Effort']);
						$score = "$scorestr%";
					}
					if($student_info['ClassEffort'] == -1) {
						$score = "<b>$score</b> (N/A)";
					} else {
						$scorestr = round($student_info['ClassEffort']);
						$score = "<b>$score</b> ($scorestr%)";
					}
				} else {
					$score = "N/A";
				}
				if(($effort_type == $CLASS_EFFORT_TYPE_INDEX or $effort_type == $CLASS_EFFORT_TYPE_PERCENT) and !$student_info['ReportDone']) {
					echo "               <td><input type='text' name='effort' " .
										"id='effort' value='$scorestr' size='4' onChange='recalc_effort();'> = <label name='eavg' id='eavg' for='effort'>$score</label</td>\n";
				} else {
					echo "               <td>$score</td>\n";
				}
			}
			echo "            </tr>\n";
		}
		if($conduct_type != $CLASS_CONDUCT_TYPE_NONE) {
			echo "            <tr>\n";
			echo "               <td>Conduct:</td>\n";
	
			/* Check for type of conduct mark and put in appropriate information */
			if($conduct_type != $CLASS_CONDUCT_TYPE_NONE) {
				if($conduct_type == $CLASS_CONDUCT_TYPE_PERCENT) {
					if(isset($_POST["conduct"])) {
						$scorestr = $_POST["conduct"];
						if(strval(intval($scorestr)) != $scorestr) {
							$score = "N/A";
						} elseif(intval($scorestr) > 100) {
							$score = "100%";
						} elseif(intval($scorestr) < 0) {
							$score = "0%";
						} else {
							$score = "$scorestr%";
						}
					} else {
						if($student_info['Conduct'] == -1) {
							$scorestr = "";
							$score = "N/A";
						} else {
							$scorestr = round($student_info['Conduct']);
							$score = "$scorestr%";
						}
					}
				} elseif($conduct_type == $CLASS_CONDUCT_TYPE_INDEX) {
					if(isset($_POST["conduct"])) {
						$scorestr = safe($_POST["conduct"]);
						$query =	"SELECT Display FROM nonmark_index " .
									"WHERE Input='$scorestr' " .
									"AND   NonmarkTypeIndex=$conduct_type_index";
						$nres =& $db->query($query);
						if(DB::isError($nres)) die($nres->getDebugInfo());
		
						if($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
							$score = $nrow['Display'];
						} else {
							$score = "N/A";
						}
					} else {
						$scoreindex = $student_info['Conduct'];
						$query =	"SELECT Input, Display FROM nonmark_index " .
									"WHERE NonmarkIndex=$scoreindex";
						$nres =& $db->query($query);
						if(DB::isError($nres)) die($nres->getDebugInfo());
		
						if($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
							$scorestr = $nrow['Input'];
							$score    = $nrow['Display'];
						} else {
							$scorestr = "";
							$score    = "N/A";
						}
					}
				} elseif($conduct_type == $CLASS_CONDUCT_TYPE_CALC or $conduct_type = $CLASS_CONDUCT_TYPE_PUN) {
					if($student_info['Conduct'] == -1) {
						$score = "N/A";
					} else {
						$scorestr = round($student_info['Conduct']);
						if($scorestr < 60) {
							$color = "#CC0000";
						} elseif($scorestr < 75) {
							$color = "#666600";
						} elseif($scorestr < 90) {
							$color = "#000000";
						} else {
							$color = "#339900";
						}
						$score = "<span style='color: $color'>$scorestr%</span>";
					}
					if($student_info['ClassConduct'] == -1) {
						$score = "<b>$score</b> (N/A)";
					} else {
						$scorestr = round($student_info['ClassConduct']);
						$score = "<b>$score</b> ($scorestr%)";
					}
				} else {
					$score = "N/A";
				}
				if(($conduct_type == $CLASS_CONDUCT_TYPE_INDEX or $conduct_type == $CLASS_CONDUCT_TYPE_PERCENT) and !$student_info['ReportDone']) {
					echo "               <td><input type='text' name='conduct' " .
										"id='conduct' value='$scorestr' size='4' onChange='recalc_conduct();'> = <label name='cavg' id='cavg' for='conduct'>$score</label</td>\n";
				} else {
					echo "               <td>$score</td>\n";
				}
			}
			echo "            </tr>\n";
		}
		if($absence_type != $ABSENCE_TYPE_NONE) {
			echo "            <tr>\n";
			echo "               <td>Absences:</td>\n";
	
			/* Check for type of absences mark and put in appropriate information */
			if($absence_type != $ABSENCE_TYPE_NONE) {
				if($absence_type == $ABSENCE_TYPE_NUM) {
					if(isset($_POST["absences"])) {
						$scorestr = $_POST["absences"];
						if(strval(intval($scorestr)) != $scorestr) {
							$score = "N/A";
						} elseif(intval($scorestr) < 0) {
							$score = "0";
						} else {
							$score = "$scorestr";
						}
					} else {
						if($student_info['Absences'] == -1) {
							$scorestr = "";
							$score = "N/A";
						} else {
							$scorestr = $student_info['Absences'];
							$score = "$scorestr";
						}
					}
				} elseif($absence_type == $ABSENCE_TYPE_CALC) {
					$nquery =	"SELECT AttendanceTypeIndex, COUNT(AttendanceIndex) AS Count " .
								"       FROM attendance INNER JOIN subject USING (SubjectIndex) " .
								"       INNER JOIN period USING (PeriodIndex) " .
								"WHERE  attendance.Username = '$student_username' " .
								"AND    subject.YearIndex = $yearindex " .
								"AND    subject.TermIndex = $termindex " .
								"AND    period.Period = 1 " .
								"AND    attendance.AttendanceTypeIndex > 0 " .
								"GROUP BY AttendanceTypeIndex ";
					$cRes =&   $db->query($nquery);
					if(DB::isError($cRes)) die($cRes->getDebugInfo());          // Check for errors in query
					while($cRow =& $cRes->fetchrow(DB_FETCHMODE_ASSOC)) {
						if($cRow['AttendanceTypeIndex'] == $ATT_ABSENT)    $absent    = $cRow['Count'];
						if($cRow['AttendanceTypeIndex'] == $ATT_LATE)      $late      = $cRow['Count'];
						if($cRow['AttendanceTypeIndex'] == $ATT_SUSPENDED) $suspended = $cRow['Count'];
					}
					$score = $absent + $suspended;
					$score = "<b>$score</b>";
				} else {
					$score = "N/A";
				}
				if(($absence_type == $ABSENCE_TYPE_NUM) and !$student_info['ReportDone']) {
					echo "               <td><input type='text' name='absences' " .
										"id='absences' value='$scorestr' size='4' onChange='recalc_absences();'> = <label name='abavg' id='abavg' for='absences'>$score</label</td>\n";
				} else {
					echo "               <td>$score</td>\n";
				}
			}
			echo "            </tr>\n";
		}
	}

	if($ct_comment_type != $COMMENT_TYPE_NONE) {
		echo "            <tr>\n";
		echo "               <td colspan='2'><b>Class Teacher's comment:</b><br>\n";
		if(isset($_POST["ct_comment"])) {
			$commentstr = htmlspecialchars($_POST["ct_comment"], ENT_QUOTES);
		} else {
			$commentstr = htmlspecialchars($student_info['CTComment'], ENT_QUOTES);
		}
		if(($ct_comment_type == $COMMENT_TYPE_MANDATORY or
		    $ct_comment_type == $COMMENT_TYPE_OPTIONAL) and
		   !$student_info['ReportDone'] and
		   !$student_info['CTCommentDone']) {
			echo "               <textarea name='ct_comment' " .
									"id='ct_comment' rows='5' cols='80' " .
									"onChange='recalc_comment(&quot;ct&quot;);'>$commentstr</textarea>\n";
		} else {
			echo "               $commentstr\n";
		}
		echo "               </td>\n";
		echo "            </tr>\n";
	}
	if($hod_comment_type != $COMMENT_TYPE_NONE) {
		echo "            <tr>\n";
		echo "               <td colspan='2'><b>Head of Department's comment:</b><br>\n";
		if(isset($_POST["hod_comment"])) {
			$commentstr = htmlspecialchars($_POST["hod_comment"], ENT_QUOTES);
		} else {
			$commentstr = htmlspecialchars($student_info['HODComment'], ENT_QUOTES);
		}
		if(($hod_comment_type == $COMMENT_TYPE_MANDATORY or
		    $hod_comment_type == $COMMENT_TYPE_OPTIONAL) and
		   ($is_admin or $is_hod or $is_principal or $is_proofreader) and
		   !$student_info['ReportDone'] and
		   !$student_info['HODCommentDone']) {
			echo "               <textarea name='hod_comment' " .
									"id='hod_comment' rows='5' cols='80' " .
									"onChange='recalc_comment(&quot;hod&quot;);'>$commentstr</textarea>\n";
		} else {
			echo "               $commentstr\n";
		}
		echo "               </td>\n";
		echo "            </tr>\n";
	}
	if($pr_comment_type != $COMMENT_TYPE_NONE) {
		echo "            <tr>\n";
		echo "               <td colspan='2'><b>Principal's comment:</b><br>\n";
		if(isset($_POST["pr_comment"])) {
			$commentstr = htmlspecialchars($_POST["pr_comment"], ENT_QUOTES);
		} else {
			$commentstr = htmlspecialchars($student_info['PrincipalComment'], ENT_QUOTES);
		}
		if(($pr_comment_type == $COMMENT_TYPE_MANDATORY or
		    $pr_comment_type == $COMMENT_TYPE_OPTIONAL) and
		   ($is_admin or $is_principal or $is_proofreader) and
		   !$student_info['ReportDone'] and
		   !$student_info['PrincipalCommentDone']) {
			echo "               <textarea name='pr_comment' " .
									"id='pr_comment' rows='5' cols='80' " .
									"onChange='recalc_comment(&quot;pr&quot;);'>$commentstr</textarea>\n";
		} else {
			echo "               $commentstr\n";
		}
		echo "               </td>\n";
		echo "            </tr>\n";
	}
	echo "            </tr>\n";
	echo "         </table>\n";
	if($can_do_report) {
		echo "         <p></p>\n";
		echo "         <p align='center'>\n";
		if($prev_uname != "") {
			echo "            <input type='hidden' name='studentprev' value='$prev_uname'>\n";
			echo "            <input type='submit' name='student_$prev_uname' value='&lt;&lt;'>&nbsp; \n";
		}
		if(!$student_info['ReportDone']) {
			echo "            <input type='submit' name='action' value='Update'>&nbsp; \n";
		}
		if((($is_hod       and $hod_comment_type != $COMMENT_TYPE_NONE
						   and !$student_info['HODCommentDone']) or
			($is_principal and $pr_comment_type  != $COMMENT_TYPE_NONE
						   and !$student_info['PrincipalCommentDone']) or
			($is_ct        and $ct_comment_type  != $COMMENT_TYPE_NONE
						   and !$student_info['CTCommentDone'])) and
			!$student_info['ReportDone']) {
			echo "            <input type='submit' name='action' value='Finished with comments'>&nbsp; \n";
		}
		if(($student_info['CTCommentDone'] or
			($student_info['HODCommentDone'] and ($is_admin or $is_hod or $is_principal or $is_proofreader)) or
			($student_info['PrincipalCommentDone'] and ($is_admin or $is_principal or $is_proofreader))) and
		!$student_info['ReportDone']) {
			echo "            <input type='submit' name='action' value='Edit comments'>&nbsp; \n";
		}
		if(($is_hod or $is_principal or $is_admin) and !$student_info['ReportDone']) {
			echo "            <input type='submit' name='action' value='Close report'>&nbsp; \n";
		}
		if($student_info['ReportDone'] and ($is_admin or $is_principal)) {
			echo "            <input type='submit' name='action' value='Open report'>&nbsp; \n";
		}
		if($is_proofreader) {
			echo "            <input type='submit' name='action' value='Done with report'>&nbsp; \n";
		}
		echo "            <input type='submit' name='action' value='Cancel'>\n";
		if($next_uname != "") {
			echo "            <input type='submit' name='student_$next_uname' value='&gt;&gt;'>&nbsp; \n";
			echo "            <input type='hidden' name='studentnext' value='$next_uname'>\n";
		}
	
		echo "         </p>\n";
		echo "       </form>\n";
	}

	include "footer.php";
?>
