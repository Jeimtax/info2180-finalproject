<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$page_title = 'Add New Contact';
$current_page = 'add-contact';
$users = getAllUsers();

ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dolphin CRM - Add New Contact</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="main-content">
        <div class="container">
            <div class="page-header">
               