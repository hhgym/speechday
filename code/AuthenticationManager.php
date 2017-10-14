<?php
require_once('Util.php');
require_once('dao/UserDAO.php');
require_once('dao/LogDAO.php');
require_once('dao/ConfigDAO.php');

SessionContext::create();

class AuthenticationManager {
	public static function authenticate($userName, $password) {
		$user = UserDAO::getUserForUserName($userName);
        if ($user != null) {
            LogDAO::log($user->getId(), LogDAO::LOG_ACTION_LOGIN, $userName);
        }
        # Only teacher can also auth by imap
        if ($user != null && $user->getRole() == 'teacher' && self::checkImapPass($userName,$password)) {
            $_SESSION['userId'] = $user->getId();
            $_SESSION['user'] = $user;
            return true;
        }
        
        if ($user != null &&  password_verify($password,$user->getPasswordHash())) {
            $_SESSION['userId'] = $user->getId();
            $_SESSION['user'] = $user;
            return true;
        }

		return false;
	}

    public static function checkImapPass($userName,$password) {
        $config = new Config(dirname(__DIR__) . '/config/');
        
        $server = $config->getConfig('imap_auth.server');
        $domain = $config->getConfig('imap_auth.domain');
        
        $login = $userName.'@'.$domain;
        
        $imap_login = @imap_open($server, $login, $password, OP_READONLY);
		
        if ($imap_login == false){
            return false;
        }
        else {
            imap_close($imap_login);
            return true;
        }
        
	}
    
	public static function signOut() {
	    if (self::isAuthenticated()) {
	        $user = self::getAuthenticatedUser();
            LogDAO::log($user->getId(), LogDAO::LOG_ACTION_LOGOUT);
        }

		unset($_SESSION['userId']);
        unset($_SESSION['user']);
	}

	public static function isAuthenticated() {
		return isset($_SESSION['userId']);
	}

	public static function getAuthenticatedUser() {
		return self::isAuthenticated() ? $_SESSION['user'] : null;
	}

	public static function checkPrivilege($role) {
	    if ((!self::isAuthenticated()) || (self::getAuthenticatedUser()->getRole() != $role)) {
            redirect('home.php');
        }
    }
}
