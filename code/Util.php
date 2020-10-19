<?php

require_once('dao/EventDAO.php');
require_once('dao/RoomDAO.php');

function escape($string) {
	return nl2br(htmlentities($string));
}

class SessionContext {
	private static $isCreated = false;
	
	public static function create() {
		if (!self::$isCreated) {
			self::$isCreated = session_start();
		}
		return self::$isCreated;
	}
}

function redirect($page = null) {
	if ($page == null) {
		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : $_SERVER['REQUEST_URI'];
	}
	header("Location: $page");
}

function action($action, $params = null) {
	$res = 'controller.php?action=' . rawurlencode($action);
	$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : $_SERVER['REQUEST_URI'];
	$res .= '&page=' . rawurlencode($page);
	if (is_array($params)) {
		foreach ($params as $name => $value) {
			$res .= '&' . rawurlencode($name) . '=' . rawurlencode($value);
		}
	}
	echo $res;
}

function createPasswordHash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}


function getDateOptions($attendance, $dateFrom = true) {
    $activeEvent = EventDAO::getActiveEvent();

    $time = $activeEvent->getDateFrom();
    $startTime = $time;
    $endTime = $activeEvent->getDateTo();
    $slottime = $activeEvent->getSlotTime();
    $timeBetweenSlots = $activeEvent->getTimeBetweenSlots();
    
    $slottime = ($slottime * 60);
	$timeBetweenSlots = ($timeBetweenSlots * 60);
    
    $options = '';

    while ($time <= $endTime) {
        // $halfHour = 60 * 60 / 2;
        $selected = '';
        if ($dateFrom && $time == $attendance['from']) {
            $selected = ' selected';
        } else if (!$dateFrom && ($time - $timeBetweenSlots) == $attendance['to']) {
            $selected = ' selected';
        }
        
        if (!$dateFrom && $time != $startTime) {
            $time -= $timeBetweenSlots;
        }
        
        $options .= sprintf('<option value="%s"%s>%s</option>', $time, $selected, date('H:i', $time));
        // $time += $halfHour;
        
        if (!$dateFrom && $time != $startTime) {
            $time += $timeBetweenSlots;
        }

        $time += $slottime + $timeBetweenSlots;
    }

    return $options;
}

function getTeacherOptions() {
    $teachers = UserDAO::getUsersForRole('teacher');

    $options = '<option value="-1">Bitte wähle einen Lehrer aus ...</option>';
    foreach ($teachers as $teacher) {
        $options .= sprintf('<option value="%s" %s>%s</option>', $teacher->getId(), ($teacher->isAbsent()==1?'disabled':''),$teacher->getLastName() . ' ' . $teacher->getFirstName().' '.$teacher->getTitle().($teacher->isAbsent()==1?' - abwesend':''));
    }

    return $options;
}

function sort_students_for_form($array) {
    
    usort($array,function($a,$b){
        $c = strnatcmp($a->getClass(),$b->getClass());
        $c .= strcmp($a->getFirstName(), $b->getFirstName());
        $c .= strcmp($a->getLastName(), $b->getLastName());
    return $c;
    });
    
    return $array;
}
function getStudentsOptions() {
    $students = UserDAO::getUsersForRole('student');

    $students = sort_students_for_form($students);
    
    $options = '<option value="-1">Bitte wähle einen Schüler aus ...</option>';
    foreach ($students as $student) {
        $options .= sprintf('<option value="%s">%s</option>', $student->getId(), $student->getClass() . ' - ' . $student->getFirstName() . ' ' . $student->getLastName());
    }

    return $options;
}

function getRoomOptions() {
    $rooms = RoomDAO::getAllFreeRooms();
    
    $options = '<option value="-1" selected>Bitte wähle einen Raum aus ...</option>';
    $options .= '<option value="0">kein Raum</option>';
    
    foreach ($rooms as $room) {
        $options .= sprintf('<option value="%s">%s - %s</option>', $room->getId(), $room->getRoomNumber(), $room->getRoomName());
    }
    

    return $options;
}



function toDate($timestamp, $format) {
    return date($format, $timestamp);
}

function getActionString($actionId) {
    switch ($actionId) {
        case 1:
            return 'eingeloggt';
        case 2:
            return 'ausgeloggt';
        case 3:
            return 'Termin gebucht';
        case 4:
            return 'Termin gelöscht';
        case 5:
            return 'Anwesenheit geändert';
        case 6:
            return 'Pausen geändert';
        case 7:
            return 'Raum geändert';
        default:
            return 'Unbekannte Aktion';
    }
}

function getActiveSpeechdayText() {
    $activeEvent = EventDAO::getActiveEvent();
    if ($activeEvent != null) {
        return "Elternsprechtag am " . toDate($activeEvent->getDateFrom(), 'd.m.Y');
    } else {
        return "Es gibt momentan keinen aktiven Elternsrpechtag!";
    }
}

function isBookingtimeForActiveSpeechday() {
    $activeEvent = EventDAO::getActiveEvent();
    
    if ($activeEvent->getStartPostDate() > time()) {
            return true;
    } else {
        return false;
    }
}

function optionalBreak() {
    return '<span class="no-print"><br></span><span class="only-print"> - </span>';
}
