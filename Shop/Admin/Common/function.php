<?php
/**
 * 管理后台配置文件
 * @author Abiao
 * @copyright 2014
 */
function get_tm_text($tm_no){	
	$template=C('template');
	foreach ($template as $key => $val){
		if($val['tm_no']==trim($tm_no)){
			return $val['text'];
		}
	}
}


?>