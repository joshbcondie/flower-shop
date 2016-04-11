<?php

/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 4/11/2016
 * Time: 10:10 AM
 */
Class User {
    const USER_ID = 'user_id';
    const ANONYMOUS_USER_NAME = 'Anonymous';
    const ANONYMOUS_USER_ID = -1;

    private $userId;
    private $username;

    public function __construct($userId, $username) {
        $this->username = $username;
        $this->userId = $userId;
    }


    public function getUsername() {
        return $this->username;
    }


    public function setUsername($username) {
        $this->username = $username;
    }


    public function getUserId() {
        return $this->userId;
    }


    public function setUserId($userId) {
        $this->userId = $userId;
    }


    public function getLastCheckin() {
        return $this->getCheckins()[0]['login_time'];
    }


    public function getCheckins() {
        $checkinQuery = "SELECT user_logins.login_time
						FROM user_logins 
						INNER JOIN users ON user_logins.user_id = users.id
						WHERE users.id = :userId
						ORDER BY login_time DESC";
        $stmt = Database::getDB()->prepare($checkinQuery);
        $stmt->bindValue('userId', $this->userId);
        $stmt->execute();
        $checkinResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // error_log('[User][getCheckins]::$checkinResults ' . print_r($checkinResults, true));

        return $checkinResults;
    }


    public static function getUsers() {
        $usersQuery = 'SELECT id, username FROM users';
        $stmt = Database::getDB()->prepare($usersQuery);
        $stmt->execute();
        $userResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // error_log('[User][getUsers]::$userResults ' . print_r($userResults, true));

        $users = array();
        foreach ($userResults as $user) {
            // error_log('[User][getUsers]::$user ' . print_r($user, true));
            $users[] = new User($user['id'], $user['username']);
        }
        // error_log('[User][getUsers]::$users ' . print_r($users, true));
        return $users;
    }


    public static function getCurrentUser() {

        // error_log('[User][getUsers]::session_status ' . print_r(session_status(), true));
        // error_log('[User][getUsers]::$_SESSION ' . print_r($_SESSION, true));

        if (session_status() == PHP_SESSION_ACTIVE && isset($_SESSION[self::USER_ID])) {
            return self::getUserById($_SESSION[self::USER_ID]);
        }
        else {
            return false;
        }
    }


    public static function getUserById($userId) {
        if ($userId == self::ANONYMOUS_USER_ID) {
            return new User(self::ANONYMOUS_USER_ID, self::ANONYMOUS_USER_NAME);
        }
        $userQuery = 'SELECT id, username FROM users WHERE id = :userId';
        $stmt = Database::getDB()->prepare($userQuery);
        $stmt->bindValue('userId', $userId);
        $stmt->execute();
        $userResults = $stmt->fetch(PDO::FETCH_ASSOC);

        return new User($userResults['id'], $userResults['username']);
    }


    public static function isUser($username) {
        $userQuery = 'SELECT count(*) FROM users WHERE username = :username';
        $stmt = Database::getDB()->prepare($userQuery);
        $stmt->bindValue('username', $username);
        $stmt->execute();
        $userResults = $stmt->fetch(PDO::FETCH_NUM);
        $userResults = $userResults[0];

        // error_log('[mtapi][User][isUser]::$username ' . print_r($username, true));
        // error_log('[mtapi][User][isUser]::$userResults ' . print_r($userResults, true));

        return $userResults > 0;
    }


    public static function createUser($username) {
        $userQuery = 'INSERT INTO users(username) VALUES(:username)';
        $stmt = Database::getDB()->prepare($userQuery);
        $stmt->bindValue('username', $username);
        $stmt->execute();
        $userId = Database::getDB()->lastInsertId();

        self::login($username);

        return new User($userId, $username);
    }


    public static function login($username) {

        $userQuery = 'SELECT id, username FROM users WHERE username = :username';
        $stmt = Database::getDB()->prepare($userQuery);
        $stmt->bindValue('username', $username);
        $stmt->execute();
        $userResults = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userResults) {
            // error_log('[mtapi][User][login]::$userResults ' . print_r($userResults, true));

            $userId = $userResults['id'];

            $recordLoginQuery = 'INSERT INTO user_logins(user_id) VALUES(:userId)';
            $stmt = Database::getDB()->prepare($recordLoginQuery);
            $stmt->bindValue('userId', $userId);
            $stmt->execute();

            if (session_status() != PHP_SESSION_ACTIVE) {
                session_start();
            }

            $_SESSION[self::USER_ID] = $userId;

            return new User($userId, $username);
        }
    }

    public static function logout() {
        unset($_SESSION[self::USER_ID]);
    }

    public static function setUser($userId) {
        if (session_status() != PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION[self::USER_ID]) || $_SESSION[self::USER_ID] != $userId) {

            if ($userId == self::ANONYMOUS_USER_ID) {
                $_SESSION[self::USER_ID] = $userId;
                return new User(self::ANONYMOUS_USER_ID, self::ANONYMOUS_USER_NAME);
            }

            $userQuery = 'SELECT id, username FROM users WHERE id = :userId';
            $stmt = Database::getDB()->prepare($userQuery);
            $stmt->bindValue('userId', $userId);
            $stmt->execute();
            $userResults = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userResults) {
                // error_log('[mtapi][User][login]::$userResults ' . print_r($userResults, true));

                $username = $userResults['username'];

                $recordLoginQuery = 'INSERT INTO user_logins(user_id) VALUES(:userId)';
                $stmt = Database::getDB()->prepare($recordLoginQuery);
                $stmt->bindValue('userId', $userId);
                $stmt->execute();

                $_SESSION[self::USER_ID] = $userId;

                return new User($userId, $username);
            }
        }
    }

}