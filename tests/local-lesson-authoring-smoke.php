<?php
/** Local-only regression checks with temporary data removed in finally. */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$_SERVER['HTTP_HOST']='aeluong-lms.local';$_SERVER['REQUEST_URI']='/';require dirname(__DIR__).'/wp-load.php';
if(wp_parse_url(home_url(),PHP_URL_HOST)!=='aeluong-lms.local'){exit(1);}
add_filter('pre_wp_mail','__return_true');
$admin=get_user_by('login','admin');wp_set_current_user($admin->ID);
function author_check($ok,$label){if(!$ok){throw new RuntimeException($label);}echo "PASS $label\n";}
$lesson=0;$section=null;$fail=false;
try{
 $lesson=wp_insert_post(['post_type'=>'lp_lesson','post_status'=>'publish','post_title'=>'[Temporary test] Shared lesson','post_content'=>'<p><span style="font-family:Arial;font-size:24px;color:#ff0000"><em>Typography test</em></span></p>']);
 author_check(lms_site_core_place_lesson($lesson,13,1,0)===true,'Assign to first course');
 author_check(lms_site_core_place_lesson($lesson,33,2,0)===true,'Share same lesson with second course');
 author_check(count(lms_site_core_lesson_locations($lesson))===2,'One lesson has two native relationships');
 author_check(lms_site_core_place_lesson($lesson,33,2,2)===true,'Repeated save does not duplicate');
 author_check(is_wp_error(lms_site_core_place_lesson($lesson,13,2,1)),'Reject section belonging to another course');
 author_check(is_wp_error(lms_site_core_place_lesson($lesson,13,1,0)),'Reject stale placement');
 $section=(new \LearnPress\Models\CoursePostModel(\LearnPress\Models\CourseModel::find(13,false)))->add_section(['section_name'=>'[Temporary test] Move']);
 author_check(lms_site_core_place_lesson($lesson,13,$section->get_section_id(),1)===true,'Move within one course');
 author_check(count(lms_site_core_lesson_locations($lesson))===2,'Move retains other course');
 author_check(lms_site_core_place_lesson($lesson,13,0,$section->get_section_id())===true,'Remove one relationship');
 author_check(count(lms_site_core_lesson_locations($lesson))===1,'Removal preserves shared lesson');
 $data=['course_id'=>13,'item_type'=>'lms_shared_lesson'];
 $result=\LearnPress\TemplateHooks\Course\AdminEditCurriculumTemplate::render_list_items_not_assign($data);
 author_check(str_contains($result->content,'data-id="'.$lesson.'"'),'Shared bank includes lesson assigned elsewhere');
 $result=\LearnPress\TemplateHooks\Course\AdminEditCurriculumTemplate::render_list_items_not_assign(['course_id'=>33,'item_type'=>'lms_shared_lesson']);
 author_check(!str_contains($result->content,'data-id="'.$lesson.'"'),'Shared bank excludes lesson already in current course');
 wp_set_current_user(0);
 author_check(is_wp_error(lms_site_core_place_lesson($lesson,13,1,0)),'Guest cannot mutate curriculum');
 wp_set_current_user(get_user_by('login','lms_student_allowed')->ID);
 author_check(is_wp_error(lms_site_core_place_lesson($lesson,13,1,0)),'Student cannot mutate curriculum');
 $course=\LearnPress\Models\CourseModel::find(13,false);
 $buttons=lms_site_core_course_detail_buttons(['btn_buy'=>'','btn_learning'=>''],$course,null);
 author_check(str_contains($buttons['btn_buy'],'Tiếp tục học'),'Enrolled Student gets resume action');
 author_check(str_contains($buttons['btn_buy'],'/lessons/'),'Resume points into course lesson');
 wp_set_current_user($admin->ID);
 author_check(!apply_filters('use_block_editor_for_post_type',true,'lp_lesson'),'Lesson uses native continuous editor');
 author_check(str_contains(get_post_field('post_content',$lesson),'color:#ff0000'),'Stored typography unchanged');
 echo "All authoring checks passed\n";
}catch(Throwable $e){$fail=true;fwrite(STDERR,$e->getMessage()."\n");}
finally{
 wp_set_current_user($admin->ID);
 if($lesson){foreach(lms_site_core_lesson_locations($lesson) as $loc){lms_site_core_place_lesson($lesson,(int)$loc->section_course_id,0,(int)$loc->section_id);}wp_delete_post($lesson,true);}
 if($section){$section->delete();}
}
exit($fail?1:0);
