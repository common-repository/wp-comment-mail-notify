<?php
/*
Plugin Name: wp_comment_mail_notify
Version: 0.0.1
Plugin URI: http://www.phpsong.com
Author: 小松博客
Author URI: http://www.phpsong.com
Description: 显示前后台是否回复时邮件通知选项，内置回复邮件模板。开发这个插件的目的是为了修改留言email地址不正确的时候回复发邮件的后台修改成不发邮件。
*/
//下面要做成评论插件
add_action('wp_insert_comment','xiaosong_insert_comment_mail_notify',10,2);
function xiaosong_insert_comment_mail_notify($comment_ID,$commmentdata) {
    $comment_mail_notify = isset($_POST['comment_mail_notify']) ? 1 : 0;
    //存储在数据库里的字段名字，取出数据的就会用到
    update_comment_meta($comment_ID,'_comment_mail_notify',$comment_mail_notify);

}

//自动勾选 
function xiaosong_add_checkbox() {
  echo '<label for="comment_mail_notify" class="checkbox inline" style="padding-top:0"><input type="checkbox" name="comment_mail_notify" id="comment_mail_notify" value="comment_mail_notify" checked="checked"/>有人回复时邮件通知我</label>';
} 
add_action('comment_form','xiaosong_add_checkbox');


add_filter( 'manage_edit-comments_columns', 'xiaosong_comments_columns' );
function xiaosong_comments_columns( $columns ){
    $columns[ '_comment_mail_notify' ] = '是否回复时邮件通知';
    return $columns;
}

add_action( 'manage_comments_custom_column', 'xiaosong_output_comments_columns', 10, 2 );
function xiaosong_output_comments_columns( $column_name, $comment_id ){
    switch( $column_name ) {
    case "_comment_mail_notify" :
		$get_comment=get_comment_meta( $comment_id, '_comment_mail_notify', true );
		if($get_comment){
			$checked='checked="checked"';
		}
		echo '<input type="checkbox" name="comment_mail_notify" id="comment_mail_notify" value="1" '.$checked.'/>';
    break;
	}
}

//评论列表显示是否邮件
function xiaosong_create_comment_meta_box() {
    global $theme_name;
    if ( function_exists('add_meta_box') ) {
		add_meta_box( 'new-comment-meta-boxes', '自定义模块', 'xiaosong_new_comment_meta_boxes', 'comment', 'normal', 'high' );
	}
}

function xiaosong_new_comment_meta_boxes() {	
    global $comment_id;
    $meta_box_value = get_comment_meta($comment_id, '_comment_mail_notify', true);
        // 自定义字段标题
        echo'是否回复时邮件通知';
    if($meta_box_value){
		$checked='checked="checked"';
	}
	echo '<input type="checkbox" name="comment_mail_notify" id="comment_mail_notify" value="1" '.$checked.'/>';
}
add_action('admin_menu', 'xiaosong_create_comment_meta_box');

//后台保存是否回复数据
function xiaosong_save_comment_meta_box(){
	global $comment_id;
	$data = $_POST['comment_mail_notify']?"1":"0";
	$get_comment=get_comment_meta($comment_id, '_comment_mail_notify');
    if($get_comment == "" || (is_array($get_comment) && count($get_comment)==0)){			
		add_comment_meta($comment_id, '_comment_mail_notify', $data, true);			
    }elseif($data != get_comment_meta($comment_id, '_comment_mail_notify', true)){
        update_comment_meta($comment_id, '_comment_mail_notify', $data);
    }elseif($data == ""){
        delete_comment_meta($comment_id, '_comment_mail_notify', $get_comment);
	}	
}
add_action('edit_comment', 'xiaosong_save_comment_meta_box');
 

//评论回复邮件通知
add_action('comment_post','xiaosong_comment_mail_notify'); 
function xiaosong_comment_mail_notify($comment_id) {
  $admin_notify = '1'; // admin 要不要收回复通知 ( '1'=要 ; '0'=不要 )
  $admin_email = get_bloginfo ('admin_email'); // $admin_email 可改为你指定的 e-mail.
  $comment = get_comment($comment_id);
  $comment_author_email = trim($comment->comment_author_email);
  $parent_id = $comment->comment_parent ? $comment->comment_parent : '';
  
	if ($comment_author_email == $admin_email && $admin_notify == '1')
		update_comment_meta($comment_id,'_comment_mail_notify',1);

  $notify = $parent_id ? get_comment($parent_id)->comment_mail_notify : '0';
  $spam_confirmed = $comment->comment_approved;


  if ($parent_id != '' && $spam_confirmed != 'spam' && $admin_notify == '1') {
	$wp_email = 'no-reply@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME'])); // e-mail 发出点, no-reply 可改为可用的 e-mail.
	$to = trim(get_comment($parent_id)->comment_author_email);
	$subject = 'Hi，您在 [' . get_option("blogname") . '] 的留言有人回复啦！';
	$message = '<div style="background-color:#fff; border:1px solid #ccc; color:#111; -moz-border-radius:8px; -webkit-border-radius:8px; -khtml-border-radius:8px; border-radius:8px; font-size:12px; width:702px; margin:0 auto; margin-top:10px;">
    <div style="background:#428BCA; width:100%; height:60px; color:white; -moz-border-radius:6px 6px 0 0; -webkit-border-radius:6px 6px 0 0; -khtml-border-radius:6px 6px 0 0; border-radius:6px 6px 0 0; ">
    <span style="height:60px; line-height:60px; margin-left:30px; font-size:12px;"> 您在<a style="text-decoration:none; color:#ff0;font-weight:600;"> [' . get_option("blogname") . '] </a>上的留言有回复啦！</span></div>
	<div style="width:90%; margin:0 auto">
	  <p>' . trim(get_comment($parent_id)->comment_author) . ', 您好!</p>
	  <p>您曾在《' . get_the_title($comment->comment_post_ID) . '》的留言:</p> 
      <p style="background-color: #EEE;border: 1px solid #DDD;padding: 20px;margin: 15px 0;">'
	   . trim(get_comment($parent_id)->comment_content) . '</p>
	  <p >' . trim($comment->comment_author) . ' 给您的回应:</p>  
	  <p style="background-color: #EEE;border: 1px solid #DDD;padding: 20px;margin: 15px 0;">'
	   . trim($comment->comment_content) . '<br /></p>
	  <p>点击 <a href="' . htmlspecialchars(get_comment_link($parent_id)) . '">查看回应完整內容</a></p>
	  <p>欢迎再次光临 <a href="' . get_option('home') . '">' . get_option('blogname') . '</a></p>
	  <p style="color:#999">(此邮件由系统自动发出，请勿回复.)</p>
	</div></div>';
	$from = "From: \"" . get_option('blogname') . "\" <$wp_email>";
	$headers = "$from\nContent-Type: text/html; charset=" . get_option('blog_charset') . "\n";
	wp_mail( $to, $subject, $message, $headers );
  }
}


?>