<?php
class SessionManager
{
  public function __construct()
  {
    if (session_status() == PHP_SESSION_NONE) {
      session_start();
    }
  }

  public function login($user)
  {
    $_SESSION['user'] = $user;
  }

  public function logout()
  {
    session_unset();
    session_destroy();
  }

  public function isLoggedIn()
  {
    return isset($_SESSION['user']);
  }

  public function getUser()
  {
    return $_SESSION['user'] ?? null;
  }
}
