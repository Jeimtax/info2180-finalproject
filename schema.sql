DROP DATABASE IF EXISTS dolphin_crm;
CREATE DATABASE dolphin_crm;
USE dolphin_crm;

DROP TABLE IF EXISTS `Users`;
CREATE TABLE `Users`(
    id INT AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(255),
    lastname VARCHAR(255),
    password VARCHAR(255),
    email VARCHAR(255),
    role VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

DROP TABLE IF EXISTS `Contacts`;
CREATE TABLE `Contacts` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    firstname VARCHAR(255),
    lastname VARCHAR(255),
    email VARCHAR(255),
    telephone VARCHAR(255),
    company VARCHAR(255),
    type VARCHAR(255), -- Sales Lead or Support
    assigned_to INT,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES Users(id),
    FOREIGN KEY (created_by) REFERENCES Users(id)
);

DROP TABLE IF EXISTS `Notes`;
CREATE TABLE `Notes` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact_id INT,
    comment TEXT,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contact_id) REFERENCES Contacts(id),
    FOREIGN KEY (created_by) REFERENCES Users(id)
);


INSERT INTO Users (firstname, lastname, password, email, role, created_at)
VALUES ('Admin', 'User', '$2y$10$SZhjoxrkZaRAk5v44n0KpuKelvCmlST6bx38YFVe.g1wlJC.SNpay', 'admin@project2.com', 'Administrator', NOW());