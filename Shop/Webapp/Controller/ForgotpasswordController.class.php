<?php
/**
 * Webapp忘记密码控制器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Webapp\Controller;
use Think\Controller;
class ForgotpasswordController extends FrontendController{
    var $_user_mod = null;
    var $_fpwd_log_mod = null;
    function __construct(){
        parent::__construct();
        $this->ForgotpasswordController();
    }
    function ForgotpasswordController(){
        $this->_user_mod = D('User');
        $this->_fpwd_log_mod = M('FpwdLog');
        $this->_config_seo(array('name'=>'Forgot password'));
    }
    //忘记密码
    function index(){
        if(!IS_POST){
            $this->display('./Forgot_Password');   
        }else{
            $email = trim(I('email'));
            $user = $this->_user_mod->getByEmail($email);
            if(!$user){
                $this->error('The email does not exist.');
                return;
            }
            $where['status'] = 0;
            $where['express'] = array('gt',gmtime());
            $where['email'] = $email;
            $this->_fpwd_log_mod->where($where)->setField('status',1);
            $data = array(
                'email' => $email,
                'express' => gmtime() + 48 * 3600,
                'status' => 0,
            );
            if(!$fid = $this->_fpwd_log_mod->add($data)){
                $this->error('Adding password retrieval record failed.');
                return;
            }
            
            $url = trim(C('webapp_url'),'/').U('Forgotpassword/reset',array('fid'=>$fid));
			//发送邮件处理
            $tpl=M('template')->where("tm_no='get_password'")->find();
            if($tpl){
	            //发送邮件处理 
		        sendEmailByTemplate('get_password',$data['email'],array('get_pwd_url'=>$url));
       		/*
            	$email=$user['email']; 
	            $password='';
	        	$first_name=$user['first_name'];
	        	$last_name=$user['last_name'];
	        	$site_url='http://'.C('site_url');
	        	$site_name='http://'.C('site_name');
	        	$login_url=$site_url.U('Index/login_register'); 
	        	$get_pwd_url=$url;
	        	$search=array('{$email}','{$password}','{$first_name}','{$last_name}','{$login_url}','{$site_url}','{$site_name}','{$get_pwd_url}');
	        	$replace=array($email,$password,$first_name,$last_name,$login_url,$site_url,$site_name,$get_pwd_url);
	        	//邮件标题替换
	        	$title=$tpl['tm_subject'];
	        	$title=str_ireplace($search, $replace, $title);  
	        	//邮件内容替换
	        	$content=str_ireplace($search, $replace, htmlspecialchars_decode($tpl['tm_content']));          	
            */
            }else{ 
	            $content = 'Dear %s,<br/>这是一封密码找回邮件，请点击连接<a href="%s">%s</a>重置您的登陆密码，该邮件48小时内有效。<br/>%s';
	            $content = sprintf($content,$user['first_name'].' '.$user['last_name'],$url,$url,date('Y-m-d H:i:s'));	
            	sendemail($email,'OKchem.com Password Assistance.',$content); 
            }
            
	           
            $this->success('The email of password retrieval has been sent to your email box, please check it out in 48 hours.','',5);
        }
    }
    
    /** 重置密码 */
    function reset(){
        $fid = I('fid','','intval');
        $flog = $this->_fpwd_log_mod->find($fid);
        if(!$flog){
            $this->error('The link is illegal.');
            return;
        }
        if($flog['status']){
            $this->error('The link is not valid.');
            return;
        }
        $notime = gmtime();
        if($notime > $flog['express']){
            $this->error('The link has expired.');
            return;
        }
        if(!IS_POST){
            $this->assign('flog',$flog);
            $this->display('./Forgot_Password.reset');
        }else{
            $user = $this->_user_mod->getByEmail($flog['email']);
            if(!$user){
                $this->error('The account does not exist.');
                return;
            }
            $data = array(
                'user_id' => $user['user_id'],
                'password' => trim(I('password')),
                'repassword' => trim(I('repassword'))
            );
            if(!$this->_user_mod->create($data)){
                $thisd->error($this->_user_mod->getError());
                return;
            }
            $string = new \Org\Util\String();
            $data['code'] = $string->randString(6);
            $data['password'] = md5(md5($data['password']).$data['code']);
            $data['last_time'] = gmtime();
            $this->_user_mod->save($data);
            $where['fid'] = $fid;
            $this->_fpwd_log_mod->where($where)->setField('status',2);
            $this->success('Password has been successfully reset, please login.',U('Login/index'));
            
        }
    }
}


?>