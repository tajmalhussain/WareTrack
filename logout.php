<?php
/**
 * WareTrack - Session Sign Out Handler
 */

require_once __DIR__ . '/includes/functions.php';

logoutUser();
header('Location: login.php');
exit;
