<?php
    $conn = new mysqli('localhost', 'driver', 'driver', 'driver');
    
    $conn->query('DROP TABLE IF EXISTS shop');
    $conn->query('DROP TABLE IF EXISTS driver');
    $conn->query('DROP TABLE IF EXISTS oauth');
    
    $conn->query('CREATE TABLE shop (
        id VARCHAR(10) PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        address VARCHAR(200) NOT NULL,
        latitude DECIMAL(23, 20) NOT NULL,
        longitude DECIMAL(23, 20) NOT NULL,
        url VARCHAR(600) UNIQUE NOT NULL
    )');
    
    $conn->query('CREATE TABLE driver (
        id INTEGER PRIMARY KEY DEFAULT 0,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        latitude DECIMAL(23, 20) NOT NULL DEFAULT 40.244444,
        longitude DECIMAL(23, 20) NOT NULL DEFAULT -111.660833,
        token VARCHAR(60) NOT NULL,
        last_delivery_id VARCHAR(60) NOT NULL,
        last_shop_url VARCHAR(600) NOT NULL,
        last_estimated_time DECIMAL(7, 2) NOT NULL
    )');
    $conn->query('INSERT INTO driver () VALUES ()');
    
    $conn->query('CREATE TABLE oauth(
        id INTEGER PRIMARY KEY DEFAULT 0,
        client_id VARCHAR(60) NOT NULL,
        client_secret VARCHAR(60) NOT NULL,
        account_sid VARCHAR(60) NOT NULL,
        auth_token VARCHAR(60) NOT NULL,
        phone VARCHAR(20) NOT NULL
    )');
    $conn->query('INSERT INTO oauth () VALUES ()');
    
    $conn->close();
    
    header('Location: index.php');
?>