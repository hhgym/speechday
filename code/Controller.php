<?php
require_once('AuthenticationManager.php');
require_once('dao/UserDAO.php');
require_once('dao/EventDAO.php');
require_once('dao/SlotDAO.php');
require_once('dao/LogDAO.php');
require_once('dao/RoomDAO.php');
require_once('SimpleICS.php');
require_once('dao/ConfigDAO.php');

class Controller {
    // request wide singleton
    protected static $instance = false;

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Controller();
        }
        return self::$instance;
    }

    public function handlePostRequest() {
        //check request method
        if (($_SERVER['REQUEST_METHOD'] != 'POST') || (!isset($_REQUEST['action']))) {
            redirect('home.php');
        }

        //execute action
        $method = 'action_' . $_REQUEST['action'];
        $this->$method();
    }

    protected function forward($errors = null, $target = null) {
        if ($target == null) {
            if (!isset($_REQUEST['page'])) {
                throw new Exception('Missing target for forward!');
            }
            $target = strtok($_REQUEST['page'], '?');
        }
        // forward request to target
        require($_SERVER['DOCUMENT_ROOT'] . $target);
        exit(0); // --> successful termination of script
    }

    //=== USER ACTIONS ===
    protected function action_createEvent() {
        $name = $_REQUEST['name'];
        $date = $_REQUEST['date'];
        $beginTime = $_REQUEST['beginTime'];
        $endTime = $_REQUEST['endTime'];
        $slotDuration = $_REQUEST['slotDuration'];
		$timeBetweenSlots = $_REQUEST['timeBetweenSlots'];
        $breakFrequency = $_REQUEST['breakFrequency'];
        $setActive = $_REQUEST['setActive'] == 'true' ? true : false;
        $bookingDateStart = $_REQUEST['bookingDateStart'];
        $bookingDateEnd = $_REQUEST['bookingDateEnd'];

        $unixTimeFrom = strtotime($date . ' ' . $beginTime);
        $unixTimeTo = strtotime($date . ' ' . $endTime);
        
        if (!$unixTimeFrom || !$unixTimeTo) {
            return;
        }
        
        $startPostDate = strtotime($bookingDateStart);
        if (!$bookingDateStart) {
            $startPostDate = time();
        }
                
        $finalPostDate = strtotime($bookingDateEnd);
        if (!$finalPostDate) {
            $finalPostDate = strtotime($date . ' 0:00');
        }

        $eventId = EventDAO::createEvent($name, $unixTimeFrom, $unixTimeTo, $slotDuration, $timeBetweenSlots, $breakFrequency, $setActive, $startPostDate, $finalPostDate);
        if ($eventId > 0) {
            echo 'success';
        }
    }

    protected function action_changeAttendance() {
        $fromTime = $_REQUEST['inputFromTime'];
        $toTime = $_REQUEST['inputToTime'];
        $userId = $_REQUEST['userId'];
        $eventId = $_REQUEST['eventId'];

        if ($toTime < $fromTime) {
            echo 'failure';
            return;
        }

        SlotDAO::changeAttendanceForUser($userId, $eventId, $fromTime, $toTime);

        if (isset($_REQUEST['absent'])) {
           UserDAO::updateAbsent($userId, true);
           RoomDAO::updateRoomForTeacher('', $userId, $eventId, true);
        } else {
            UserDAO::updateAbsent($userId, false);
        }
        
        echo 'success';
    }

    protected function action_changeRoom() {
        $roomId = $_REQUEST['roomId'];
        $userId = $_REQUEST['userId'];
        $eventId = $_REQUEST['eventId'];
        
        $room = RoomDAO::getRoomForTeacherId($userId);
        
        if ($room != null) {
            $roomIdOld = $room->getId();
            
        } else {
            $roomIdOld = '0';
        }
        
        $freeRooms = RoomDAO::getAllFreeRooms();

        if (!array_key_exists($roomId, $freeRooms) && $roomId != '0' && $roomId != '-1') {
            echo 'RoomIsBlocked';
            return;
        }
        
        if ($roomId == '-1' || $roomId == $roomIdOld) {
            echo 'NoOrSameRoom';
            return;
        }
        
        if (roomIdOld !== '0') {
            //unset current room from teacher
            $success = RoomDAO::updateRoomForTeacher($roomId, $userId, $eventId, true);
        }

        $success = RoomDAO::updateRoomForTeacher($roomId, $userId, $eventId);
        
        if ($success) {
            $info = json_encode(array('eventId' => $eventId, 'roomIdNew' => $roomId, 'roomIdOld' => $roomIdOld));
            LogDAO::log($userId, LogDAO::LOG_ACTION_CHANGE_ROOM, $info);
            echo 'success';
        } else {
            echo 'failure';
        }
        
        return;
    }
    
    protected function action_uploadFile() {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        header('Content-Type: text/html; charset=UTF-8');

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!array_key_exists('file-0', $_FILES)) {
                echo 'Es wurde keine Datei ausgewählt!';
                return;
            }

            $name = $_FILES['file-0']['name'];
            $tmpName = $_FILES['file-0']['tmp_name'];
            $error = $_FILES['file-0']['error'];
            $size = $_FILES['file-0']['size'];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            switch ($error) {
                case UPLOAD_ERR_OK:
                    //validate file size
                    if ($size / 1024 / 1024 > 2) {
                        echo 'Die Datei überschreitet die Maximalgröße!';
                        return;
                    }

                    //upload file
                    $type = $_REQUEST['uploadType'];
                    if (in_array($type, array('student', 'teacher', 'subject', 'room'))) {
                        if (!$this->validateFileExtension($ext, array('csv'))) {
                            echo 'Ungültiges Dateiformat!';
                            return;
                        }
                        $targetPath = $this->uploadFileAs($name, $tmpName);
                        $importCSVResult = $this->importCSV($type, $targetPath);
                        echo $importCSVResult['success'] ? 'success' : $importCSVResult['message'];
                        return;

                    } else if ($type == 'newsletter') {
                        if (!$this->validateFileExtension($ext, array('odt'))) {
                            echo 'Ungültiges Dateiformat!';
                            return;
                        }
                        $this->uploadFileAs('newsletter.' . $ext, $tmpName);
                        echo 'success';
                        return;

                    } else {
                        echo 'Ungültiger Typ!';
                        return;
                    }

                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    echo 'Die Datei überschreitet die Maximalgröße!';
                    return;

                case UPLOAD_ERR_PARTIAL:
                    echo 'Die Datei konnte nicht vollständig hochgeladen werden!';
                    return;

                case UPLOAD_ERR_NO_FILE:
                    echo 'Es wurde keine Datei ausgewählt!';
                    return;

                case UPLOAD_ERR_NO_TMP_DIR:
                    echo 'Kein Ordner für den Dateiupload verfügbar!';
                    return;

                case UPLOAD_ERR_CANT_WRITE:
                    echo 'Die Datei konnte nicht auf den Server geschrieben werden!';
                    return;

                case UPLOAD_ERR_EXTENSION:
                    echo 'Der Dateiupload wurde durch eine Erweiterung abgebrochen!';
                    return;

                default:
                    echo 'Die Datei konnte nicht hochgeladen werden!';
                    return;
            }
        }
    }

    private function checkCSVHeader($type, $row) {
        $constraints['teacher'] = array('Vorname', 'Nachname', 'Klasse', 'Benutzername', 'Passwort', 'Titel', 'Raumnummer', 'Raumname');
        $constraints['student'] = array('Vorname', 'Nachname', 'Klasse', 'Benutzername', 'Passwort');
        $constraints['subject'] = array('ToDo');
        $constraints['room'] = array('Raumnummer', 'Raumname');

        $constraintPart = implode('', $constraints[$type]);
        $length = strlen($constraintPart);
        if (substr(implode('', $row), 0 - $length) == substr($constraintPart, 0 - $length)) {
            return true;
        } else {
            return false;
        }
    }

    private function removeSpecials($string) {
        $search  = array('ç', 'æ',  'œ',  'á', 'é', 'í', 'ó', 'ú', 'à', 'è', 'ì', 'ò', 'ù', 'ä', 'ë', 'ï', 'ö', 'ü', 'ÿ', 'â', 'ê', 'î', 'ô', 'û', 'å', 'ø', 'ß', 'Ä', 'Ö', 'Ü');
        $replace = array('c', 'ae', 'oe', 'a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'ae', 'e', 'i', 'oe', 'ue', 'y', 'a', 'e', 'i', 'o', 'u', 'a', 'o', 'ss', 'Ae', 'Oe', 'Ue');
        return str_replace($search, $replace, $string);
    }

    private function generateUserName($firstName, $lastName, $digits = 3) {
        $randomDigit = rand(pow(10, $digits - 1), pow(10, $digits) - 1);
        $firstName = strtolower($this->removeSpecials(preg_replace('/\s/', '', $firstName)));
        $lastName = strtolower($this->removeSpecials(preg_replace('/\s/', '', $lastName)));

        return substr($lastName, 0, 3) . substr($firstName, 0, 3) . $randomDigit;
    }

    private function generateRandomPassword($length = 10) {
        $chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ123456789!@#$%&*()_-=+,.?';
        $password = substr(str_shuffle($chars), 0, $length);
        return $password;
    }

    protected function uploadFileAs($name, $tmpName) {
        $folder = 'uploads';
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }

        $targetPath = $folder . DIRECTORY_SEPARATOR . $name;
        move_uploaded_file($tmpName, $targetPath);
        return $targetPath;
    }

    protected function importCSV($role, $targetPath) {
        // import into database
        $filename = $targetPath;
        $fp = fopen($filename, 'r');

        //parse the csv file row by row
        $firstRow = true;
        $users = array();
        $accessData = array();
        $rooms = array();
        $userNames = array();

        $duplicateUserError = array(
            'success' => false,
            'message' => 'Die Benutzernamen sind nicht eindeutig! Bitte vergib eindeutige Benutzernamen!'
        );

        $csv = file_get_contents($filename);
        $isUTF8 = mb_detect_encoding($csv, mb_detect_order(), TRUE) == 'UTF-8';

        while (($row = fgetcsv($fp, 0, ';')) != FALSE) {
            if (!$isUTF8) {
                $row = array_map('utf8_encode', $row);
            }

            if ($firstRow) {
                if (!$this->checkCSVHeader($role, $row)) {
                    fclose($fp);
                    return array(
                        'success' => false,
                        'message' => 'Die Spalten der CSV Datei passen nicht zum gewählten Typ!'
                    );
                } else {
                    $firstRow = false;
                }
            } elseif (in_array($role, array('teacher','student'))) {
                //insert csv data into mysql table
                $class = trim($row[2]) != '' ? trim($row[2]) : null;

                if ($role == 'teacher') {
                    $userName = trim($row[3]);
                    $password = trim($row[4]);

                    if (!$this->checkForUniqueUserName($userName, $userNames)) {
                        fclose($fp);
                        return $duplicateUserError;
                    }
                    $userNames[] = $userName;
                    $title = trim($row[5]);

                    $roomNumber = trim($row[6]);
                    $roomName = trim($row[7]);
                    if ($roomNumber != '' && $roomName != '') {
                        $rooms[$userName] = array($roomNumber, $roomName);
                    }
                } elseif ($role == 'student') {
                    $userName = trim($row[3]);

                    $tries = 0;
                    if ($userName == '') {
                        do {
                            $userName = $this->generateUserName(trim($row[0]), trim($row[1]));
                            $tries++;
                        } while ((!$this->checkForUniqueUserName($userName, $userNames)) && ($tries < 500));
                    }
                    if (!$this->checkForUniqueUserName($userName, $userNames)) {
                        fclose($fp);
                        return $duplicateUserError;
                    }
                    $userNames[] = $userName;
                    $title='';

                    $password = trim($row[4]) == '' ? $this->generateRandomPassword() : trim($row[4]);

                    $accessData[] = array($userName, $password);
                }

                $users[] = array($userName, createPasswordHash($password), trim($row[0]), trim($row[1]), $class, $role, $title);
            } elseif ($role == 'room') {
                $roomNumber = trim($row[0]);
                $roomName = trim($row[1]);
                if ($roomNumber != '' && $roomName != '') {
                    array_push($rooms, array($roomNumber, $roomName));
                }
            }
        }

        $deleteUserSuccess = UserDAO::deleteUsersByRole($role);
        $deleteEventSuccess = true;
        $deleteRoomSuccess = true;
        if ($role == 'teacher') {
            $deleteEventSuccess = EventDAO::deleteAllEvents();
            $deleteRoomSuccess = RoomDAO::deleteAllRooms();
        }
        
        if ($role == 'room') {
            $deleteRoomSuccess = RoomDAO::deleteAllRooms();
        }

        if (!$deleteUserSuccess || !$deleteEventSuccess || !$deleteRoomSuccess) {
            fclose($fp);
            return array(
                'success' => false,
                'message' => 'Die bestehenden Einträge des gewählten Typs konnten nicht gelöscht werden!'
            );
        }

        if (in_array($role, array('teacher','student'))) {
            UserDAO::bulkInsertUsers($users, $rooms);
            if (count($accessData) > 0) {
                UserDAO::bulkInsertAccessData($accessData);
            }
        } elseif ($role == 'room') {
            RoomDAO::bulkInsertRooms($rooms);
        }
          
        fclose($fp);
        return array(
            'success' => true,
            'message' => 'Die CSV Datei wurde erfolgreich importiert!'
        );
    }

    private function checkForUniqueUserName($userName, $userNames) {
        return !in_array($userName, $userNames);
    }

    protected function validateFileExtension($ext, $allowed) {
        if (!in_array($ext, $allowed)) {
            return false;
        }

        return true;
    }

    protected function action_changeSlot() {
        $slotId = $_REQUEST['slotId'];
        $studentId = $_REQUEST['studentId'];
        $teacherId = $_REQUEST['teacherId'];
        $userId = $_REQUEST['userId'];
        $eventId = $_REQUEST['eventId'];

        $info = json_encode(array('eventId' => $eventId, 'slotId' => $slotId));
        LogDAO::log($userId, LogDAO::LOG_ACTION_BOOK_SLOT, $info);

        
        // if (UserDAO::getUserForId($userId)->getRole() == 'teacher') {
        if (AuthenticationManager::getAuthenticatedUser()->getRole() == 'teacher') {
            $bookedbyTeacher = 1;
        } else {
            $bookedbyTeacher = 0;
        }

        $result = SlotDAO::setStudentToSlot($eventId, $slotId, $studentId, $teacherId, $bookedbyTeacher);
        if ($result['success']) {
            if ($result['rowCount'] > 0) {
                echo('success');
            } else {
                echo('dirtyRead');
            }
        } else {
            echo('error');
        }
    }

    protected function action_ToggleSlot() {
        $slotId = $_REQUEST['slotId'];
        $slotType = $_REQUEST['slotType'];
        $teacherId = $_REQUEST['teacherId'];
        $eventId = $_REQUEST['eventId'];

        $info = json_encode(array('eventId' => $eventId, 'slotId' => $slotId, 'slotType' => $slotType));
        LogDAO::log($teacherId, LogDAO::LOG_ACTION_PAUSE_SLOT, $info);

        $result = SlotDAO::togglePauseToSlot($eventId, $teacherId, $slotId, $slotType);
        if ($result['success']) {
            if ($result['rowCount'] > 0) {
                echo('success');
            } else {
                echo('dirtyRead');
            }
        } else {
            echo('error');
        }
    }
    
    protected function action_deleteSlot() {
        $userId = $_REQUEST['userId'];
        $slotId = $_REQUEST['slotId'];
        $eventId = $_REQUEST['eventId'];

        $info = json_encode(array('eventId' => $eventId, 'slotId' => $slotId));
        LogDAO::log($userId, LogDAO::LOG_ACTION_DELETE_SLOT, $info);

        $success = SlotDAO::deleteStudentFromSlot($eventId, $slotId);
        if ($success) {
            echo('success');
        } else {
            echo('error');
        }
    }

    protected function action_setActiveEvent() {
        $eventId = $_REQUEST['eventId'];

        $success = EventDAO::setActiveEvent($eventId);

        if ($success) {
            echo('success');
        } else {
            echo('error');
        }
    }

    protected function action_deleteEvent() {
        $eventId = $_REQUEST['eventId'];

        $success = EventDAO::deleteEvent($eventId);

        if ($success) {
            echo('success');
        } else {
            echo('error');
        }
    }

    protected function action_createUser() {
        $userName = $_REQUEST['userName'];
        $password = $_REQUEST['password'];
        $firstName = $_REQUEST['firstName'];
        $lastName = $_REQUEST['lastName'];
        $class = $_REQUEST['class'];
        $type = $_REQUEST['type'];
        $roomNumber = $_REQUEST['roomNumber'];
        $roomName = $_REQUEST['roomName'];

        $userId = UserDAO::register($userName, $password, $firstName, $lastName, $class, $type);
        $updateRoomResult = true;
        if ($roomNumber != '' && $roomName != '') {
            $updateRoomResult = RoomDAO::update($roomNumber, $roomName, $userId)['success'];
        }

        if (($userId > 0) && $updateRoomResult) {
            echo('success');
        } else if ($userId == -1) {
            echo('Der Benutzer existiert bereits!');
        } else {
            echo('Das Passwort muss mindestens ' . UserDAO::MIN_PASSWORD_LENGTH . ' Zeichen lang sein!');
        }
    }

    protected function action_editUser() {
        $userId = $_REQUEST['userId'];
        $userName = $_REQUEST['userName'];
        $password = $_REQUEST['password'];
        $firstName = $_REQUEST['firstName'];
        $lastName = $_REQUEST['lastName'];
        $class = $_REQUEST['class'];
        $type = $_REQUEST['type'];
        $roomNumber = $_REQUEST['roomNumber'];
        $roomName = $_REQUEST['roomName'];

        $updateUserResult = UserDAO::update($userId, $userName, $password, $firstName, $lastName, $class, $type);
        $updateRoomResult = true;
        if ($roomNumber != '' && $roomName != '') {
            $updateRoomResult = RoomDAO::update($roomNumber, $roomName, $userId)['success'];
        }
        if (isset($_REQUEST['absent'])) {
           UserDAO::updateAbsent($userId, true);
        }

        if ($updateUserResult && $updateRoomResult) {
            echo('success');
        } else {
            echo('error');
        }
    }

    protected function action_deleteUser() {
        $userId = $_REQUEST['userId'];

        $deleteUserResult = UserDAO::deleteUserById($userId);

        if ($deleteUserResult) {
            echo('success');
        } else {
            echo('error');
        }
    }

    protected function action_editUserPwd() {
        $userId = $_REQUEST['userId'];
        $userName = $_REQUEST['userName'];
        $password = $_REQUEST['password'];
        $firstName = $_REQUEST['firstName'];
        $lastName = $_REQUEST['lastName'];
        $class = $_REQUEST['class'];
        $type = $_REQUEST['type'];

        $updateUserResult = UserDAO::update($userId, $userName, $password, $firstName, $lastName, $class, $type);

        if ($updateUserResult) {
            echo('success');
        } else {
            echo('error');
        }
    }
    
    
    protected function action_createNewsletter() {
        if (!UserDAO::checkAccessData()) {
            echo 'Keine Schüler-Zugangsdaten vorhanden! Es müssen zuerst die Schüler importiert werden!';
            return false;
        }

        $user = AuthenticationManager::getAuthenticatedUser();

        if (!file_exists('uploads/newsletter.odt')) {
            echo 'Keine Rundbrief-Vorlage vorhanden! Bitte lade zuerst eine hoch!';
            return false;
        }

        $newFileName = 'uploads/newsletter_filled.odt';
        copy('uploads/newsletter.odt', $newFileName);

        $zip = new ZipArchive;
        $fileToModify = 'content.xml';
        if ($zip->open($newFileName) === TRUE) {
            //Read contents into memory
            $oldContents = $zip->getFromName($fileToModify);
            //Modify contents:
            $newContents = $this->createNewsletter($oldContents);
            if ($newContents == null) {
                echo 'Der Rundbrief konnte nicht erstellt werden, da es keinen aktiven Elternsprechtag gibt!';
                $zip->close();
                unlink($newFileName);
                return false;
            }

            //Delete the old...
            $zip->deleteName($fileToModify);
            //Write the new...
            $zip->addFromString($fileToModify, $newContents);
            //And write back to the filesystem.
            $zip->close();

            echo 'success';
        } else {
            echo 'Der Rundbrief konnte nicht geöffnet werden!';
        }
    }

    protected function action_deleteNewsletter() {
        $newsletterPath = 'uploads/newsletter_filled.odt';

        if (!file_exists($newsletterPath)) {
            echo 'success';
            return true;
        }

        if (unlink($newsletterPath)) {
            echo 'success';
        } else {
            echo 'Der Rundbrief konnte nicht gelöscht werden!';
        }
    }

    protected function action_deleteAccessData() {
        $deleteSuccess = UserDAO::deleteAccessData();

        if ($deleteSuccess) {
            echo 'success';
        } else {
            echo 'Die Schüler-Zugangsdaten konnten nicht gelöscht werden!';
        }
    }

    private function createNewsletter($template) {
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->loadXML($template);
        $root = $doc->documentElement;

        $styles = $root->getElementsByTagName('automatic-styles')->item(0);
        $styleNode = $doc->createElement('style:style');
        $styleNode->setAttribute('style:name', 'NewsletterLineBreak');
        $styleNode->setAttribute('style:family', 'paragraph');
        $styleNodeChild = $doc->createElement('style:paragraph-properties');
        $styleNodeChild->setAttribute('fo:break-before', 'page');
        $styleNode->appendChild($styleNodeChild);
        $styles->appendChild($styleNode);

        $officeText = $root->getElementsByTagName('text')->item(0);
        $officeText->setAttribute("text:use-soft-page-breaks", "true");

        $breakNode = $doc->createElement('text:p');
        $breakNode->setAttribute('text:style-name', 'NewsletterLineBreak');
        $officeText->appendChild($breakNode);

        // --- student loop ---
        $copyNodeBackup = $officeText->cloneNode(true);
        $bodyNode = $root->getElementsByTagName('body')->item(0);

        $activeEvent = EventDAO::getActiveEvent();
        if ($activeEvent == null) {
            return null;
        }
        $students = UserDAO::getStudentsForNewsletter();

        foreach ($students as $studentInfo) {
            $student = $studentInfo['student'];
            $password = $studentInfo['password'];
            $trans = array(
                'ESTODAY' => toDate(time(), 'd.m.Y'),
                'ESDATE' => toDate($activeEvent->getDateFrom(), 'd.m.Y'),
                'ESFIRSTNAME' => escape($student->getFirstName()),
                'ESLASTNAME' => escape($student->getLastName()),
                'ESUSERNAME' => escape($student->getUserName()),
                'ESCLASS' => escape($student->getClass()),
                'ESPASSWORD' => escape($password)
            );

            $copyNode = $copyNodeBackup->cloneNode(true);

            $part = $doc->saveXML($copyNode);
            $part = strtr($part, $trans);

            $newPart = $doc->createDocumentFragment();
            $newPart->appendXML($part);

            $bodyNode->appendChild($newPart);
        }
        // --- student loop ---

        $doc->formatOutput = TRUE;
        $newFile = $doc->saveXML();

        return $newFile;
    }

    protected function action_deleteStats() {
        $userId = $_REQUEST['userId'];

        if ($userId != -1) {
            $success = LogDAO::deleteStatsForUser($userId);
        } else {
            $success = LogDAO::deleteAllStats();
        }

        if ($success) {
            echo 'success';
        } else {
            echo 'failure';
        }
    }
    
    protected function action_downloadICS() {
        
        $user = AuthenticationManager::getAuthenticatedUser();
        $activeEvent = EventDAO::getActiveEvent();

        $eventName = $activeEvent->getName();
        
        if ($user->getRole() == 'student') {
            $slots = SlotDAO::getBookedSlotsForStudent($activeEvent->getId(), $user->getId());
        } else {
            $slots = SlotDAO::getBookedSlotsForTeacher($activeEvent->getId(), $user->getId());
        }
        
        $config = new Config('config');
        $adress = $config->getConfig('school.name') . ', ' . $config->getConfig('school.adress.street') . ', ' . $config->getConfig('school.adress.postcode') . ' ' . $config->getConfig('school.adress.state');
        $url = $config->getConfig('school.url');
        
        $cal = new SimpleICS();
        
        if(count($slots) > 0) {
            
            foreach ($slots as $slot) {
                
                if ($user->getRole() == 'student') {
                    $meetingPersonName = $slot['teacherName'];
                    $room = RoomDAO::getRoomForTeacherId($slot['teacherId']);
                } else {
                    $meetingPersonName = $slot['studentName'] . ' ' . $slot['studentClass'];
                    $room = RoomDAO::getRoomForTeacherId($user->getId());
                }
                
                // $dateFrom = strftime("%Y-%m-%dT%H:%M:%S",$slot['dateFrom']);
                // $dateTo = strftime("%Y-%m-%dT%H:%M:%S",$slot['dateTo']);
                
                $timezone = new DateTimeZone('Europe/Berlin');
                $dateFromObject = DateTime::createFromFormat('U', $slot['dateFrom'], $timezone);
                $dateFromObject->setTimezone($timezone);
                $dateToObject = DateTime::createFromFormat('U', $slot['dateTo'], $timezone);
                $dateToObject->setTimezone($timezone);

                $dateFrom =  $dateFromObject->format(DateTime::ATOM);
                $dateTo = $dateToObject->format(DateTime::ATOM);

                if ($room != null) {
                    $roomString = '\r\nRaum: ' . $room->getRoomNumber() . ' - ' . $room->getRoomName();
                } else {
                    $roomString = '';
                }
                
                $cal->addEvent(function($e) use ($dateFrom, $dateTo, $meetingPersonName, $eventName, $roomString, $adress, $url) {
                    $e->startDate = new DateTime($dateFrom);
                    $e->endDate = new DateTime($dateTo);
                    $e->uri = $url;
                    $e->location = $adress;
                    $e->description = $meetingPersonName . $roomString;
                    $e->summary = $eventName;
                });
            }
            header('Content-type: text/calendar; charset=utf-8');
            header('Content-Disposition: attachment; filename=event.ics');
            echo $cal->serialize();
        } else {
            header('Location: home.php');
        }
        
    }
    
	protected function action_downloadAttendanceOfTeacher() {
		$teachers = UserDAO::getUsersForRole('teacher');
		$event = EventDAO::getActiveEvent();

        $eventName = $event->getName();
		
		$data = array(array("Name", "von", "bis", "Raumnummer","Raumname","abwesend"));

		// print_r($teachers);
		foreach ($teachers as $teacher) {
			$attendance = SlotDAO::getAttendanceForUser($teacher->getId(), $event);
			$room = RoomDAO::getRoomForTeacherId($teacher->getId());
			$name = $teacher->getFirstName() . ' ' . $teacher->getLastName();
			$isAbsent = UserDAO::isAbsent($teacher->getId());

			if ($room != null) {
				$raumnummer = $room->getRoomNumber();
				$raumname = $room->getRoomName();
			} else {
				$raumnummer = '';
				$raumname = '';
			}
			
			if (!$isAbsent) {
				$von = toDate($attendance['from'], 'H:i');
				$bis = toDate($attendance['to'], 'H:i');
			} else {
				$von = '';
				$bis = '';
			}
			
			array_push($data, array($name,$von,$bis,$raumnummer,$raumname,$isAbsent));
		}
		// print_r($data);
		$filename = $eventName.'_Anwesenheit.csv';
		// open raw memory as file so no temp files needed, you might run out of memory though
		$f = fopen('php://memory', 'w'); 
		// loop over the input array
		foreach ($data as $line) { 
			// generate csv lines from the inner arrays
			fputcsv($f, $line, ';'); 
		}
		// reset the file pointer to the start of the file
		fseek($f, 0);
		// tell the browser it's going to be a csv file
		header('Content-Type: application/csv');
		// tell the browser we want to save it instead of displaying it
		header('Content-Disposition: attachment; filename="'.$filename.'";');
		// make php send the generated csv lines to the browser
		fpassthru($f);
	}

    protected function action_downloadBookedSlots() {
		$activeEvent = EventDAO::getActiveEvent();
		$teachers = UserDAO::getUsersForRole('teacher');
		$event = EventDAO::getActiveEvent();
        

        $eventName = $activeEvent->getName();
		
		$data = array(array("Lehrer", "von", "bis", "Schüler", "Klasse", "Raumnummer","Raumname"));

		foreach ($teachers as $teacher) {
            $slots = SlotDAO::getBookedSlotsForTeacher($event->getId(), $teacher->getId());
            $room = RoomDAO::getRoomForTeacherId($teacher->getId());
            $teachername = $teacher->getFirstName() . ' ' . $teacher->getLastName();
			$isAbsent = UserDAO::isAbsent($teacher->getId());
			
			if ($room != null) {
				$raumnummer = $room->getRoomNumber();
				$raumname = $room->getRoomName();
			} else {
				$raumnummer = '';
				$raumname = '';
			}

			foreach ($slots as $slot) {
                if (!$isAbsent) {
                    $von = toDate($slot['dateFrom'], 'H:i');
                    $bis = toDate($slot['dateTo'], 'H:i');
                    array_push($data, array($teachername,$von,$bis,$slot['studentName'],$slot['studentClass'],$raumnummer,$raumname));
                }
            }
		}
		// print_r($data);
		$filename = $eventName.'_Termine.csv';
		// open raw memory as file so no temp files needed, you might run out of memory though
		$f = fopen('php://memory', 'w'); 
		// loop over the input array
		foreach ($data as $line) { 
			// generate csv lines from the inner arrays
			fputcsv($f, $line, ';'); 
		}
		// reset the file pointer to the start of the file
		fseek($f, 0);
		// tell the browser it's going to be a csv file
		header('Content-Type: application/csv');
		// tell the browser we want to save it instead of displaying it
		header('Content-Disposition: attachment; filename="'.$filename.'";');
		// make php send the generated csv lines to the browser
		fpassthru($f);
	}
	
	    protected function action_downloadOverviewRooms() {

		$event = EventDAO::getActiveEvent();
        $eventName = $event->getName();
		
		$data = array(array('Raumnummer','Raumname','Lehrer'));
		
		$rooms = RoomDAO::getAllRooms();

		foreach ($rooms as $room) {
			
			$raumnummer = $room->getRoomNumber();
			$raumname = $room->getRoomName();
			
			$teacher = UserDAO::getUserForId($room->getTeacherId());
			if ($teacher != null) {
				$teachername = $teacher->getFirstName() . ' ' . $teacher->getLastName();
			} else {
				$teachername = '';
			}
			
			array_push($data, array($raumnummer,$raumname,$teachername));
		}
		// print_r($data);
		$filename = $eventName.'_Raeume.csv';
		// open raw memory as file so no temp files needed, you might run out of memory though
		$f = fopen('php://memory', 'w'); 
		// loop over the input array
		foreach ($data as $line) { 
			// generate csv lines from the inner arrays
			fputcsv($f, $line, ';'); 
		}
		// reset the file pointer to the start of the file
		fseek($f, 0);
		// tell the browser it's going to be a csv file
		header('Content-Type: application/csv');
		// tell the browser we want to save it instead of displaying it
		header('Content-Disposition: attachment; filename="'.$filename.'";');
		// make php send the generated csv lines to the browser
		fpassthru($f);
	}
	
    protected function action_downloadBookedSlotsForEachTeacher() {
        $activeEvent = EventDAO::getActiveEvent();
		$teachers = UserDAO::getUsersForRole('teacher');
		$event = EventDAO::getActiveEvent();
        
        $eventName = $activeEvent->getName();
        
        // create your zip file
        $zipname = $eventName.'.zip';
        $zip = new ZipArchive;
        $zip->open($zipname, ZipArchive::CREATE);
        
        // loop over teachers
        foreach ($teachers as $teacher) {
            $slots = SlotDAO::getBookedSlotsForTeacher($event->getId(), $teacher->getId());
            $room = RoomDAO::getRoomForTeacherId($teacher->getId());
            // $teachername = $teacher->getFirstName() . ' ' . $teacher->getLastName();
			$isAbsent = UserDAO::isAbsent($teacher->getId());
            
            if (!$isAbsent and !empty($slots)) {
            
                if ($room != null) {
                    $raumnummer = $room->getRoomNumber();
                    $raumname = $room->getRoomName();
                } else {
                    $raumnummer = '';
                    $raumname = '';
                }
                
                $data = array(array("von", "bis", "Schüler", "Klasse", "Raumnummer","Raumname"));

                foreach ($slots as $slot) {
                    
                        $von = toDate($slot['dateFrom'], 'H:i');
                        $bis = toDate($slot['dateTo'], 'H:i');
                        array_push($data, array($von,$bis,$slot['studentName'],$slot['studentClass'],$raumnummer,$raumname));
                }
                // open raw memory as file so no temp files needed, you might run out of memory though
                $f = fopen('php://memory', 'w');
                // write the data to csv
                foreach ($data as $line) { 
                    // generate csv lines from the inner arrays
                    fputcsv($f, $line, ';'); 
                }
                // return to the start of the stream
                rewind($f);
                $teacherfile = $teacher->getUserName();
                // add the in-memory file to the archive, giving a name
                $zip->addFromString($teacherfile.'.csv', stream_get_contents($f) );
                //close the file
                fclose($f);
            }
		}
		// close the archive
        $zip->close();
        
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename='.$zipname);
        header('Content-Length: ' . filesize($zipname));
        readfile($zipname);

        // remove the zip archive
        // you could also use the temp file method above for this.
        unlink($zipname);
    }
    
    protected function action_changeConfig() {
        
        $config = new Config('config');
        
        // echo(var_export($_REQUEST));
        
        $config->setConfig('school.name', $_REQUEST['schoolName']);
        $config->setConfig('school.adress.street', $_REQUEST['schoolStreet']);
        $config->setConfig('school.adress.postcode', $_REQUEST['schoolPostcode']);
        $config->setConfig('school.adress.city', $_REQUEST['schoolCity']);
        $config->setConfig('school.adress.state', $_REQUEST['schoolState']);
        $config->setConfig('school.adress.land', $_REQUEST['schoolLand']);
        $config->setConfig('school.phonenumber', $_REQUEST['schoolPhonenumber']);
        $config->setConfig('school.faxnumber', $_REQUEST['schoolFaxnumber']);
        $config->setConfig('school.url', $_REQUEST['schoolUrl']);
        $config->setConfig('school.email', $_REQUEST['schoolEmail']);
        
        $config->setConfig('title', $_REQUEST['title']);
        $config->setConfig('titleAbbreviation', $_REQUEST['titleAbbreviation']);
        
        $config->setConfig('imap_auth.server', $_REQUEST['imapServer']);
        $config->setConfig('imap_auth.domain', $_REQUEST['imapDomain']);
        
        file_put_contents(dirname(__DIR__).'/config/config.php', "<?php return " . var_export($config->getConfig(), true) . ";" );
        
        echo 'success';
        
    }  
    
}
