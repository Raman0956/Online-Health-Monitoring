<?php

// Abstract User class
abstract class User {
    protected $userID;
    protected $name;
    protected $email;
    protected $phoneNumber;
    protected $password;
    protected $userType;

    public function __construct($userID, $name, $email, $phoneNumber, $password, $userType) {
        $this->userID = $userID;
        $this->name = $name;
        $this->email = $email;
        $this->phoneNumber = $phoneNumber;
        $this->password = $password;
        $this->userType = $userType;
    }

    // Abstract method for login, must be implemented by subclasses
    abstract public function login($conn, $email, $password);

    // Method to log out the user
    public function logout() {
        echo "$this->name logged out successfully.\n";
    }

    // Getters
    public function getUserID() {
        return $this->userID;
    }

    public function getName() {
        return $this->name;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getPhoneNumber() {
        return $this->phoneNumber;
    }

    public function getUserType() {
        return $this->userType;
    }
}
?>