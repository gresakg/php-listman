#!/usr/bin/php
<?php

/**
 * php cli tool for managing mail lists
 *
 * 1. import csv (merge with existing)
 * 2. validate hosts and emails
 * 3. invalidate or unsubscribe an email or a list of emails
 * 4. export valid mails
 * 
 */

define('nl' , "\n");

$list = new listman($argv);



class listman {

	protected $args;

	public function __construct($args) {
		$this->connect_db();
		$this->args = $args;
		if(empty($args[1])) {
			$this->help();
		} else {
			$this->$args[1];
		}
	}

	public function help() {
		$this->display("Listman is a command line tool for managing mailing lists.");
	}

	protected function display($text) {
		echo nl;
		echo $text;
		echo nl;

	}

	protected function connect_db() {
		require_once "rb.php";
		R::setup('mysql:host=localhost;dbname=emails', 'gregor', 'dangajag');
	}

	public function __call($name, $args) {
		$this->display("The command ". $name . " does not exist.");
		$this->help();
	}

}
