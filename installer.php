<?php 

class MyInstaller extends Installer {
	
	public function MyInstaller(){
		parent::Installer();
	}
	
	public function up(){
		global $C, $db1;
		$manifest_file = $C->PLUGINS_DIR.'hamyar/manifest.json';
		if (!is_file($manifest_file)) {
			echo "همیار شیرترانیکس از فایل پیکربندی برخوردار نبوده یا معتبر نمی‌باشد!";
			die();
		}
		$languages_file = $C->INCPATH.'languages/fa/language.php';
		if (is_file($languages_file)) {
			$db1->query('REPLACE INTO settings SET word="LANGUAGE", value="fa"');
			$db1->query('REPLACE INTO settings SET word="PDATE", value="1"');
		}
		$db1->query('REPLACE INTO settings SET word="PDATE_FORMAT", value="%Y/%m/%d - %H:%M"');
		$db1->query('REPLACE INTO settings SET word="DEF_TIMEZONE", value="Asia/Tehran"');
		$db1->query('REPLACE INTO settings SET word="PDATE_TYPE", value="1"');
		$db1->query('REPLACE INTO settings SET word="RTL", value="1"');
		$manifest = json_decode(file_get_contents($manifest_file));
		$db1->query('REPLACE INTO settings SET word="LNG_VER", value="'.$manifest->code_version.'"');
	}
	
	public function down(){
		//
	}
	
}