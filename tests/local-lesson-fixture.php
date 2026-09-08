<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST']='aeluong-lms.local'; $_SERVER['REQUEST_URI']='/';
require dirname(__DIR__).'/wp-load.php';
if (wp_parse_url(home_url(),PHP_URL_HOST)!=='aeluong-lms.local') { exit(1); }
add_filter('pre_wp_mail','__return_true');
wp_set_current_user(get_user_by('login','admin')->ID);
$fixture=get_option('lms_lesson_ui_fixture');
if (!$fixture) {
 $fixture=['courses'=>[],'sections'=>[]];
 $pass=wp_generate_password(24,false);
 $uid=wp_insert_user(['user_login'=>'lms_lesson_ui_'.strtolower(wp_generate_password(6,false)),'user_pass'=>$pass,'role'=>'student']);
 if(is_wp_error($uid)){throw new Exception($uid->get_error_message());}
 $fixture['user']=$uid; $fixture['password']=$pass;
 foreach(['A','B'] as $name){
  $cid=wp_insert_post(['post_type'=>'lp_course','post_status'=>'publish','post_title'=>'[Test local] Lesson dùng chung '.$name]);
  $model=\LearnPress\Models\CourseModel::find($cid,false);
  $post=new \LearnPress\Models\CoursePostModel($model);
  $section=$post->add_section(['section_name'=>'Chương thử nghiệm '.$name]);
  $fixture['courses'][]=$cid; $fixture['sections'][]=$section->get_section_id();
  lms_site_core_enroll_user_in_course($uid,$cid);
 }
 $fixture['lesson']=wp_insert_post(['post_type'=>'lp_lesson','post_status'=>'publish','post_title'=>'[Test local] Đối chiếu typography','post_content'=>wp_slash(get_post_field('post_content',59))]);
 $section=\LearnPress\Models\CourseSectionModel::find($fixture['sections'][0],$fixture['courses'][0],false);
 $section->add_items(['items'=>[['id'=>$fixture['lesson'],'type'=>'lp_lesson']]]);
 update_option('lms_lesson_ui_fixture',$fixture,false);
}
$fixture['login']=get_userdata($fixture['user'])->user_login;
$fixture['course_urls']=array_map('get_permalink',$fixture['courses']);
$fixture['lesson_url']=\LearnPress\Models\CourseModel::find($fixture['courses'][0],false)->get_item_link($fixture['lesson']);
echo wp_json_encode($fixture,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
