<?php
session_start();
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');
echo "Сессия полностью стерта. <a href='login.php'>Зайти заново</a>";
?>