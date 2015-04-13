<?php 

class MyInstaller extends Installer {
	
	public function MyInstaller(){
		parent::Installer();
	}
	
	public function up(){
		global $C, $db1;
		$languages_file = $C->INCPATH.'languages/fa/language.php';
		if (is_file($languages_file)) {
			$db1->query('REPLACE INTO settings SET word="LANGUAGE", value="fa"');
			$db1->query('REPLACE INTO settings SET word="PDATE", value="1"');
		}
		$db1->query('REPLACE INTO settings SET word="PDATE_FORMAT", value="%Y/%m/%d - %H:%M"');
		$db1->query('REPLACE INTO settings SET word="DEF_TIMEZONE", value="Asia/Tehran"');
		$db1->query('REPLACE INTO settings SET word="PDATE_TYPE", value="1"');
		$db1->query('REPLACE INTO settings SET word="RTL", value="1"');
	}
	
	public function down(){
		//
	}
	
}