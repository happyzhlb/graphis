<?php
/**
 * 系统设置控制器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Controller; 
use Think\Controller;
class SystemController extends BackendController{
    function index(){ 
        if(!IS_POST){
            $_region_mod = D('Region');
            $this->assign('region',$_region_mod->_get_region(0,true));
            $this->display('./system.form');    
        }else{
            $params = $_POST;
            $str = "<?php\r\n";
            $str .= "return array(\r\n";
            foreach($params as $key => $value){
                $str .= "'{$key}' => '{$value}',\r\n";
            }
            $str .= ");\r\n?>"; 
            file_put_contents('./Shop/Common/Conf/setting.php',$str);  #var_dump(file_get_contents('./Shop/Common/Conf/setting.php')); var_dump($str); exit;
            $this->success('系统设置保存成功',U('/Admin/System'));
        }
    }
    
    public function mail_test(){
    	$test_title='邮件功能测试成功 '.C('mail_nickname');
    	$test_content='<div style="color:#f00"><b>邮件功能测试内容</b> </div>'.todate(time());
    	$res=sendemail(I('test_address'), $test_title, $test_content);  
    	if($res){
    		echo '测试邮件发送成功！'. I('test_address') ;
    	}else{
    		echo '测试邮件发送失败！';
    	}
    }
} 
?>