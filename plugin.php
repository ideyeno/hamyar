<?php

	// hamyarSTX / همیار شیرترانیکس
	// by Nima Saberi / توسط نیما صابری
	
	// www.Sharetronix.ir
	// تمامی حقوق برای شیرترانیکس فارسی محفوظ می باشد
	// 09119303114 - donbaler@gmail.com

	class hamyar extends plugin
	{	
	
		public function __construct()
		{
			parent::__construct();
			global $C;		
			$this->pluginfo = $this->manifest();
			$this->rtl = (isset($C->RTL) ? intval($C->RTL) : 1);
			$this->debug = (isset($C->DEBUG) ? intval($C->DEBUG) : 0);
			$this->debug_ip = (isset($C->DEBUG_IP) ? strip_tags($C->DEBUG_IP) : '127.0.0.1');
			$this->pdate = (isset($C->PDATE) ? intval($C->PDATE) : 0);
			$this->pdate_type = (isset($C->PDATE_TYPE) ? intval($C->PDATE_TYPE) : 1);
			$this->pdate_format = (isset($C->PDATE_FORMAT) ? strip_tags($C->PDATE_FORMAT) : '%Y/%m/%d - %H:%M');
			if ( $this->pdate === 1 ) {
				require_once $C->PLUGINS_DIR.'hamyar/system/classes/class_pdate.php';
			}
			$C->SHARETRONIX_IR		= $this->pluginfo->version;
		}
		
		public function onPageLoad()
		{
			global $C, $page;
			
			// متن ادیتور
			//$this->setVar('in_group', '', true);
			
			// فراخوانی RTL/CSS
			if ( $this->rtl > 0 ) {
				if ($page->is_mobile) {
					$this->setVar( 'header_data', '<link href="'.$C->OUTSIDE_SITE_URL .'apps/hamyar/static/mobile_rtl.css?v='.$this->pluginfo->version.'" type="text/css" rel="stylesheet" />' );
				}else{
					$this->setVar( 'header_data', '<link href="'.$C->SITE_URL .'apps/hamyar/static/rtl.css?v='.$this->pluginfo->version.'" type="text/css" rel="stylesheet" />' );
				}
			}
			
			if ( $this->getCurrentController() == 'pluginstaller' ) {
				$this->setVar( 'header_data', '<link href="'.$C->SITE_URL .'apps/hamyar/static/pluginstaller.css?v='.$this->pluginfo->version.'" type="text/css" rel="stylesheet" />' );
			}
			
			if ( $this->user->is_logged ){
				
				$this->setVar( 'header_top_menu', '<li><a class="item-btn bizcard '.( $this->getCurrentController() === 'user' ? 'active' : '').'" data-userid="'.$this->user->id.'" href="'. $C->SITE_URL.$this->user->info->username.'"><span>پروفایل</span></a></li>' );
				
				if ( $this->user->info->is_network_admin > 0 ){
					
					if ( $this->debug > 0 ) {
						$C->DEBUG_USERS		= array($this->debug_ip);
						$C->DEBUG_CONSOLE  	= TRUE;
					}
					
					// اعلام بروزرسانی جدید
					if ( in_array($this->getCurrentController(), array('admin', 'general', 'hamyar', 'pluginstaller')) ) {
						$this->check_update();
					}
					
					// اضافه کردن لینک به منو
					$this->setVar( 'administration_left_menu', '<li><a class="item-btn '.( $this->getCurrentController() === 'general'? ' selected' : '').'" href="'. $C->SITE_URL .'plugin/hamyar/general"><span>همیار شیرترانیکس</span></a></li>' );
					$this->setVar( 'administration_left_menu', '<li><a class="item-btn '.( $this->getCurrentController() === 'pluginstaller'? ' selected' : '').'" href="'. $C->SITE_URL .'plugin/hamyar/pluginstaller"><span>مدیریت پلاگین</span></a></li>' );
					
					// نمایش جزئیات
					//$this->setVar( 'left_content_placeholder', 'وضعیت لود سرور : '.$this->get_load().'<br>' );
					//$this->setVar( 'left_content_placeholder', 'آخرین بروزرسانی : '.$this->check_update().'<br>' );
				}
			}
			
			// کپی رایت
			$this->setVar( 'stx_footer_link_abc', '&copy; قدرت گرفته توسط <a href="http://sharetronix.com" target="_blank">Sharetronix</a> <a href="http://sharetronix.ir" target="_blank">فارسی</a>', true );
			//$this->setVar( 'footer_placeholder', '&nbsp;&middot;&nbsp; مسئولیت ارسال‌ها با کاربران می‌باشد.', true );

		}
		
		public function onPostLoad( &$var )
		{
			if ( $this->pdate === 1 ) {
				global $C;
				if ( $this->pdate_type === 1 ) {
					$this->setVar( 'activity_permlink', '&nbsp;<a href="'.$C->SITE_URL.'view/post:'.$var[0]->post_id.'">'.pstrftime($this->pdate_format, $var[0]->post_date).'</a>', true );
				} else {
					//
				}
			}			
		}
		
		public function onPostCommentLoad( &$var )
		{
			if ( $this->pdate === 1 ) {	
				//global $C;
				if ( $this->pdate_type === 1 ) {
					$this->setVar( 'activity_comment_date', '&nbsp;'.pstrftime($this->pdate_format, $var[0]->comment_date), true );		
				}
			}				
		}
		
		public function manifest() {
			global $C, $cache, $db2, $page, $plugins_manager;
			$manifest_file = $C->PLUGINS_DIR.'hamyar/manifest.json';
			if (!is_file($manifest_file)) {
				//echo "همیار شیرترانیکس از فایل پیکربندی برخوردار نبوده یا معتبر نمی‌باشد!";
				//die();
				$db2->query("DELETE FROM plugins WHERE name='hamyar'");
				$db2->query("DELETE FROM plugins_tables WHERE owner='hamyar'");
				$plugins_manager->invalidateEventCache();
				invalidateCachedHTML();
				//$page->redirect('settings');
			}
			if ( $C->LANGUAGE == 'fa' ) {
				$languages_file = $C->INCPATH.'languages/fa/language.php';
				if (!is_file($languages_file)) {
					$db2->query('REPLACE INTO settings SET word="LANGUAGE", value="en"');
				}
			}
			$cachekey	= 'check_hamyar_manifest';
			$cached = $cache->get($cachekey);
			$manifest = '';
			if ($cached) {
				$manifest = $cached;
			}else{
				$manifest = json_decode(file_get_contents($manifest_file));
				$cache->set($cachekey, $manifest, 6*60*60);
			}
			return $manifest;
		}
		
		public function check_update() {
			global $C, $cache;
			$cachekey	= 'sharetronix_update_checker';
			$cached = $cache->get($cachekey);
			$result = '';
			if ($cached) {
				$result = $cached;
			}else{
				$url = 'http://sharetronix.ir/download/';
				$fields = array(
					'ver' => urlencode($C->VERSION),
					'url' => urlencode($C->SITE_URL),
					'email' => urlencode($C->SYSTEM_EMAIL),
					'lng' => urlencode($C->LANGUAGE)
				);
				$fields_string = '';
				foreach($fields as $key=>$value) {
					$fields_string .= $key.'='.$value.'&';
				}
				rtrim($fields_string, '&');
				$ch = curl_init();
				$timeout = 5;
				curl_setopt($ch,CURLOPT_URL, $url);
				curl_setopt($ch,CURLOPT_POST, count($fields));
				curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
				$result = curl_exec($ch);
				curl_close($ch);
				$cache->set($cachekey, $result, 12*60*60);
			}
			if ( isset ($result) ) {
				$check = explode(":", $result);
				if ( in_array($check[0], array('OK', 'ER')) ) {
					$version_check_result = FALSE;
					if ( version_compare($check[1], $C->VERSION, '>') ) {
						$title = 'نگارش '.$check[1].' شیرترانیکس منتشر شد!';
						$message = 'برای بروزرسانی به <b><a href="http://sharetronix.ir/download/" target="_blank">شیرترانیکس فارسی</a></b> رجوع نمایید ...';
						$version_check_result = TRUE;
					}elseif ( version_compare($check[3], $this->pluginfo->version, '>') ) {
						$title = 'نگارش '.$check[3].' همیار شیرترانیکس منتشر شد!';
						$message = 'برای بروزرسانی به <b><a href="http://sharetronix.ir/hamyar/" target="_blank">شیرترانیکس فارسی</a></b> رجوع نمایید ...';
						$version_check_result = TRUE;
					}elseif ( version_compare($check[4], $C->LNG_VER, '>') ) {
						$title = 'نگارش جدید بسته زبان فارسی منتشر شد!';
						$message = 'برای بروزرسانی بسته زبان <b><a href="'.$C->SITE_URL.'plugin/hamyar/general/?lng=update&ver='.$check[4].'">اینجا</a></b> کلیک کرده یا فایل مذکور را <b><a href="http://sharetronix.ir/lng/'.$check[4].'.zip" target="_blank">دانلود</a></b> نمایید.';
						$version_check_result = TRUE;
					}else{
						$version_check_result = FALSE;
					}
					if( $version_check_result ){
						$tpl = new template( array(), FALSE );
						$this->setVar('main_content_placeholder', $tpl->designer->okMessage($title, $message, TRUE) );
					}
				}
				return false;
			}
			return false;
		}
		
		public function get_feed() {
			global $C, $cache;
			$cachekey	= 'sharetronix_rss_feed';
			$data = '';
			$cached = $cache->get($cachekey);
			if ($cached) {
				$data = $cached;
			}else{
				$xml = ("http://feeds.feedburner.com/sharetronixir");
				$xmlDoc = new DOMDocument();
				$xmlDoc->load($xml);
				$x = $xmlDoc->getElementsByTagName('item');
				$data = '';
				for ($i=0; $i<=14; $i++) {
					$item_title = $x->item($i)->getElementsByTagName('title')->item(0)->childNodes->item(0)->nodeValue;
					$item_link = $x->item($i)->getElementsByTagName('guid') ->item(0)->childNodes->item(0)->nodeValue;
					$data .= "<a href='" . $item_link . "' target='_blank' class='blog-subject'>" . $item_title . "</a><br>";
				}
				$cache->set($cachekey, $data, 24*60*60);
			}
			return $data;
		}
		
		private function getFile($ver){
			$timeout = 5;
			$req = curl_init();
			curl_setopt($req, CURLOPT_URL, 'http://sharetronix.ir/lng/fa-ir_'.$ver.'.zip');
			curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($req, CURLOPT_CONNECTTIMEOUT, $timeout);
			$data = curl_exec($req);
			$info = curl_getinfo($req);
			curl_close($req);
			if($info['http_code'] !== 200) {
				return false;
			}
			global $C;
			$lang_file = $C->INCPATH . "tmp/".$ver.".zip";
			@file_put_contents($lang_file, $data);
			return $lang_file;
		}
		
		private function extractLangpack($file){
			global $C;
			$tmp_folder = $C->INCPATH . "tmp/" . md5(time()+1);
			if(is_dir($tmp_folder) == false) {
				mkdir($tmp_folder, 0777, true);
			}
			$zip = new ZipArchive();
			if($zip->open($file) === true)
			{
				$zip->extractTo($tmp_folder);
				$zip->close();
			}else{
				return false;
			}
			return $tmp_folder;
		}
		
		public function install($ver) {
			if (empty($ver)) {
				return false;
			}
			$ver = strip_tags($ver);
			global $C, $db2, $cache;
			require_once $C->INCPATH.'helpers/func_filesystem.php';
			$langfile = $this->getFile($ver);
			if ( !$langfile ) {
				return false;
			}
			$langfolder = $this->extractLangpack($langfile);
			if ( !$langfolder ) {
				return false;
			}
			if(is_dir($C->INCPATH.'languages/fa') ) {
				rrmdir($C->INCPATH.'languages/fa');
			}
			$to = $C->INCPATH . "languages/fa";
			if(!is_dir($to)) {
				mkdir($to, 0777, true);
			}
			rcopy($langfolder . "/fa", $to);
			$db2->query("INSERT INTO languages (langkey, installed, `version`) VALUES ('fa', 1, 5) ON DUPLICATE  KEY UPDATE installed = 1, `version` = 5");
			$db2->query('REPLACE INTO settings SET word="LANGUAGE", value="fa"');
			$db2->query('REPLACE INTO settings SET word="LNG_VER", value="'.$ver.'"');
			$cache->del('check_hamyar_manifest');
		}
		
	}
	
?>