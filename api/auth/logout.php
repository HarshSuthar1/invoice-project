<?php
require_once '../../app/bootstrap.php';
Auth::logout();
header("Location: /Business%20project/public/index.php?page=login");
exit; 
