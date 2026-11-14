<?php
require_once __DIR__ . '/../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    cerrar_sesion();
}
header('Location: inicio.php');
exit;
