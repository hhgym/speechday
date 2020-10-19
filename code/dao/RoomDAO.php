<?php

require_once('AbstractDAO.php');

class RoomDAO extends AbstractDAO {
    public static function deleteAllRooms() {
        $con = self::getConnection();
        $s1 = self::query($con, 'DELETE FROM room;', array(), true);

        return $s1['success'];
    }

    public static function getAllRooms() {
        $rooms = array();
        $con = self::getConnection();
        $res = self::query($con, 'SELECT id, roomNumber, roomName, teacherId FROM room', array());

        while ($r = self::fetchObject($res)) {
            $rooms[$r->id] = new Room($r->id, $r->roomNumber, $r->roomName, $r->teacherId);
        }
        self::close($res);
        return $rooms;
    }

	//rename to getAllUsedRooms() and modify files
    public static function getAllUsedRooms() {
        $rooms = array();
        $con = self::getConnection();
        $res = self::query($con, 'SELECT id, roomNumber, roomName, teacherId FROM room', array());

        while ($r = self::fetchObject($res)) {
            $rooms[$r->teacherId] = new Room($r->id, $r->roomNumber, $r->roomName, $r->teacherId);
        }
        self::close($res);
        return $rooms;
    }

    public static function getAllFreeRooms() {
        $rooms = array();
        $con = self::getConnection();
        $res = self::query($con, 'SELECT id, roomNumber, roomName FROM room WHERE teacherId is NULL', array());

        while ($r = self::fetchObject($res)) {
            $rooms[$r->id] = new Room($r->id, $r->roomNumber, $r->roomName, $r->NULL);
        }
        self::close($res);
        return $rooms;
    }
    
    public static function getRoomForTeacherId($teacherId) {
        $room = null;
        $con = self::getConnection();
        $res = self::query($con, 'SELECT id, roomNumber, roomName, teacherId FROM room WHERE teacherId = ?;', array($teacherId));
        
        if ($r = self::fetchObject($res)) {
            $room = new Room($r->id, $r->roomNumber, $r->roomName, $r->teacherId);
        }
        self::close($res);
        return $room;
    }
    
    public static function getRoomForId($roomId) {
        $room = null;
        $con = self::getConnection();
        $res = self::query($con, 'SELECT id, roomNumber, roomName, teacherId FROM room WHERE id = ?;', array($roomId));
        
        if ($r = self::fetchObject($res)) {
            $room = new Room($r->id, $r->roomNumber, $r->roomName, $r->teacherId);
        }
        self::close($res);
        return $room;
    }

    public static function update($roomNumber, $roomName, $teacherId) {
        $con = self::getConnection();

        if ($roomName != '' && $roomNumber != '') {
            $query = 'UPDATE room SET roomNumber = ?, roomName = ? WHERE teacherId = ?;';
            $params = array($roomNumber, $roomName, $teacherId);

            $res = self::query($con, $query, $params, true);
            $count = $res['statement']->rowCount();

            if ($count < 1) {
                $query = 'INSERT IGNORE INTO room (roomNumber, roomName, teacherId) VALUES (?, ?, ?);';
                $params = array($roomNumber, $roomName, $teacherId);

                return self::query($con, $query, $params, true);
            } else {
                return $res;
            }
        } else {
            $query = 'DELETE FROM room WHERE teacherId = ?';
            $params = array($teacherId);

            return self::query($con, $query, $params, true);
        }
    }
    
    // public static function updateRoomForTeacher($roomId, $userId, $eventId, $unset = true) {
        
        // $room = self::getRoomForTeacherId($userId);
        
        // $con = self::getConnection();
        
        // if ($room != null) {
            // $result = self::query($con, 'UPDATE room SET teacherId = NULL WHERE teacherId = ?;', array($userId), true);
        // }
        
        // if ($result['success'] || $unset) {
            // $result = self::query($con, 'UPDATE room SET teacherId = ? WHERE id = ?;', array($userId, $roomId), true);
            
            // $info = json_encode(array('eventId' => $eventId, 'roomId' => $roomId, 'roomIdold' => $room->getId()));
            // LogDAO::log($userId, LogDAO::LOG_ACTION_CHANGE_ROOM, $info);
        // }
        
        // return $result['success'];
    // }
    
    // public static function setRoomForTeacher($roomId, $userId, $eventId) {
        
        
        // $con = self::getConnection();
        
        // $result = self::query($con, 'UPDATE room SET teacherId = ? WHERE id = ?;', array($userId, $roomId), true);
        
        // $info = json_encode(array('eventId' => $eventId, 'roomId' => $roomId));
        // LogDAO::log($userId, LogDAO::LOG_ACTION_CHANGE_ROOM, $info);
        
        // return $result['success'];
    // }
    
    // public static function unsetRoomForTeacher($roomId, $userId, $eventId) {
        
        // $room = self::getRoomForTeacherId($userId);
        
        // $con = self::getConnection();
        
        // $result = self::query($con, 'UPDATE room SET teacherId = ? WHERE id = ?;', array($userId, $roomId), true);
        
        // $info = json_encode(array('eventId' => $eventId, 'roomId' => $roomId));
        // LogDAO::log($userId, LogDAO::LOG_ACTION_CHANGE_ROOM, $info);
        
        // return $result['success'];
    // }
    
    
    public static function updateRoomForTeacher($roomId, $userId, $eventId, $setnull = false) {
        
        $con = self::getConnection();
        
        if ($setnull) {
            $result = self::query($con, 'UPDATE room SET teacherId = NULL WHERE teacherId = ?;', array($userId), true);
        } else {
            $result = self::query($con, 'UPDATE room SET teacherId = ? WHERE id = ?;', array($userId, $roomId), true);
        }
        
        return $result['success'];
    }

    public static function bulkInsertRooms($rooms) {
        $con = self::getConnection();
        $roomSth = self::getConnection()->prepare('INSERT IGNORE INTO room (roomNumber, roomName) VALUES (?, ?);');

        foreach ($rooms as $room) {
                    $roomSth->bindValue(1, $room[0]);
                    $roomSth->bindValue(2, $room[1]);
                    $roomSth->execute();
        }
    }
}