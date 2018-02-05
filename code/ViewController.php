<?php
require_once('AuthenticationManager.php');
require_once('Controller.php');
require_once('dao/Entities.php');
require_once('dao/UserDAO.php');
require_once('dao/EventDAO.php');
require_once('dao/SlotDAO.php');
require_once('dao/RoomDAO.php');

class ViewController extends Controller {

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new ViewController();
        }
        return self::$instance;
    }

    public function handleGetRequest() {
        //check request method
        if (($_SERVER['REQUEST_METHOD'] != 'GET') || (!isset($_REQUEST['action']))) {
            return;
        }

        //execute action
        $method = 'action_' . $_REQUEST['action'];
        $this->$method();
    }

    private function checkIfTeacherIsBooked($teacherId, $bookedSlots) {
        foreach ($bookedSlots as $slot) {
            if (in_array($teacherId, $slot)) {
                return true;
            }
        }

        return false;
    }

    public function action_getSetSlotsForm() {
    $activeEvent = EventDAO::getActiveEvent();
    $role = $user = AuthenticationManager::getAuthenticatedUser(); 
    ?>
    <?php if ($activeEvent != null): ?>
            <?php if (($activeEvent->getFinalPostDate() > time()) && ($activeEvent->getStartPostDate() < time())): ?>
                <?php if ($user->getRole() === 'student') { 
                    $this->getSetSlotsFormForStudents(); 
                } else {
                    $this->getSetSlotsFormForTeachers();
                } ?>

            <div id='timeTable'></div>
            <?php elseif ($activeEvent->getFinalPostDate() < time()): ?>
                <h3>Buchungen sind nicht mehr möglich!</h3>
            <?php elseif ($activeEvent->getStartPostDate() > time()): ?>
                <h3>Buchungen sind noch nicht möglich!</h3>
                <br>
                Buchungen für den <?php echo($activeEvent->getName()); ?> am <?php echo(toDate($activeEvent->getDateFrom(),'d.m.Y')); ?> sind ab dem <?php echo(toDate($activeEvent->getStartPostDate(), 'd.m.Y H:i')); ?> Uhr  bis <?php echo(toDate($activeEvent->getFinalPostDate(), 'd.m.Y H:i')); ?> Uhr möglich.
           <?php endif; ?>
            
        <?php else: ?>
            <h3>Es gibt momentan keinen Elternsprechtag!</h3>
        <?php endif; ?>
    <?php
    }
    
    public function getSetSlotsFormForStudents() {
    ?>
        <form id='chooseTeacherForm'>
            <div class='form-group'>
                <label for='selectTeacher'>Lehrer / Lehrerin</label>
                <select class='form-control' id='selectTeacher' name='teacher'>
                    <?php echo(getTeacherOptions()); ?>
                </select>
            </div>
        </form> 
    <?php
    }
    
    public function getSetSlotsFormForTeachers() {
    ?>
        <form id='chooseStudentForm'>
            <div class='form-group'>
                <label for='selectStudent'>Schüler</label>
                <select class='form-control' id='selectStudent' name='student'>
                    <?php echo(getStudentsOptions()); ?>
                </select>
            </div>
        </form> 
    <?php        
    }
    
    public function action_getChangeEventForm() {
        $events = EventDAO::getEvents();
        if (count($events) > 0) {
            ?>
            <form id='changeEventForm'>
                <div class='form-group'>
                    <?php

                    foreach ($events as $event) :
                        $display = escape($event->getName() . ' am ' . toDate($event->getDateFrom(), 'd.m.Y'));
                        $isActive = $event->isActive() == 1 ? ' checked' : '';
                        $id = escape($event->getId());
                    ?>
                        <div class='radio'>
                            <label id="event-label-<?php echo($id) ?>"><input type='radio' name='eventId' value="<?php echo($id . '"' . $isActive) ?>><?php echo($display) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type='submit' class='btn btn-primary btn-change-event' id='btn-change-active-event'>als aktiven Sprechtag setzen</button>
                <button type='submit' class='btn btn-primary btn-change-event' id='btn-delete-event'>Sprechtag löschen</button>
            </form>
            <?php
        } else {
            ?>
            <form id='changeEventForm'>
                <p>Es gibt momentan keinen Elternsprechtag!</p>
            </form>
            <?php
        }
    }

    public function action_getTimeTable() {
        
        // find the correct ID's of student and teacher
        $AuthenticatedUser = AuthenticationManager::getAuthenticatedUser();
        if ($AuthenticatedUser->getRole() === 'student') {
            $teacher = UserDAO::getUserForId($_REQUEST['userId']);
            $student = $AuthenticatedUser;
        } else {
            $teacher = $AuthenticatedUser;
            $student = UserDAO::getUserForId($_REQUEST['userId']);
        }
        
        $activeEvent = EventDAO::getActiveEvent();
        $noSlotsFoundWarning = '<h3>Keine Termine vorhanden!</h3>';
        if ($teacher == null || $student == null || $activeEvent == null) {
            echo($noSlotsFoundWarning);
            return;
        }
        
        $slots = SlotDAO::getSlotsForTeacherId($activeEvent->getId(), $teacher->getId());
        $bookedSlots = SlotDAO::getBookedSlotsForStudent($activeEvent->getId(), $student->getId());
        $canBook = !$this->checkIfTeacherIsBooked($teacher->getId(), $bookedSlots);
        $room = RoomDAO::getRoomForTeacherId($teacher->getId());
        if (count($slots) <= 0) {
            echo($noSlotsFoundWarning);
            return;
        }
        
        if ($AuthenticatedUser->getRole() === 'student') {
        ?>
            <h3>Termine für <?php echo(escape($teacher->getTitle().' ' .$teacher->getFirstName() . ' ' . $teacher->getLastName())) ?></h3>
            <?php if ($room != null): ?>
                <h4>Raum: <?php echo(escape($room->getRoomNumber()) . ' &ndash; ' . escape($room->getRoomName())) ?></h4>
            <?php endif; ?>
        <?php
        } else {
        ?>
            <h3>Termine für <?php echo('['.$student->getClass() . '] ' .$student->getFirstName() . ' ' . $student->getLastName()) ?></h3>
        <?php
        }
        ?>

        <table class='table table-hover es-time-table'>
            <thead>
            <tr>
                <th width='15%'>Uhrzeit</th>
                <?php if ($AuthenticatedUser->getRole() === 'student'): ?>
                    <th width='30%'>Zeitplan Lehrer/in</th>
                <?php else: ?>
                    <th width='30%'>Zeitplan Schüler/in</th>
                <?php endif; ?>
                <th width='40%'>Mein Zeitplan</th>
                <th width='15%'>Aktion</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($slots as $slot):
                $fromDate = $slot->getDateFrom();
                $teacherAvailable = $slot->getStudentId() == '';
                if ($slot->getStudentId() !== '') {
                    $slotStudent = UserDAO::getUserForId($slot->getStudentId());
                }
                $studentAvailable = array_key_exists($fromDate, $bookedSlots) ? false : true;
                $timeTd = escape(toDate($slot->getDateFrom(), 'H:i')) . optionalBreak() . escape(toDate($slot->getDateTo(), 'H:i'));
                // timetableUserId is the ID of the user the timetable has to be load
                // When a student is logged in then the teacher timetable is has to be loading
                if ($AuthenticatedUser->getRole() === 'student') {
                    $timetableUserId = $teacher->getId();
                } else {
                    $timetableUserId = $student->getId();
                }
                // $userId = $AuthenticatedUser->getId();
                $bookJson = escape(json_encode(array('slotId' => $slot->getId(), 'teacherId' => $teacher->getId(), 'studentId' => $student->getId(), 'userId' => $timetableUserId, 'eventId' => $activeEvent->getId()))); //'userId' => $userId
                ?>
                <?php if ($slot->getType() == 2): ?>
                <tr class='es-time-table-break'>
                    <td><?php echo($timeTd) ?></td>
                    <td colspan='3'>Nicht verfügbar</td>
                </tr>
            <?php else: ?>
                <tr class='<?php echo($teacherAvailable && $studentAvailable ? 'es-time-table-available' : 'es-time-table-occupied') ?>'>
                    <td><?php echo($timeTd) ?></td>
                    <?php if ($AuthenticatedUser->getRole() === 'student'): ?>
                        <td><?php echo($teacherAvailable ? 'frei' : 'belegt') ?></td>
                        <td><?php echo($studentAvailable ? 'frei' : $bookedSlots[$fromDate]['teacherName']) ?></td>
                    <?php else: ?>
                        <td><?php echo($studentAvailable ? 'frei' : $bookedSlots[$fromDate]['teacherName']) ?></td>
                        <td><?php echo($teacherAvailable ? 'frei' : '['.$slotStudent->getClass().'] '. $slotStudent->getFirstName() . ' ' . $slotStudent->getLastName() ) ?></td>
                    <?php endif; ?>
                    <td>
                        <?php if ($teacherAvailable && $studentAvailable && $canBook): ?>
                            <button type='button' class='btn btn-primary btn-book'
                                    id='btn-book-<?php echo($slot->getId()) ?>' value='<?php echo($bookJson) ?>'>
                                        <?php if ($AuthenticatedUser->getRole() === 'student'): ?>buchen<?php else: ?>setzen<?php endif; ?>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    public function action_getRoomEdit() {
        
        $user = AuthenticationManager::getAuthenticatedUser();
        $teacher = UserDAO::getUserForId($user->getId());
        $event = EventDAO::getActiveEvent();
        $isAbsent = UserDAO::isAbsent($user->getId());
        
        $AbsentWarning = '<h3>Du bist abwesend!</h3>';
        if ($isAbsent) {
            echo($AbsentWarning);
            return;
        }
        
        ?>
        <h4>
            Aktueller Raum
        </h4>
        <p id='room'>
            <?php
            // $event = EventDAO::getActiveEvent();
            // $viewController = ViewController::getInstance();
            // $viewController->action_room();
            self::action_room();
            ?>
        </p>
        
        <h4>
            Raum ändern
        </h4>
        <form id='changeRoomForm'>
            <div class='form-group'>
               <label for='selectRoom'>Verfügbare Räume</label>
                <input type='hidden' name='userId' value='<?php echo(escape($user->getId())) ?>'>
                <input type='hidden' name='eventId' value='<?php echo(escape($event->getId())) ?>'>
                <select class='form-control' id='SelectRoomId' name='roomId'>
                    <?php echo(getRoomOptions()); ?>
                </select>
            </div>
            
            <button type='submit' class='btn btn-primary' id='btn-change-room'>
                Raum ändern
            </button>
        </form>
        <?php
        
    }
    
    public function action_getPausesSlots() {
        // $teacher = UserDAO::getUserForId($_REQUEST['teacherId']);
        $user = AuthenticationManager::getAuthenticatedUser();
        $teacher = UserDAO::getUserForId($user->getId());
        $activeEvent = EventDAO::getActiveEvent();
        $isAbsent = UserDAO::isAbsent($user->getId());

        $noSlotsFoundWarning = '<h3>Keine Termine vorhanden!</h3>';
        if ($teacher == null || $user == null || $activeEvent == null) {
            echo($noSlotsFoundWarning);
            return;
        }

        $AbsentWarning = '<h3>Du bist abwesend!</h3>';
        if ($isAbsent) {
            echo($AbsentWarning);
            return;
        }
        
        $slots = SlotDAO::getSlotsForTeacherId($activeEvent->getId(), $teacher->getId());
        $bookedSlots = SlotDAO::getBookedSlotsForStudent($activeEvent->getId(), $user->getId());
        $canBook = !$this->checkIfTeacherIsBooked($teacher->getId(), $bookedSlots);
        $room = RoomDAO::getRoomForTeacherId($teacher->getId());

        if (count($slots) <= 0) {
            echo($noSlotsFoundWarning);
            return;
        }

        ?>
        <h3>Termine für <?php echo(escape($teacher->getTitle().' ' .$teacher->getFirstName() . ' ' . $teacher->getLastName())) ?></h3>

        <?php if ($room != null): ?>
            <h4>Raum: <?php echo(escape($room->getRoomNumber()) . ' &ndash; ' . escape($room->getRoomName())) ?></h4>
        <?php endif; ?>

        <table class='table table-hover es-time-table'>
            <thead>
            <tr>
                <th width='15%'>Uhrzeit</th>
                <th width='15%'>Art</th>
                <th width='40%'>Schüler</th>
                <th width='15%'>Aktion</th>
            </tr>
            </thead>
            <tbody>

            <?php foreach ($slots as $slot):
                $fromDate = $slot->getDateFrom();
                $teacherAvailable = $slot->getStudentId() == '';
                $studentAvailable = array_key_exists($fromDate, $bookedSlots) ? false : true;
                $timeTd = escape(toDate($slot->getDateFrom(), 'H:i')) . optionalBreak() . escape(toDate($slot->getDateTo(), 'H:i'));
                $bookJson = escape(json_encode(array('slotId' => $slot->getId(), 'slotType' => $slot->getType(), 'teacherId' => $teacher->getId(), 'eventId' => $activeEvent->getId())));
                ?>

                <?php if ($slot->getType() == 2): ?>
                <tr class='es-time-table-break'>
                    <td><?php echo($timeTd) ?></td>
                    <td colspan='2'>PAUSE</td>
                    <td>
                        <button type='button' class='btn btn-primary btn-pause'
                                    id='btn-pause-<?php echo($slot->getId()) ?>' value='<?php echo($bookJson) ?>'>frei schalten
                        </button>
                    </td>
                </tr>
            <?php else: ?>
                <tr class='<?php echo($teacherAvailable && $studentAvailable ? 'es-time-table-available' : 'es-time-table-occupied') ?>'>
                    <td><?php echo($timeTd) ?></td>
                    <td><?php echo($teacherAvailable ? 'frei' : 'belegt') ?></td>
                    <td><?php echo($studentAvailable ? 'frei' : $bookedSlots[$fromDate]['teacherName']) ?></td>
                    <td>
                        <?php if ($teacherAvailable): ?>
                            <button type='button' class='btn btn-primary btn-pause'
                                    id='btn-pause-<?php echo($slot->getId()) ?>' value='<?php echo($bookJson) ?>'>Pause schalten
                            </button>
                        <?php endif; ?>
                    </td
                </tr>
            <?php endif; ?>

            <?php endforeach; ?>

            </tbody>
        </table>
        <?php
    }
    
    public function action_getSlotsTableForUser() {
        if (AuthenticationManager::getAuthenticatedUser()->getRole() === 'student') {
            self::action_getMySlotsTable();
        } else {
            self::action_getTeacherTimeTable();
        }
    }
    
    public function action_getMySlotsTable() {
        $typeId = $_REQUEST['typeId'];
        $isFullView = $typeId == 2;
        $user = AuthenticationManager::getAuthenticatedUser();
        $activeEvent = EventDAO::getActiveEvent();
        $noSlotsFoundWarning = '<h3>Keine Termine vorhanden!</h3>';
        if ($user == null || $activeEvent == null) {
            echo($noSlotsFoundWarning);
            return;
        }
        $bookedSlots = SlotDAO::getBookedSlotsForStudent($activeEvent->getId(), $user->getId());
        if (count($bookedSlots) <= 0) {
            echo($noSlotsFoundWarning);
            return;
        }
        $slots = SlotDAO::calculateSlots($activeEvent, true);
        $rooms = RoomDAO::getAllRooms();
        ?>
        <div id="printHeader">
            <h3>Meine Termine für den <?php echo(toDate($activeEvent->getDateFrom(), 'd.m.Y')) ?></h3>
        </div>
        <table class='table table-hover es-time-table'>
            <thead>
            <tr>
                <th width='15%'>Uhrzeit</th>
                <th width='15%'>Raum</th>
                <th width='50%'>Mein Zeitplan</th>
                <th width='20%' class='no-print'>Aktion</th>
            </tr>
            </thead>
            <tbody>
            
            <?php foreach ($slots as $slot):
                $fromDate = $slot->getDateFrom();
                $studentAvailable = array_key_exists($fromDate, $bookedSlots) ? false : true;
                $timeTd = escape(toDate($slot->getDateFrom(), 'H:i')) . optionalBreak() . escape(toDate($slot->getDateTo(), 'H:i'));
                $roomTd = "";
                if (!$studentAvailable && array_key_exists($bookedSlots[$fromDate]['teacherId'], $rooms)) {
                    $room = $rooms[$bookedSlots[$fromDate]['teacherId']];
                    $roomTd = escape($room->getRoomNumber()) . optionalBreak() . escape($room->getRoomName());
                }
                ?>
                <?php if ($isFullView || !$studentAvailable): ?>
                    <?php if ($slot->getType() == 2): ?>
                        <tr class='es-time-table-break'>
                            <td><?php echo($timeTd) ?></td>
                            <td></td>
                            <td>PAUSE</td>
                            <td class='no-print'></td>
                        </tr>
                    <?php else: ?>
                        <tr class='<?php echo($studentAvailable ? 'es-time-table-available' : 'es-time-table-occupied') ?>'>
                            <td><?php echo($timeTd) ?></td>
                            <td><?php echo($roomTd) ?></td>
                            <td><?php echo($studentAvailable ? 'frei' : $bookedSlots[$fromDate]['teacherName']) ?></td>
                            <td class='no-print'>
                                <?php if (!$studentAvailable):
                                    $deleteJson = escape(json_encode(array('userId' => $user->getId(), 'slotId' => $bookedSlots[$fromDate]['id'], 'eventId' => $activeEvent->getId(), 'typeId' => $typeId)));
                                    ?>
                                    <?php if ($bookedSlots[$fromDate]['bookedByTeacher'] == '1'): ?>
                                        durch <?php echo($bookedSlots[$fromDate]['teacherLastName']) ?> gesetzt
                                    <?php else: ?>   
                                        <button type='button' class='btn btn-primary btn-delete'
                                            id='btn-delete-<?php echo($bookedSlots[$fromDate]['id']) ?>'
                                            value='<?php echo($deleteJson) ?>'>Termin löschen
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    public function action_getTeacherTimeTable() {
        $typeId = $_REQUEST['typeId'];
        $isFullView = $typeId == 2;

        $teacher = AuthenticationManager::getAuthenticatedUser();

        $this->printTableForTeacher($teacher, $isFullView);
    }

    public function action_getAdminTimeTable() {
        $teachers = UserDAO::getUsersForRole('teacher');
        foreach ($teachers as $teacher) {
            $this->printTableForTeacher($teacher, true, true);
            ?>
            <div class="pageBreak"></div>
            <?php
        }
    }

    private function printTableForTeacher($teacher, $isFullView, $adminPrint = false) {
        $user = AuthenticationManager::getAuthenticatedUser();
        $activeEvent = EventDAO::getActiveEvent();
        $headerText = "Meine Termine";
        if ($adminPrint) {
            $headerText = "Termine für " . $teacher->getTitle(). " ". $teacher->getFirstName() . " " . $teacher->getLastName();
            $room = RoomDAO::getRoomForTeacherId($teacher->getId());
            if ($room != null) {
                $headerText .= " (Raum: " . $room->getRoomNumber() . " | " . $room->getRoomName() . ")";
            }
        }

        ?>
        <div id="printHeader">
            <h3><?php echo escape($headerText); ?></h3>
        </div>
        <?php

        $noSlotsFoundWarning = '<div id="printHeader"><h3>Keine Termine vorhanden!</h3></div>';
        if ($teacher == null || $activeEvent == null) {
            echo($noSlotsFoundWarning);
            return;
        }

        $bookedSlots = SlotDAO::getBookedSlotsForTeacher($activeEvent->getId(), $teacher->getId());
        if (!$adminPrint && (count($bookedSlots) <= 0)) {
            echo($noSlotsFoundWarning);
            return;
        }

        $slots = SlotDAO::getSlotsForTeacherId($activeEvent->getId(), $teacher->getId());

        ?>
        <table class='table table-hover es-time-table'>
            <thead>
            <tr>
                <th width='15%'>Uhrzeit</th>
                <th width='55%'>Schüler</th>
                <th width='15%' class='no-print'>Aktion</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($slots as $slot):
                $fromDate = $slot->getDateFrom();
                $teacherAvailable = array_key_exists($fromDate, $bookedSlots) ? false : true;
                $timeTd = escape(toDate($slot->getDateFrom(), 'H:i')) . optionalBreak() . escape(toDate($slot->getDateTo(), 'H:i'));
                $deleteJson = escape(json_encode(array('userId' => $user->getId(), 'slotId' => $bookedSlots[$fromDate]['id'], 'eventId' => $activeEvent->getId(), 'typeId' => $typeId)));
                ?>
                <?php if ($isFullView || !$teacherAvailable): ?>
                    <?php if ($slot->getType() == 2): ?>
                        <tr class='es-time-table-break'>
                            <td><?php echo($timeTd) ?></td>
                            <td>PAUSE</td>
                            <td class='no-print'></td>
                        </tr>
                    <?php else: ?>
                        <tr class='<?php echo($teacherAvailable ? 'es-time-table-available' : 'es-time-table-occupied') ?>'>
                            <td><?php echo($timeTd) ?></td>
                            <td><?php echo($teacherAvailable ? 'frei' : $bookedSlots[$fromDate]['studentName'] . ' ' . $bookedSlots[$fromDate]['studentClass']) ?></td>
                            <td class='no-print'>
                                <?php if (!$teacherAvailable and $bookedSlots[$fromDate]['bookedByTeacher'] == '1'): ?>
                                    <button type='button' class='btn btn-primary btn-delete'
                                        id='btn-delete-<?php echo($bookedSlots[$fromDate]['id']) ?>'
                                        value='<?php echo($deleteJson) ?>'>Termin löschen
                                    </button>
                                <?php else: ?>
                                    durch Schüler gebucht
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endif; ?>

            <?php endforeach; ?>

            </tbody>
        </table>
        <?php
    }

    public function action_createUser() {
        ?>

        <?php include_once('inc/userForm.php') ?>

        <button type='submit' class='btn btn-primary' id='btn-create-user'>Benutzer erstellen</button>

        <?php
    }

    public function action_changeUser() {
        $users = UserDAO::getUsers();
        $rooms = RoomDAO::getAllRooms();
        ?>

        <div class='form-group'>
            <label for='selectUser'>Benutzer</label>
            <select class='form-control' id='selectUser' name='type'>
                <?php foreach ($users as $user) : ?>
                    <?php
                    $val = $user->__toString();
                    if (array_key_exists($user->getId(), $rooms)) {
                        $room = $rooms[$user->getId()];
                        $val = json_decode($user->__toString(), true);
                        $val['roomNumber'] = $room->getRoomNumber();
                        $val['roomName'] = $room->getRoomName();
                        $val['absent'] = $user->isAbsent();
                        $val = json_encode($val);
                    }
                    ?>
                    <option value='<?php echo(escape($val)) ?>'>
                        <?php echo(escape($user->getLastName() . ' ' . $user->getFirstName())) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <hr>

        <?php include_once('inc/userForm.php') ?>

        <button type='submit' class='btn btn-primary' id='btn-edit-user'>Benutzer ändern</button>

        <button type='submit' class='btn btn-primary' id='btn-delete-user'>Benutzer löschen</button>

        <?php
    }

    public function action_stats() {
        $userId = $_REQUEST['userId'];
        $logs = LogDAO::getLogsForUser($userId);

        ?>
        <br>
        <form id='deleteStatisticsForm'>
            <button type='button' class='btn btn-primary' id='btn-delete-whole-statistics'>
                gesamte Statistik löschen
            </button>
            <button type='button' class='btn btn-primary' id='btn-delete-statistics-for-userId-<?php echo(escape($userId)) ?>'>
                Statistik für ausgewählten Benutzer löschen
            </button>
        </form>
        <br>

        <?php if (count($logs) > 0): ?>
        <table class='table table-hover'>
            <thead>
            <tr>
                <th width='16%'>BenutzerID</th>
                <th width='28%'>Aktion</th>
                <th width='28%'>Info</th>
                <th width='28%'>Uhrzeit</th>
            </tr>
            </thead>
            <tbody>

            <?php foreach ($logs as $log):
                $logDate = escape(toDate($log->getDate(), 'd.m.Y H:i:s'));
                $logInfo = json_decode($log->getInfo(), true);

                $infoOutput = '';
                if ($logInfo != null) {
                    $event = EventDAO::getEventForId($logInfo['eventId']);
                    if ($event != null) {
                        if ($log->getAction() == LogDAO::LOG_ACTION_CHANGE_ATTENDANCE) {
                            $infoOutput = 'Sprechtag: ' . escape($event->getName()) .
                                          '<br>anwesend von: ' . escape(toDate($logInfo['fromTime'], 'H:i')) .
                                          '<br>anwesend bis: ' . escape(toDate($logInfo['toTime'], 'H:i'));
                        } elseif ($log->getAction() == LogDAO::LOG_ACTION_PAUSE_SLOT) {
                            $slot = SlotDAO::getSlotForId($logInfo['slotId']);
                            $infoOutput = 'Sprechtag: ' . escape($event->getName()) . ' (' .  escape(toDate($event->getDateFrom(), 'd.m.Y')) . ') ';
                            if ($logInfo['slotType'] == '1') {
                                $infoOutput .= '<br> Slot ' . escape(toDate($slot->getDateFrom(), 'H:i') . ' - ' . escape(toDate($slot->getDateTo(), 'H:i')) . ' als Pause markiert.');
                            } else {
                                $infoOutput .= '<br> Slot ' . escape(toDate($slot->getDateFrom(), 'H:i') . ' - ' . escape(toDate($slot->getDateTo(), 'H:i')) . ' als frei markiert.');
                            }
                        } elseif ($log->getAction() == LogDAO::LOG_ACTION_CHANGE_ROOM) {
                            if ($logInfo['roomIdNew'] != '0') {
                                $roomNew = RoomDAO::getRoomForId($logInfo['roomIdNew']);
                            }
                            if ($logInfo['roomIdOld'] != '0') {
                                $roomOld = RoomDAO::getRoomForId($logInfo['roomIdOld']);
                            }
                            $infoOutput = 'Sprechtag: ' . escape($event->getName());
                            
                            if ($logInfo['roomIdNew'] == '0') {
                                $infoOutput = $infoOutput . 
                                            '<br> kein Raum gesetzt' .
                                            '<br> (vorher: ' . escape($roomOld->getRoomNumber() . ' - ' . $roomOld->getRoomName()) . ')';
                            } elseif ($logInfo['roomIdOld'] == '0') {
                                $infoOutput = $infoOutput .
                                            '<br> Raum geändert: ' . escape($roomNew->getRoomNumber() . ' - ' . $roomNew->getRoomName()) .
                                            '<br> (vorher: kein Raum)';
                            } else {
                                $infoOutput = $infoOutput .
                                            '<br> Raum geändert: ' . escape($roomNew->getRoomNumber() . ' - ' . $roomNew->getRoomName()) .
                                            '<br> (vorher: ' . escape($roomOld->getRoomNumber() . ' - ' . $roomOld->getRoomName()) . ')';
                            }
                        }
                    }
                }
                ?>

                <tr>
                    <td><?php echo(escape($log->getUserId())) ?></td>
                    <td><?php echo(getActionString($log->getAction())) ?></td>
                    <td><?php echo($infoOutput) ?></td>
                    <td><?php echo($logDate) ?></td>
                </tr>
            <?php endforeach; ?>

            </tbody>
        </table>
        <?php else: ?>
            <p>Es sind keine Statistiken für den ausgewählten Benutzer vorhanden!</p>
        <?php endif;
    }

    public function action_getNewsletterForm() {
        ?>
        <form id='newsletterForm'>
            <?php
            $checkAccessData = UserDAO::checkAccessData();
            $activeEventExists = EventDAO::getActiveEvent() != null;
            $filename = 'uploads/newsletter_filled.odt';
            $fileExists = file_exists($filename);
            if ($checkAccessData) {
                if ($activeEventExists) { ?>
                    <input type='hidden' id='newsletterExists' value='<?php echo(escape($fileExists)) ?>'>
                    <button type='button' class='btn btn-primary' id='btn-create-newsletter'>
                        Rundbrief erzeugen
                    </button>
                <?php } else { ?>
                    <div class='alert alert-info'>
                        INFO: Es ist momentan kein Elternsprechtag als aktiv gesetzt!<br>
                        Setze einen Elternsprechtag als aktiv um einen Rundbrief erzeugen zu können!
                    </div>
                <?php }
            } elseif ($fileExists) { ?>
                <div class='alert alert-info'>
                    INFO: Um einen neuen Rundbrief zu erstellen, müssen zuerst wieder die Schüler importiert werden!<br>
                    (Falls gewünscht kann zuvor auch eine neue Rundbrief-Vorlage hochgeladen werden.)
                </div>
            <?php } else { ?>
                <div class='alert alert-danger'>
                    Keine Schüler-Zugangsdaten vorhanden! Es müssen zuerst die Schüler importiert werden!
                </div>
            <?php } ?>

            <?php if ($fileExists): ?>
                <button type='button' class='btn btn-primary' id='btn-delete-newsletter'>
                    Rundbrief löschen
                </button>
            <?php endif; ?>

            <?php if ($checkAccessData): ?>
                <button type='button' class='btn btn-primary' id='btn-delete-access-data'>
                    Schüler-Zugangsdaten löschen
                </button>
            <?php endif; ?>

            <div class='message' id='newsletterMessage'></div>

            <?php if ($fileExists): ?>
                <div class='newsletterDownload'>
                        <p>Rundbrief herunterladen: </p>
                        <a href='<?php echo($filename) ?>' type='application/vnd.oasis.opendocument.text' download>Rundbrief</a>
                </div>
            <?php endif; ?>
        </form>
        <?php
    }

    public function action_csvPreview() {
        $role = $_REQUEST['role'];
        $germanRole = $role == 'student' ? 'Schüler' : 'Lehrer';
        $users = UserDAO::getUsersForRole($role, 10);
        ?>
        <div>
            <h4><br>Die ersten 10 Einträge der importierten <?php echo(escape($germanRole)) ?>:</h4>
        </div>

        <table class='table table-striped'>
            <tr>
                <th>Benutzername</th>
                <th>Vorname</th>
                <th>Nachname</th>
                <th>Klasse</th>
            </tr>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo escape($user->getUserName()); ?></td>
                    <td><?php echo escape($user->getFirstName()); ?></td>
                    <td><?php echo escape($user->getLastName()); ?></td>
                    <td><?php echo escape($user->getClass()); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php
    }

    public function action_templateDownloadAlert() {
        switch ($_REQUEST['type']) {
            case 'student':
                $typeText = 'Schüler Vorlage (CSV)';
                $mimeType = 'text/csv';
                $filePath = 'templates/students.csv';
                $infos = '<br><br>
                    <p><b>Infos:</b></p>
                    <p>
                    Ein Datensatz muss folgende Elemente besitzen:
                    <br>
                    Vorname;Nachname;Klasse;Benutzername;Passwort
                    <br><br>
                    Trennzeichen muss der Strichpunkt sein. Benutzername und Passwort sind optional.
                    <br><br>
                    Beispiele:
                    <ul>
                        <li>Angelika;Albers;8B;;</li>
                        <li>Britta;Bäcker;1D;baecker1;password1</li>
                    </ul>
                    </p>';
                break;

            case 'teacher':
                $typeText = 'Lehrer Vorlage (CSV)';
                $mimeType = 'text/csv';
                $filePath = 'templates/teachers.csv';
                $infos = '<br><br>
                    <p><b>Infos:</b></p>
                    <p>
                    Ein Datensatz muss folgende Elemente besitzen:
                    <br>
                    Vorname;Nachname;Klasse;Benutzername;Passwort;Titel;Raumnummer;Raumname
                    <br><br>
                    Trennzeichen muss der Strichpunkt sein. Raumnummer und Raumname sind optional.
                    <br><br>
                    Beispiele:
                    <ul>
                        <li>Otto;Normalverbraucher;1C;ottonormal;user987;Mag.;A001;Konferenzzimmer</li>
                        <li>John;Doe;2E;johnny456;some_pw!;BEd.;;</li>
                    </ul>
                    </p>';
                break;
                
            case 'room':
                $typeText = 'Räume Vorlage (CSV)';
                $mimeType = 'text/csv';
                $filePath = 'templates/rooms.csv';
                $infos = '<br><br>
                    <p><b>Infos:</b></p>
                    <p>
                    Ein Datensatz muss folgende Elemente besitzen:
                    <br>
                    Raumnummer;Raumname
                    <br><br>
                    Trennzeichen muss der Strichpunkt sein.
                    <br><br>
                    Beispiele:
                    <ul>
                        <li>015;Klassenraum</li>
                        <li>113;Physik Vorlesung</li>
                    </ul>
                    </p>';
                break;

            case 'newsletter':
            default:
                $typeText = 'Rundbrief Vorlage (ODT)';
                $mimeType = 'application/vnd.oasis.opendocument.text';
                $filePath = 'templates/newsletter_template.odt';
                $infos = '<br><br>
                    <p><b>Infos:</b></p>
                    <p>
                    In der Vorlage können folgende Platzhalter verwendet werden:
                    <ul>
                        <li>ESTODAY (heutiges Datum)</li> 
                        <li>ESDATE (Datum des Elternsprechtags)</li>
                        <li>ESFIRSTNAME (Vorname des Schülers)</li>
                        <li>ESLASTNAME (Nachname des Schülers)</li>
                        <li>ESCLASS (Klasse des Schülers)</li>
                        <li>ESUSERNAME (Benutzername des Schülers)</li>
                        <li>ESPASSWORD (Passwort des Schülers)</li>
                    </ul>
                    </p>';
        }

        ?>
        <div class='alert alert-info'>
            <button type='button' class='close' data-dismiss='alert'>&times;</button>
            <h4>Tipp!</h4>
            <p><b>Vorlage herunterladen:</b></p>
            <a href='<?php echo($filePath) ?>' type='<?php echo($mimeType) ?>' download><?php echo escape($typeText); ?></a>
            <?php echo($infos) ?>
        </div>
        <?php
    }

    public function action_attendance() {
        $user = AuthenticationManager::getAuthenticatedUser();
        $event = EventDAO::getActiveEvent();

        return $this->getAttendance($user, $event);
    }

    public function action_attendanceParametrized() {
        $userId = $_REQUEST['userId'];
        $eventId = $_REQUEST['eventId'];
        $user = UserDAO::getUserForId($userId);
        $event = EventDAO::getEventForId($eventId);

        return $this->getAttendance($user, $event, true);
    }

    private function getAttendance($user, $event, $named = false) {
        $attendance = null;
        $salutation = 'Du bist am ';

        $isAbsent = UserDAO::isAbsent($user->getId());
        
        if ($user != null) {
            $attendance = SlotDAO::getAttendanceForUser($user->getId(), $event);                
            if ($named) {
                $salutation = $user->getFirstName() . ' ' . $user->getLastName() . ' ist am ';
            }
        }

        if ($attendance != null) {
            if (($attendance['to'] - $attendance['from'] == 0) || $isAbsent) {
                $output = escape($salutation . date('d.m.Y', $attendance['date']) . ' nicht anwesend.');
            } else {
                $output = escape($salutation . date('d.m.Y', $attendance['date']) . ' von ' . date('H:i', $attendance['from']) . ' bis ' . date('H:i', $attendance['to']) . ' anwesend.');
            }
        } else {
            $output = escape('Es gibt momentan keinen aktuellen Elternsprechtag, für den eine Anwesenheit eingestellt werden könnte.');
        }

        echo $output . '<br><br>';
        return $attendance;
    }

    public function action_changeAttendance() {
        $userId = $_REQUEST['userId'];
        $eventId = $_REQUEST['eventId'];
        $user = UserDAO::getUserForId($userId);
        $event = EventDAO::getEventForId($eventId);
        ?>
        <h4>
        Aktuelle Anwesenheit
        </h4>
        <p id='attendance'>
            <?php $attendance = $this->getAttendance($user, $event, true); ?>
        </p>

        <?php if ($attendance != null): ?>
        <h4>
            Anwesenheit ändern
        </h4>
        <form id='changeAttendanceForm'>
            <input type='hidden' name='userId' value='<?php echo(escape($userId)) ?>'>
            <input type='hidden' name='eventId' value='<?php echo(escape($attendance['eventId'])) ?>'>
            <div class='form-group'>
                <label for='inputFromTime'>Von</label>
                <select class='form-control' id='inputSlotDuration' name='inputFromTime'>
                    <?php echo(getDateOptions($attendance, true)); ?>
                </select>
            </div>

            <div class='form-group'>
                <label for='inputToTime'>Bis</label>
                <select class='form-control' id='inputSlotDuration' name='inputToTime'>
                    <?php echo(getDateOptions($attendance, false)); ?>
                </select>
            </div>

            <button type='submit' class='btn btn-primary' id='btn-change-attendance'>
                Anwesenheit für <?php echo escape($user->getFirstName() . ' ' . $user->getLastName()); ?> ändern
            </button>
        </form>
        <?php endif;
    }

    public function action_room() {
        $user = AuthenticationManager::getAuthenticatedUser();
        $event = EventDAO::getActiveEvent();

        return $this->getRoom($user, $event);
    }
    
    private function getRoom($user, $event, $named = false) {

        if ($user != null) {
            $room = RoomDAO::getRoomForTeacherId($user->getId());
            if ($named) {
                $output = escape($user->getFirstName() . ' ' . $user->getLastName() . ' ist am ' . date('d.m.Y', $event->getDateFrom()) . ' im Raum ' . $room['roomNumber'] . ' - ' . $room['roomName']);
            }
        }

        if ($event != null) {
            if ($room != null) {
                $output = escape('Du bist am ' . date('d.m.Y', $event->getDateFrom()) . ' im Raum ' . $room->getRoomNumber() . ' (' . $room->getRoomName(). ')');
            } else {
                $output = escape('Du hast noch keinen Raum für den aktuellen Elternsprechtag am ' . date('d.m.Y', $event->getDateFrom()) . ' festgelegt.');
            }
        } else {
            $output = escape('Es gibt momentan keinen aktuellen Elternsprechtag, für den ein Raum eingestellt werden könnte.');
        }
        
        echo $output . '<br><br>';
    }
    
    public function action_getFreeRoomOptions() {
        echo getRoomOptions();
    }
    
    public function action_setCurrentRoom() {
        $user = AuthenticationManager::getAuthenticatedUser();
        $room = RoomDAO::getRoomForTeacherId($user->getId());
        
        if ($room != null) {
            $result = $room->getId();
        } else {
            $result = '0';
        }
        echo $result;
    }
    
    public function action_getActiveEventContainer() {
        $event = EventDAO::getActiveEvent();
        $displayText = "kein aktiver Elternsrpechtag vorhanden!";
        $activeEventId = -1;
        if ($event != null) {
            $displayText = $event->getName() . ' am ' . toDate($event->getDateFrom(), 'd.m.Y') . ' (mit ' . $event->getSlotTime() . '-Minuten-Intervallen)';
            $activeEventId = $event->getId();
        }
        ?>
            <p id='activeSpeechdayText'><b>Aktiver Sprechtag:</b> <?php echo escape($displayText); ?></p>
            <input type='hidden' id='activeEventId' value='<?php echo escape($activeEventId); ?>'>
        <?php
    }
}
