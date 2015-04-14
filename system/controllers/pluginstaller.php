<?php
	
	// PlugInstaller
	// by Nima Saberi / توسط نیما صابری
	
	// www.Sharetronix.ir
	// تمامی حقوق برای شیرترانیکس فارسی محفوظ می باشد
	// 09119303114 - donbaler@gmail.com

	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if( !$this->user->is_logged ) {
		$this->redirect('signin');
	}
	$db2->query('SELECT 1 FROM users WHERE id="'.$this->user->id.'" AND is_network_admin=1 LIMIT 1');
	if( 0 == $db2->num_rows() ) {
		$this->redirect('dashboard');
	}
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/admin.php');
	
	$tpl = new template( array('page_title' => $C->SITE_TITLE.' - مدیریت پلاگین', 'header_page_layout'=>'sc') );
	$tpl->initRoutine('AdminLeftMenu', array());
	$tpl->routine->load();

	$tabs		= array('view', 'install', 'uninstall');
	$C->STX_API = 'http://sharetronix.ir/';
	$C->SHARETRONIX_IR = (isset($C->SHARETRONIX_IR) ? $C->SHARETRONIX_IR : '');
	
	$tab = '';
	$css = '';
	if (!class_exists('hamyar')) {
		require_once $C->PLUGINS_DIR.'hamyar/plugin.php';
		$css = '<link href="'.$C->SITE_URL .'apps/hamyar/static/pluginstaller.css" type="text/css" rel="stylesheet" />';
	} 
	$folder = $css;
	$mnfst = $css;
	if( $this->param('tab') && in_array($this->param('tab'), $tabs) ) {
		$tab	= $this->param('tab');
	}else{
		$folder .= '<h3 class="section-title1" >مدیریت پلاگین</h3><div class="afterttl">';
		if (is_dir($C->PLUGINS_DIR)){
			$dp = opendir($C->PLUGINS_DIR);
			while ($entry = readdir($dp)) {
				if (is_dir($C->PLUGINS_DIR.$entry)){
					if ( $entry != '.' ) {
						if ( $entry != '..' ) {
							$folder .= '<a href="'.$C->SITE_URL.'plugin/hamyar/pluginstaller/tab:view/folder:'.$entry.'"><div class="p-file1"><p><img src="'.$C->SITE_URL.'apps/hamyar/static/pluginstaller/view.png" /><b>'.$entry.'</b></p></div></a>';
						}
					}
				}
			} 
			closedir($dp);
		}
		if(dir_is_empty($C->PLUGINS_DIR)){
			$folder .= '<div class="p-file1"><p><img src="'.$C->SITE_URL.'apps/hamyar/static/pluginstaller/view.png" /><b dir="rtl">پلاگین در فولدر apps وجود ندارد.</b></p></div>';
		}
		if (!is_dir($C->PLUGINS_DIR)){
			$folder .= '<div class="p-file1"><p><img src="'.$C->SITE_URL.'apps/hamyar/static/pluginstaller/view.png" /><b dir="rtl">فولدر apps وجود ندارد.</b></p></div>';
		}
		$hamyar = new hamyar();
		$data = $hamyar->get_feed();
		$folder .=	'
			</div>
			<div class="p-login1">
				<center><img src="'.$C->SITE_URL.'apps/hamyar/static/pluginstaller/logo.png" /></center>
				<div class="news1">'.$data.'</div>
				<center>
					<a href="'.$C->STX_API.'" target="_blank"><div class="activi1" />شیرترانیکس فارسی</div></a>
					<a href="'.$C->STX_API.'forum" target="_blank"><div class="activi1" />مشاهده انجمن شیرترانیکس</div></a>
					<a href="'.$C->STX_API.'download/" target="_blank"><div class="activi1" />دریافت بسته‌های فریم‌ورک</div></a>
				</center>
			</div>
		';
		$tpl->layout->setVar('main_content', $folder);
	}
	if ($tab == 'view' && $this->param('folder')) {
		$IF_CAN_INSTALL = FALSE;
		$IF_CAN_UNINSTALL = FALSE;
		global $C, $plugins_manager, $user;
		$PLUGIN_DIR = $this->param('folder');
		$manifest_file = $C->PLUGINS_DIR.$PLUGIN_DIR."/manifest.json";
		$mnfst .= '<h3 class="section-title2" >مشخصات پلاگین '.$PLUGIN_DIR.'</h3>';
		if(!is_file($manifest_file)){
			$mnfst .= "پلاگین مورد نظر از فایل پیکربندی برخوردار نبوده یا معتبر نمی‌باشد.";
		}else{
			$r = $db2->fetch('SELECT * FROM `plugins` WHERE name="'.$PLUGIN_DIR.'"');
			if(count($r) > 0){
				$IF_CAN_UNINSTALL = TRUE;
			}else{
				$IF_CAN_INSTALL = TRUE;
			}
			$manifest_img = $C->PLUGINS_DIR.$PLUGIN_DIR."/screen.jpg";
			if(!is_file($manifest_img)){
				$img = $C->SITE_URL.'apps/hamyar/static/pluginstaller/screen.jpg';
			}else{
				$img = $C->SITE_URL.'apps/'.$PLUGIN_DIR."/screen.jpg";
			}
			$mnfst .= '<div class="p-file2">';
			$mnfst .= '<div style="float:right"><img src="'.$img.'" /></div>';
			$cachekey	= 'check_view_plugin_'.$PLUGIN_DIR;
			$cached = $cache->get($cachekey);
			if ($cached) {
				$manifest = $cached;
			}else{
				$manifest = json_decode(file_get_contents($manifest_file));
				$cache->set($cachekey, $manifest, 1*60*60);
			}
			if (!empty($manifest->plugin_name)) {
				$mnfst .= '<br><p><b>'.ucfirst($manifest->plugin_name).'</b>'.(empty($manifest->version)?'':'<strong>'.$manifest->version.'</strong>').'</p><br>';
			}
			if (!empty($manifest->descr)) {
				$mnfst .= '<em>Description:</em><p>'.$manifest->descr.'</p><br>';
			}
			if (!empty($manifest->author)) {
				$mnfst .= '<em>Author:</em><p>'.$manifest->author.'</p><br>';
			}
			if (!empty($manifest->email)) {
				$mnfst .= '<em>Email:</em><p>'.$manifest->email.'</p><br>';
			}
			if (!empty($manifest->url)) {
				$mnfst .= '<em>Web:</em><p>'.$manifest->url.'</p><br>';
			}
			if (!empty($manifest->mobile)) {
				$mnfst .= '<em>Mobile:</em><p>'.$manifest->mobile.'</p><br>';
			}
			$mnfst .= '</div>';
			$mnfst .= '<div class="p-controll2">';
			if ($IF_CAN_INSTALL) {
				$mnfst .= '<img src="'.$C->SITE_URL.'apps/hamyar/static/pluginstaller/view.png" /> <a href="'.$C->SITE_URL.'plugin/hamyar/pluginstaller/tab:install/plugin:'.$PLUGIN_DIR.'">Install</a>';
			}
			if ($IF_CAN_UNINSTALL) {
				$mnfst .= '<img src="'.$C->SITE_URL.'apps/hamyar/static/pluginstaller/uninstall.png" /> <a href="'.$C->SITE_URL.'plugin/hamyar/pluginstaller/tab:uninstall/plugin:'.$PLUGIN_DIR.'">Uninstall</a>';
			}
			$mnfst .= '<a href="'.$C->SITE_URL.'plugin/hamyar/pluginstaller"><div class="activi2" />بازگشت</div></a>';
			$mnfst .= '</div>';
		}
		$tpl->layout->setVar('main_content', $mnfst);
	}
	if ($tab == 'install' && $this->param('plugin')) {
		$IF_CAN_INSTALL = FALSE;
		$IF_CAN_UNINSTALL = FALSE;
		$PLUGIN_DIR = $this->param('plugin');
		$manifest_file = $C->PLUGINS_DIR.$PLUGIN_DIR."/manifest.json";
		$cachekey	= 'check_view_plugin_'.$PLUGIN_DIR;
		$cached = $cache->get($cachekey);
		if ($cached) {
			$manifest = $cached;
		}else{
			$manifest = json_decode(file_get_contents($manifest_file));
			$cache->set($cachekey, $manifest, 1*60*60);
		}
		$mnfst .= '<h3 class="section-title3" >نصب پلاگین '.$PLUGIN_DIR.'</h3>';
		if(!is_file($manifest_file)){
			$mnfst .= "پلاگین مورد نظر از فایل پیکربندی برخوردار نبوده یا معتبر نمی‌باشد.";
		}else{
			$r = $db2->fetch('SELECT * FROM `plugins` WHERE name="'.$PLUGIN_DIR.'"');
			if(count($r) > 0){
				$IF_CAN_UNINSTALL = TRUE;
			}else{
				$IF_CAN_INSTALL = TRUE;
			}
			if ($IF_CAN_INSTALL) {
				$install_file = $C->PLUGINS_DIR.$PLUGIN_DIR."/installer.php";
				$C->INSTALLER_PATH = $C->INCPATH."plugin_installer".DIRECTORY_SEPARATOR;
				require_once $C->INSTALLER_PATH.'Installer.php';
				if(is_file($install_file))	{
					include($install_file);
					MyInstaller::$mode = Installer::INSTALLER_MODE_INSTALL;
					MyInstaller::$manifest = $manifest;
					$inst = new MyInstaller();
					if(method_exists($inst, "up") && method_exists($inst, "down")){
						@$inst->up();
					}
					$mnfst .= '<font class="nokte3">نکته:</font> دیتابیس شبکه متناسب با تغییرات پلاگین بروز رسانی شد.<br><br>';
				}
				$plugins_manager->invalidateEventCache();
				invalidateCachedHTML();
				$db2->query("INSERT INTO plugins (name, is_installed, date_installed, installed_by_user_id) VALUES ('".$manifest->plugin_name."', 1, ".(time()).", '".$user->id."')");
				$mnfst .= '<b class="installok3" align="center">عملیات نصب پلاگین بدرستی صورت پذیرفت.</b>';
			}else{
				$mnfst .= '<b class="installerr3" align="center">این پلاگین پیش از این نصب شده و امکان نصب مجدد وجود ندارد.</b>';
			}
			$mnfst .= '<a href="'.$C->SITE_URL.'plugin/hamyar/pluginstaller"><div class="activi3" />رجوع به لیست</div></a>';
		}
		$tpl->layout->setVar('main_content', $mnfst);
	}
	function dir_is_empty($dir) {
		if (!is_readable($dir)) return NULL;
		return (count(scandir($dir)) == 2);
	}
	if ($tab == 'uninstall' && $this->param('plugin')) {
		$IF_CAN_INSTALL = FALSE;
		$IF_CAN_UNINSTALL = FALSE;
		$PLUGIN_DIR = $this->param('plugin');
		$manifest_file = $C->PLUGINS_DIR.$PLUGIN_DIR."/manifest.json";
		$cachekey	= 'check_view_plugin_'.$PLUGIN_DIR;
		$cached = $cache->get($cachekey);
		if ($cached) {
			$manifest = $cached;
		}else{
			$manifest = json_decode(file_get_contents($manifest_file));
			$cache->set($cachekey, $manifest, 1*60*60);
		}
		$mnfst .= '<h3 class="section-title3" >نصب پلاگین '.$PLUGIN_DIR.'</h3>';
		if(!is_file($manifest_file)){
			$mnfst .= "پلاگین مورد نظر از فایل پیکربندی برخوردار نبوده یا معتبر نمی‌باشد.";
		}else{
			$r = $db2->fetch('SELECT * FROM `plugins` WHERE name="'.$PLUGIN_DIR.'"');
			if(count($r) > 0){
				$IF_CAN_UNINSTALL = TRUE;
			}else{
				$IF_CAN_INSTALL = TRUE;
			}
			if ($IF_CAN_UNINSTALL) {
				$install_file = $C->PLUGINS_DIR.$PLUGIN_DIR."/installer.php";
				$C->INSTALLER_PATH = $C->INCPATH."plugin_installer".DIRECTORY_SEPARATOR;
				require_once $C->INSTALLER_PATH.'Installer.php';
				if(is_file($install_file))	{
					include($install_file);
					MyInstaller::$mode = Installer::INSTALLER_MODE_INSTALL;
					MyInstaller::$manifest = $manifest;
					$inst = new MyInstaller();
					if(method_exists($inst, "up") && method_exists($inst, "down")){
						@$inst->down();
					}
					$mnfst .= '<font class="nokte3">نکته:</font> تغییرات دیتابیس مرتبط با این پلاگین حذف شدند.<br><br>';
				}
				$db2->query('DELETE FROM plugins_installed WHERE marketplace_id="'.$manifest->plugin_name.'"');
				//@rmdir($C->PLUGINS_DIR.$manifest->plugin_name);
				$plugins_manager->invalidateEventCache();
				invalidateCachedHTML();
				$db2->query("DELETE FROM plugins WHERE name='".$manifest->plugin_name."'");
				$db2->query("DELETE FROM plugins_tables WHERE owner='".$manifest->plugin_name."'");
				$mnfst .= '<b class="installok3" align="center">عملیات حذف پلاگین بدرستی صورت پذیرفت.</b>';
			}else{
				$mnfst .= '<b class="installerr3" align="center">این پلاگین پیش از این حذف شده است.</b>';
			}
			$mnfst .= '<a href="'.$C->SITE_URL.'plugin/hamyar/pluginstaller"><div class="activi3" />رجوع به لیست</div></a>';
		}
		$tpl->layout->setVar('main_content', $mnfst);
	}
	
	$tpl->display();
		
?>