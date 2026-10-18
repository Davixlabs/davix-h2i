<?php
// Dependency-free autoloader for installs without Composer:  require 'sdk/php/autoload.php';
$base = __DIR__ . '/src/';
foreach (['Exceptions.php', 'Schema.php', 'Http.php', 'Generated/Enums.php', 'Generated/ErrorCodes.php', 'Generated/Api.php', 'Generated/ClientNamespaces.php', 'Client.php'] as $file) {
    require_once $base . $file;
}
