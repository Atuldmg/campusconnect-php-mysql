<?php
/**
 * CampusConnect - User Logout Endpoint
 */

require_once __DIR__ . '/includes/auth.php';

logout_user();

set_flash('info', 'You have been successfully signed out of CampusConnect.');
header('Location: ' . url('login.php'));
exit;
