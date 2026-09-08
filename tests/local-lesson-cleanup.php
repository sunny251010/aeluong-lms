<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$_SERVER['HTTP_HOST']='aeluong-lms.local';$_SERVER['REQUEST_URI']='/';require dirname(__DIR__).'/wp-load.php';
if(wp_parse_url(home_url(),PHP_URL_HOST)!=='aeluong-lms.local'){exit(1);}
require_once ABSPATH.'wp-admin/includes/user.php';wp_set_current_user(get_user_by('login','admin')->ID);
$f=get_option('lms_lesson_ui_fixture');if(!$f){exit;}
foreach($f['courses'] as $id){if(get_post_type($id)!=='lp_course'||strpos(get_the_title($id),'[Test local] Lesson dùng chung ')!==0){throw new Exception('Fixture changed; refusing cleanup');}}
foreach($f['courses'] as $id){wp_delete_post($id,true);echo "Deleted test course $id\n";}
if(get_post_type($f['lesson'])==='lp_lesson'&&get_the_title($f['lesson'])==='[Test local] Đối chiếu typography'){wp_delete_post($f['lesson'],true);}
$u=get_userdata($f['user']);if($u&&strpos($u->user_login,'lms_lesson_ui_')===0){wp_delete_user($u->ID);}
delete_option('lms_lesson_ui_fixture');
