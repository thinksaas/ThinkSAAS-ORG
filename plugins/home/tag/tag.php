<?php 
defined('IN_TS') or die('Access Denied.'); 

function tag(){
	//最新标签
	$arrTag = aac('tag')->findAll('tag',"`count_topic`>'0'",'uptime desc',null,30);
	
	foreach($arrTag as $key=>$item){
		$arrTag[$key]['tagname'] = tsTitle($item['tagname']);
	}


    include template('tag','tag');

	
}

addAction('home_index_left','tag');