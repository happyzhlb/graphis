<?php
/**
 * 后台主控制器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
use Common\Common\Basevisitor;
class BackendController extends Controller{
    var $visitor = null;
    function __construct(){
        parent::__construct(); 
        $this->BackendController();
    }
    
    function BackendController(){
        $this->visitor = new visitor();
        if(!$this->visitor->has_login){
            $without_login = array('login','do_login','verify','check_verify'); 
            if(!in_array(ACTION_NAME,$without_login) && CONTROLLER_NAME !='Cron' ){
                redirect(U('/Admin/Index/login'));
            }   
        }  
       $this->_auth();
    }
    //初始化权限认证
    public function _auth(){ 
       $auth=new \Org\Util\Auth();  
       if(session('user_id')!=1){
	       if(CONTROLLER_NAME!='Index' && CONTROLLER_NAME !='Cron' && !$auth->check(CONTROLLER_NAME.'-'.ACTION_NAME,session('user_id'))){
	         $this->error('你没有权限'); 
	       }
       }
    }
    
    /** 验证码 */
    function verify(){
        $Verify = new \Think\Verify();
        ob_clean();
        $Verify->fontSize = 30; 
        $Verify->length = 4;
        $Verify->fontttf = '4.ttf';
        $Verify->entry();
    }
    
    /** 异步检验验证码 */
    function check_verify(){
        $verify_code = I('verify','','trim');
        $verify = new \Think\Verify();  
        session('[start]'); 
        echo $verify->check($verify_code,'')? 'true':'false';
    }
    
    /** 后台登录 */
    function login(){
        if(!IS_POST){
            $this->display('/login');
        }else{
            $user_name = I('user_name','','trim');
            $password = I('password','','trim');
            $verify = I('verify','','trim');
            $_admin_mod = M('Admin');
            $where['user_name'] = $user_name;
            $admin_info = $_admin_mod->where($where)->find();
            if(!$admin_info){
                $this->error('管理员不存在');
                return;
            }
            $user_id = $admin_info['user_id'];
            $user_name = $admin_info['user_name'];
            if($admin_info['psw'] !== md5(md5($password).$admin_info['code'])){
                login_log($user_id,$user_name,'admin','密码错误');
                $this->error('密码错误');
                return;
            }
            //判断该管理员的状态
            if(!$admin_info['status']){
                login_log($user_id,$user_name,'admin','已被锁定');
                $this->error('您的账号已经被锁定，登录失败');
                return;
            }
            //更新登录信息
            $data = array(
                'user_id' => $user_id,
                'last_ip' => get_client_ip(),
                'last_login' => gmtime(),
                'login_times' => $admin_info['login_times'] + 1
            );
            if(!$_admin_mod->save($data)){
                login_log($user_id,$user_name,'admin','更新登录信息失败');
                $this->error('更新登录信息失败，登录失败');
                return;
            }
            //登录成功分派身份
            $this->visitor->assign($admin_info);
            login_log($user_id,$user_name,'admin','成功');
            redirect(U('Index/'));
        }
    }
    
    /** 退出登录 */
    function loginout(){
        $this->visitor->logout();
        redirect(U('/Admin/Index/login'));
    }
    
    /** 修改密码 */
    function changepwd(){
        if(!IS_POST){
            $this->display('./admin.changepwd');   
        }else{
            $oldpassword = trim(I('oldpassword'));
            $_admin_mod = D('Admin');
            $admin = $_admin_mod->find($this->visitor->get('user_id'));
            if(!$admin){
                $this->error('管理员不存在或已被删除');
                return;
            }
            //判断原密码是否正确
            $oldpassword = md5(md5($oldpassword).$admin['code']);
            if($oldpassword != $admin['psw']){
                $this->error('原密码不正确');
                return;
            }
            $data = array(
                'user_id' => $this->visitor->get('user_id'),
                'psw' => trim(I('newpassword')),
                'repsw' => trim(I('repassword'))
            );
            if(!$_admin_mod->create($data)){
                $this->error($_admin_mod->getError());
                return;
            }
            $string = new \Org\Util\String();
            $data['code'] = $string->randString(6);
            $data['psw'] = md5(md5($data['psw']).$data['code']);
            if(!$_admin_mod->save($data)){
                $this->error('修改密码失败');
                return;
            }
            $this->success('修改密码成功');
        }
    }
    
    /** 清楚缓存 */
    function clear_cache(){
        $temp_files = getfile(TEMP_PATH);
        if(!empty($temp_files)){
            foreach($temp_files as $key => $file){
                if(strpos($file,'php')){
                   @unlink(TEMP_PATH.$file);
                }
            }
            $this->success('Cache cleared');   
        }
    }
    
    /** 订单操作 */
    function order_can_handle($order_status, $refund_status){
        $you_can = array();
        if(!$refund_status){
            switch($order_status){
                case '11':
                    $you_can[] = array(
                        'handle' => 'pay',
                        'text' => '付款'
                    );
                    $you_can[] = array(
                        'handle' => 'cancel',
                        'text' => '取消'
                    );
                    break;
                case '20':
                    $you_can[] = array(
                        'handle' => 'unpay',
                        'text' => '待付款'
                    );
                    $you_can[] = array(
                        'handle' => 'shipping',
                        'text' => '发货'
                    );
                    /*$you_can[] = array(
                        'handle' => 'refund',
                        'text' => '退款/退货'
                    );*/
                    break;
                case '30':
                    $you_can[] = array(
                        'handle' => 'unship',
                        'text' => '待发货'
                    );
                    $you_can[] = array(
                        'handle' => 'finish',
                        'text' => '完成'
                    );
                    /*$you_can[] = array(
                        'handle' => 'refund',
                        'text' => '退款/退货'
                    );*/
                    break;
                case '40':
                    $you_can[] = array(
                        'handle' => 'shipped',
                        'text' => '已发货'
                    );
                    /*$you_can[] = array(
                        'handle' => 'refund',
                        'text' => '退款/退货'
                    );*/
                    break;
                default: 
                    break;
            }
        }/*else{
            $you_can[] = array(
                'handle' => 'refund_view',
                'text' => '查看退款'
            );
        }*/
        return $you_can;
    }
    
    
    
    
    /** 广告板块分类 */
    function block(){
    	return array(
    		'1'=>'首页', 
    	);
    }
    
    /** 地区异步操作 */
    function ajax_get_region(){
        $region_id = I('id','','intval');
        $_region_mod = D('Region');
        $region = $_region_mod->_get_region($region_id,true);
        $this->success($region,'',true);
    }
    
    /** ip转地区 */
    function ipToArea(){
        $area = ipToArea(I('ip'),'local');
        $this->success($area,'',true);
    }
    
}

class visitor extends Basevisitor{
    var $_info_key = 'admin_user';
    var $model_name = 'Admin';
}


?>