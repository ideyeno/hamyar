<?php
	
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
	
	require_once( $C->INCPATH.'helpers/func_languages.php' );
	
	if ( isset($_GET['lng'], $_GET['ver']) && $_GET['lng'] == 'update' ) {
		$hamyar = new hamyar();
		$version = (isset($_GET['ver']) ? strip_tags($_GET['ver']) : false);
		if ($version) {
			$hamyar->install($version);
			$this->redirect('plugin/hamyar/general');
		}
	}
	
	$s	= new stdClass;
	$db2->query('SELECT word, value FROM settings');
	while($o = $db2->fetch_object()) {
		$s->{stripslashes($o->word)}	= stripslashes($o->value);
	}
	
	$menu_timezones	= array();
	if( floatval(substr(phpversion(),0,3)) >= 5.2 ) {
		$tmp	= array();
		foreach(DateTimeZone::listIdentifiers() as $v) {
			if( substr($v, 0, 4) == 'Etc/' ) { continue; }
			if( FALSE === strpos($v, '/') ) { continue; }
			$sdf	= new DateTimeZone($v);
			if( ! $sdf ) { continue; }
			$tmp[$v]	= $sdf->getOffset( new DateTime("now", $sdf) );
		}
		asort($tmp);
		foreach($tmp as $k=>$v) {
			if ($k == 'Asia/Tehran') {
				$menu_timezones[$k]	= 'آسیا / تهران';
				continue;
			}
			$menu_timezones[$k]	= str_replace(array('/','_'), array(' / ',' '), $k);
		}
		asort($menu_timezones);
	}
	
	$menu_languages	= array();
	foreach(get_available_languages(FALSE) as $k=>$v) {
		if ($k == 'fa') {
			$menu_languages[$k]	= 'فارسی / Persian';
			continue;
		}
		$menu_languages[$k]	= $v->name;
	}
	
	$pdate_select	= array(
		'1' => 'فعال',
		'0' => 'غیرفعال',
	);
	
	$debug_select	= array(
		'1' => 'فعال',
		'0' => 'غیرفعال',
	);
	
	$pdate_type_select	= array(
		'1' => 'تاریخ و ساعت',
		'2' => 'زمان سپری شده',
	);
	
	$rtl_select	= array(
		'1' => 'فراخوانی شود',
		'0' => 'فراخوانی نشود',
	);
	
	$network_name	= $s->SITE_TITLE;
	$def_language	= $s->LANGUAGE;
	$def_timezone	= isset($s->DEF_TIMEZONE) ? $s->DEF_TIMEZONE : $C->DEF_TIMEZONE;
	$post_maxlength	= isset($s->POST_MAX_SYMBOLS) ? $s->POST_MAX_SYMBOLS : 1000;
	$pdate_post	= (isset($s->PDATE) ? $s->PDATE : 0);
	$debug	= (isset($s->DEBUG) && !empty($s->DEBUG)) ? $s->DEBUG : 0;
	$debug_ip	= (isset($s->DEBUG_IP)) ? $s->DEBUG_IP : '127.0.0.1';
	$pdate_format	=  (isset($s->PDATE_FORMAT) ? $s->PDATE_FORMAT : '%Y/%m/%d - %H:%M');
	$pdate_type	=  (isset($s->PDATE_TYPE) ? $s->PDATE_TYPE : 1);
	$rtl	=  (isset($s->RTL) ? $s->RTL : 1);
	
	$submit	= FALSE;
	$error	= FALSE;
	$errmsg	= '';
	$refresh_page = FALSE;
	
	if( isset($_POST['sbm']) ) {
		$submit	= TRUE;
		
		$plugins_manager->onAdminSettingsSubmit();
		if( !$plugins_manager->isValidEventCall() ){
			$error = TRUE;
			$errmsg = $plugins_manager->getEventCallErrorMessage();
		}
		
		if( !$error ){

			if( isset($_POST['pdate']) && in_array(intval($_POST['pdate']), array(1, 0)) ) {
				$pdate_post	= intval($_POST['pdate']);
			}
			if( isset($_POST['rtl']) && in_array(intval($_POST['rtl']), array(1, 0)) ) {
				$rtl	= intval($_POST['rtl']);
			}
			if( isset($_POST['network_post_length']) && !empty($_POST['network_post_length']) ) {
				$post_maxlength	= intval($_POST['network_post_length']);
			}
			if( isset($_POST['debug']) && in_array(intval($_POST['debug']), array(1, 0)) ) {
				$debug	= intval($_POST['debug']);
			}
			if( isset($_POST['debug_ip']) ) {
				$debug_ip	= strip_tags($_POST['debug_ip']);
				if( ! preg_match('/^([0-9]+)\.([0-9]+)\.(([0-9]+)\.([0-9]+)?)?$/', $debug_ip, $m) ) {
					$debug_ip = '127.0.0.0';
				}
			}
			if( isset($_POST['network_timezone']) && isset($menu_timezones[$_POST['network_timezone']]) ) {
				$def_timezone	= strip_tags($_POST['network_timezone']);
			}
			if( isset($_POST['pdate_format']) ) {
				if( !empty($_POST['pdate_format'])  ) {
					$pdate_format	= strip_tags($_POST['pdate_format']);
				} else {
					$pdate_format	= '%Y/%m/%d - %H:%M';
				}
			}
			if( isset($_POST['pdate_type']) && in_array(($_POST['pdate_type']), array(1, 2)) ) {
				$pdate_type	= intval($_POST['pdate_type']);
			}
			
			if( isset($_POST['network_language']) && isset($menu_languages[$_POST['network_language']]) ) {
				$def_language	= strip_tags($_POST['network_language']);
				$old	= $db2->fetch_field('SELECT value FROM settings WHERE word="LANGUAGE" LIMIT 1');
				if( $old != $def_language ) {
					$db2->query('REPLACE INTO settings SET word="LANGUAGE", value="'.$db2->e($def_language).'" ');
					$db2->query('UPDATE users SET language="'.$db2->e($def_language).'" ');
					$this->network->get_user_by_id($this->user->id, TRUE);
				}
				
				$refresh_page = ($s->LANGUAGE != $def_language);
			}
			
			$db2->query('REPLACE INTO settings SET word="DEF_TIMEZONE", value="'.$db2->e($def_timezone).'" ');
			$db2->query('REPLACE INTO settings SET word="POST_MAX_SYMBOLS", value="'.$db2->e($post_maxlength).'" ');
			$db2->query('REPLACE INTO settings SET word="PDATE", value="'.$db2->e($pdate_post).'" ');
			$db2->query('REPLACE INTO settings SET word="PDATE_FORMAT", value="'.$db2->e($pdate_format).'" ');
			$db2->query('REPLACE INTO settings SET word="PDATE_TYPE", value="'.$db2->e($pdate_type).'" ');
			$db2->query('REPLACE INTO settings SET word="RTL", value="'.$db2->e($rtl).'" ');
			$db2->query('REPLACE INTO settings SET word="DEBUG", value="'.$db2->e($debug).'" ');
			$db2->query('REPLACE INTO settings SET word="DEBUG_IP", value="'.$db2->e($debug_ip).'" ');
			
			$this->network->load_network_settings();
			$this->redirect('plugin/hamyar/general/msg:saved');
		}
	}
	
	if( $refresh_page ){
		$this->redirect('plugin/hamyar/general/msg:saved');
	}
	
	$tpl = new template( array('page_title' => 'تنظیمات همیار شیرترانیکس', 'header_page_layout'=>'sc') );
	
	$tpl->initRoutine('AdminLeftMenu', array());
	$tpl->routine->load();

	
	if( ($submit && !$error) || $this->param('msg') == 'saved' ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('admgnrl_okay'), $this->lang('admgnrl_okay_txt') ) );
	}else if( $submit && $error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('admemld_rem_error'), $errmsg) );
	}
	
	$table = new tableCreator();
	$table->form_title = 'تنظیمات همیار شیرترانیکس';
	
	$rows = array(
		$table->selectField( 'میلادی به شمسی', 'pdate', $pdate_select, $pdate_post ),
		$table->selectField( 'نوع نمایش زمان', 'pdate_type', $pdate_type_select, $pdate_type ),
		(intval($pdate_type) == 1) ? $table->inputField( 'فرمت نمایش زمان', 'pdate_format', $pdate_format ) : '',
		$table->selectField( 'زبان پیشفرض', 'network_language', $menu_languages, $def_language ),
		$table->selectField(  'بازه زمانی', 'network_timezone', $menu_timezones, $def_timezone ),
		$table->selectField(  'افزودن RTL.css', 'rtl', $rtl_select, $rtl ),
		$table->inputField(  'تعداد کاراکتر پست', 'network_post_length', $post_maxlength ),
		$table->selectField(  'وضعیت دیباگ', 'debug', $debug_select, $debug),
		(intval($debug) == 1) ? $table->inputField(  'آی‌پی دیباگ', 'debug_ip', $debug_ip ) : '',
		//$table->checkBox( '', array( array('check4update', 1, $this->lang('admgnrl_frm_check4update'), $check4update ) ) ),
		$table->submitButton( 'sbm', 'ذخیره' )
	);

	$tpl->layout->setVar('main_content', $table->createTableInput( $rows ));
	
	$num_pu = intval($db2->fetch_field('SELECT COUNT(id) FROM post_userbox'));
	$type = '<font color="green">(لزومی به تخلیه نیست)</font>';
	if ( $num_pu > 100000 ) {
		$type = '<font color="red">(تخلیه کنید)</font>';
	}
	
	$folder = '';
	$folder .= '<br><h3 class="section-title">بهینه سازی دیتابیس</h3>';
	$folder .= '<div style="text-align: justify; padding: 5px 7px; font-size: 12px;">';
	$folder .= 'تیبل posts_userbox در تمامی سطوح از <a href="http://sharetronix.ir/optimize" target="_blank" style="background: #ededed;">بهینه سازی شیرترانیکس</a> از اهمیت بالایی برخوردار بوده و افزایش سریع رکوردهای این تیبل یکی از مباحثی است که همواره در کاهش سرعت شبکه مطرح است. با عمل تخلیه‌ی posts_userbox، رکوردهای این تیبل که گاهاً به میلیون‌ها مورد می‌رسد حذف شده و علاوه بر مشهود شدن افزایش سرعت شبکه، برگه‌ی آخرین فعالیت‌های داشبورد (تب All) خالی می‌گردد، که این به معنای حذف ارسال‌های کاربران نمی‌باشد.<br><br>';
	
	$folder .= '<a class="btn blue mybtn" href="'.$C->SITE_URL.'plugin/hamyar/general/?optimize=posts_userbox">تخلیه تیبل posts_userbox</a>';
	$folder .= 'تعداد رکورد : <b>'.$num_pu.'</b> '.$type;
	$folder .= '</div>';
	$tpl->layout->setVar('main_content', $folder);
	
	if ( isset($_GET['optimize']) && $_GET['optimize'] == 'posts_userbox' ) {
		$db2->query('TRUNCATE post_userbox');
		$this->redirect('plugin/hamyar/general/optimize:ok');
	}
	
	if ( $this->param('optimize') == 'ok' ) {
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage('بهینه سازی انجام شد!', 'تیبل posts_userbox به درستی تخلیه شد.', TRUE) );
	}
	
	$tpl->display();
?>