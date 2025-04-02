<?php
session_start();
session_destroy();
header("Location: /kasir_restoran/");
exit;
?>
