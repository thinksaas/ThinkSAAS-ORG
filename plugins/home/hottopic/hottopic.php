<?php 
defined('IN_TS') or die('Access Denied.'); 
function hottopic(){
	
	$arrHotTopics = aac('group')->findAll('group_topic',array(
	    'isaudit'=>0,
    ),'addtime desc','topicid,title',10);

    include template('hottopic','hottopic');
	
}

addAction('home_index_right','hottopic');