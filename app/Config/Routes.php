<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('firebase/config', 'FirebaseController::getConfig');
$routes->get('firebase/test', 'FirebaseController::testConnection');
$routes->get('pelanggan', 'Pelanggan::index');
$routes->get('dapur', 'Dapur::index');
