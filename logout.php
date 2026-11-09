<?php
session_start();
session_destroy();
header('Location: /cims/index.php');
exit;
