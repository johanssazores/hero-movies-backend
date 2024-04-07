<?php
/*
Plugin Name: Hero Movies
Description: Hero Movies A Simple Restful WP API.
Version: 1.0
Author: Johanssen Azores
*/

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Init plugin
use HeroMovies\HeroMoviesPostType;
use HeroMovies\HeroMoviesBackend;
use HeroMovies\HeroMoviesRestApi;

HeroMoviesPostType::register();
HeroMoviesBackend::add_menu();
HeroMoviesRestApi::register_endpoints();