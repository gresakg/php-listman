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

if(!file_exists("config.php")) {
	die("Please, rename config.dist.php to config.php and edit the settings.");
}

$list = new listman($argv);

class listman {

	protected $args;

	public function __construct($args) {
		$this->connect_db();
		$this->args = $args;
		if(empty($args[1]) || !method_exists($this, $args[1])) {
			$this->help();
		} else {
			$method = $args[1];
			$this->$method();
		}
	}

	public function import() {
		$file = $this->args[2];
		if(!file_exists($file)) {
			$this->display("File not found!");
		} else {
			$this->set_csv_data($file);
			
		}

	}

	public function subscribe() {

	}

	public function validate() {

	}

	public function invalidate() {

	}

	public function unsubscribe() {

	}

	public function export() {

	}

	protected function set_csv_data($file) {
		$headers = array();
		$handle = fopen($file,"r");
		while($csv = fgetcsv($handle)) {
			if(empty($headers)) {
				$headers=$this->check_headers($csv);
			} else {
				$email = R::findOne('emails', ' email = ? ',[$csv[array_search('email',$headers)]]);
				if(empty($email)) {
					$email = R::dispense('emails');
				} else {
					// a specific operation should be needed for changing subscription status
					$csv[array_search('subscriber',$headers)] = property_exists($this, 'subscriber')?$email->subscriber:$csv[array_search('subscriber',$heades)];
				}
				$csv[array_search('valid',$headers)] = $this->sanatize_email($csv[array_search('email',$headers)]);
				foreach($csv as $key => $item) {
					$email->{$headers[$key]} = $item;
				}
				R::store($email);
			}
		}
		//var_dump($data);

	}

	protected function check_headers($csv) {
		if(!in_array('email',$csv)) {
			$this->display('The csv file is missing the emails column header. Emails are mandatory.');
			die;
		} else {
			return $csv;
		}
	}

	protected function sanatize_email($email){
		$valid = true;
		if(!$this->check_format($email)) {
			$error[] = "Email $email does not validate.";
			$valid=false;
		}
		if(!$this->verify_host($email)) {
			$error[] = "Emails $email host does not validate!";
			$valid = false;
		}
		return $valid;
	}

	public function check_format($email) {
		return preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $email);
	}

	public function verify_host($email) {
		$domain = substr(strrchr($email, "@"), 1);
		$host = R::findOne('hosts',"host = ? ",[$domain]);
		if(empty($host)) {
			$dns = (int) checkdnsrr($domain);
			$mx = (int) getmxrr($domain);
			$host = R::dispense('hosts');
			$host->host = $domain;
			$host->dns = $dns;
			$host->mx = $mx;
			R::store($host);
		}
		return (bool) $host->mx;
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
		require_once "config.php";
		extract($config);
		R::setup("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
	}

	public function __call($name, $args) {
		$this->display("The command ". $name . " does not exist.");
		$this->help();
	}

}
