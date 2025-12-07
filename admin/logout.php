<?php
session_start();
session_destroy();
header("Location: http://localhost/echoes%20of%20memories/index.php");
exit();
?>