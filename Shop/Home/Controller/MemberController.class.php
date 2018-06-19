<?php
/**
 * 会员中心控制器
 * @author Abiao
 * @copyright 2016
 */
namespace Home\Controller;
use Think\Controller;
class MemberController extends FrontendController{
	var $_user_mod=null;
	var $_user_info=null;
	var $user_id=null;
    function __construct(){
        parent::__construct();
        $this->MemberController();
    }
    
    function MemberController(){
        $this->_user_mod=D('user');
        $this->_region_mod=D('Admin/Region');
        //$this->user_id=session('user_id');
        $this->user_id=$this->visitor->get('user_id');
        $this->_user_info=$this->_user_mod->where('user_id='.$this->user_id)->find();
    }
   
    /** 会员中心初始化 */
    function _init(){
    	parent::_init();
    	$user_id= $this->visitor->get('user_id');  
	   	$cnt['msg_num']= M('userPmessage')->where('is_new=1 and to_user='.$user_id)->count();
	   	$cnt['order_count']= M('order')->where('is_delete=0 and user_id='.$user_id)->count();
       	$cnt['score']=getNameById('score','user','user_id',$user_id);  #echo M()->getLastSql(); exit();
	   	$this->assign($cnt); 
	   	
	   	$user = M('user')->find($user_id);
	   	$user['remain_days'] = dateDiff(date('Y-m-d'),$user['vip_end_date']);
	   	if($user['remain_days']<0){ $user['remain_days'] = 0; session('[start]'); session('remain_days',$user['remain_days']); }
	   	$this->assign('user',$user);# var_dump($user);
    }
    
    /** 会员中心首页 */
    function index(){ 
       $this->assign('top_name','会员中心');
	   //$user_id=$this->visitor->get('user_id');
	   
       $this->display('index/member.index'); 
    } 
    /** 会员资料*/
    function myinfo(){    
    	if(!IS_POST){     
    		if($_GET['flag']==1)$this->display('./user.myinfo');
    		$this->display('index/member.myinfo');
    	}else{ 
    		$data=array(
    			'user_id' => session('user_id'),
    			'u_pic'	=> I('u_pic','','trim'),
    			'nick_name' => I('nick_name','','trim'),
    			'city'	=> I('city','','trim'),
    			'memo' => I('memo','','trim'),  
    			'email' => I('email','','trim'),  
    		); 
    		if(I('mobile')){ 
    		   $data['mobile'] = I('mobile','');
    		}  
    		$rules = array( 
    			//array('u_pic','require','请上传您的头像.',1), 
    			array('nick_name','require','昵称必填.',1), 
    			array('city','require','城市必填.',1), 
        		//array('memo','require','备注必填.',1), 
        		array('email','require','邮箱必填.',1), 
        		array('email','email','邮箱格式不正确.',1), 
   			); 
   			if(!$this->_user_mod->validate($rules)->create()){
   				$this->error($this->_user_mod->getError());
   			} 
    		if(false!==$this->_user_mod->save($data)){
    			$this->success('个人资料更新成功.',U('Member/index'),0);
    		}
    	}
    }   
   
    /*修改密码*/
	function passwd(){
		$user_id=session('user_id');
		if(!IS_POST){
			$this->assign('top_name','修改密码');
			$this->display('index/member.passwd');
		}else{ 
    		$user=$this->_user_mod->where('user_id='.$user_id)->find();
    		$oldpwd=trim(I('oldpwd'));    //echo '<div class="skipTly">';dump($oldpwd); dump($user['code']);
    		$oldpwd=md5(md5($oldpwd).$user['code']);   //dump($oldpwd); dump($user['password']);
    		if($oldpwd!=$user['password']){
    			$this->error('原密码错误.'); return;
    		}
    		
    		$newpwd=trim(I('password')); 
    		if(strlen($newpwd)<6){
    			$this->error('新密码格式不对.'); return;
    		}
    		$repwd=trim(I('repassword')); 
    		if($repwd!=$newpwd){
    			$this->error('两次密码输入不一致.'); return;
    		}    		
    		
    		$newpwd=md5(md5($newpwd).$user['code']);
    		
		    $data=array(
    			'user_id' => $user_id,
    			'password'	=> $newpwd  
    		);  
    		if(false!==$this->_user_mod->save($data)){
    			$this->success('密码修改成功.',U('Member/index'),0);
    		}
		}
	}

	function register(){
		$this->assign('top_name','用户注册');
		$this->display('./user.register');
	}
	
	function uploadimg(){
		// 上传图片
           if($_FILES['photo']['size']>0){
				$upload = new \Think\Upload();// 实例化上传类    
				$upload->maxSize   =     1024*1024*5 ;// 设置附件上传大小    
				$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型     
				$upload->savePath  =      './article/cate_photo/'; // 设置附件上传目录    // 上传文件    
				$info   =   $upload->upload();    
				 if(!$info) {// 上传错误提示错误信息       
				 	 $this->error($upload->getError());    
				 }else{     
				 	$this->success('上传成功.',$info['photo']['savepath'].$info['photo']['savename']) ;
				 } 
        	}  
	}
	
	function tiezi(){
		$user_id=$this->visitor->get('user_id');
		$list=M('bbs')->where('user_id='.$user_id)->order('bbs_id desc')->select();  
		$this->assign('_list',$list);
		$this->assign('top_name','我的帖子');
		$this->display('./user.tiezi');
	}

	function canyu(){
		$user_id=$this->visitor->get('user_id');
		$list=M('bbs_reply')->where('user_id='.$user_id)->order('bbs_id desc')->select();  
		foreach ($list as $key => $val){
			$bbs_id[]=$val['bbs_id'];
		} 
		$bbs_id=join(',', $bbs_id); 
		$list=M('bbs')->where('bbs_id in('.$bbs_id.')')->order('bbs_id desc')->select(); 	
		$this->assign('_list',$list);
		$this->assign('top_name','我参与的');
		$this->display('./user.canyu');
	}    
    function record(){ 
//       echo  $this->visitor->get('user_id');
	   $this->assign('top_name','孕期记录');
       $this->display('./user.record'); 
    }   
    
    /** 检测email是否已经存在 */
    function check_email(){
        $email = I('email','','trim');
        $user_id = $this->user_id;
        $result = D('Member')->check_email($email, $user_id);
        echo $result? 'false': 'true';
    }
    
    function account_settings(){   
    	if(!IS_POST){   
    		$this->assign('user',$this->_user_info);
    		$this->display('./Account_settings');
    	}else{
    		$data=array(
    			'user_id' => $this->user_id,  
    		); 
    		$email= I('email','','trim'); 
    		$password= I('password','','trim'); 
    		$first_name= I('first_name','','trim'); 
    		$last_name= I('last_name','','trim'); 
    		if(!empty($email)){
    			$data['email'] = $email;
    		}
    		if(!empty($password)){
    			$data['password'] = $password;
    		}
    		if(!empty($first_name)){
    			$data['first_name'] = $first_name;
    		}
    		if(!empty($last_name)){
    			$data['last_name'] = $last_name;
    		} 
    		$rules=array(
    			array('email','email','Email invalidate',1),
    			array('email','','The new e-mail address is used by another user.',1,'unique'),
    			array('first_name','require','First Name is required',1),
    			array('last_name','require','Last Name is required',1),
    		); 
    		if(!$this->_user_mod->validate($rules)->create($data)){ 
    			$this->error($this->_user_mod->getError());
    		}else{
    			if(!empty($password)){
    			$data['password'] = MD5(MD5($password).$this->_user_info['code']);
    			}
    			$res=$this->_user_mod->save($data);
    			if(false!==res){
    				$this->success('OK');
    			}
    		}
    	}
    }
         
    /** 订阅 */
    function subscription(){   
    	$action=I('action','','trim');
    	$subscription=I('subscription','','trim');
    	$user=$this->_user_mod->where('user_id='.session('user_id'))->field('email,is_subscription')->find();
    	if($action!='do'){ 	 
    		$this->assign('user',$user); 
    		$this->display('./Subscription'); 
    	}else{ 
    		$res=$this->_user_mod->where('user_id='.session('user_id'))->setField('is_subscription',$subscription);
    		if($res && $subscription=='1'){
    			$data=array(
    				'email'=>$user['email'],
    				'ip' => $_SERVER['REMOTE_ADDR'],
    				'ctime'=> time()
    			);
    			M('email_list')->add($data);
    			sendEmailByTemplate('subscription',$user['email']);
    		}else{
    			M('email_list')->where('email="'.$user['email'].'"')->delete();
    		} 
    		$this->redirect('./Member/subscription');
    	}
    }

    /** 我关注的 */
    function mycare(){
        
        $this->display('index/member.mycare');
    }
    
    /**充值 */
    function recharge(){  
		if(IS_POST){
			$order_sn = _gen_order_sn('recharge','order_sn');
			
			$this->assign('order_sn',$order_sn);
			if(empty($order_sn)){
				$this->error('订单号不能为空.');
			}
			
			$money = I('money',0,'floatval');
			$this->assign('money',$money);
			if(empty($money)){
				$this->error('充值金额不能为空.');
			} 
			
			$pay_code = I('pay_code','alipay','trim');
			
			M()->startTrans();
			
			//vip天数
            $vipDays = array(
                '99' => 30,
                '499' => 183,
                '888' => 365,
                '1599' => 1599, 
                '1888' => 36500, 
            );
			$data = array(
			    'order_sn' => $order_sn,
			    'user_id' => $this->visitor->get('user_id'),
			    'order_status' => 11,
			    'total_amount'=> $money,
			    'pay_code' => 'alipay',
			    'add_time' => gmtime(),			    
			    'vip_days' => $vipDays[(int)$money]
			); 
			$res = M('recharge')->add($data);
			if(!$res){
			    M()->rollback();
			    $this->error('充值订单生成失败.');
			}
			M()->commit();
			
			$this->display('index/member.recharge_order'); 
		}else{
			$this->display('index/member.recharge'); 
		}
    }   
    
    

    /** 打赏口令码 */
    function code(){
        if(!IS_POST){
            
            $list = M('myalbum')->alias('my')->join('__ALBUM__ a on a.id = my.album_id')
            ->field('a.*,my.code')
            ->where('user_id='.$this->user_id)->order('my.ctime desc')->limit(0,3)->select();
            $this->assign('list',$list);
            
            $this->display('index/member.code');
        }else{
            $code = I('code','','trim');
            if(empty($code)){
                $this->error('口令码不能为空.');
            }
            $where = array(
                'order_status' => 20,
                'command_code' => $code
            );
            $find = M('reward_order')->where($where)->find();
            if($find){
                if(M('myalbum')->where(array('code'=>$code))->find()){
                    $this->error('该口令码已经被'.M('user')->where(array('user_id'=>$this->user_id))->getField('user_name').'使用过.');
                }
                $where =  array(
                    'album_id' => $find['album_id'],
                    'user_id' => $this->user_id
                );
                if(!M('myalbum')->where($where)->find()){
                    $data = $where;
                    $data['code'] = $code;
                    $data['ctime']=gmtime();
                    M('myalbum')->add($data);
                }else{
                    $data['code'] = $code;
                    $data['ctime']=gmtime();
                    M('myalbum')->where(array('album_id'=>$find['album_id'],'user_id'=>$this->user_id))->save($data);
                }
                $this->success('恭喜，口令码通过验证，成功找回一个专辑.');
            }else{
                $this->error('口令码验证失败.');
            }
        }
    }
     
    
    /**
     *  反馈信息
     * */
    function guestbook(){
        if(!IS_POST){
            $this->display('index/member.guestbook');
        }else{
            $data = array(
                'title' => I('title','','trim'),
                'content' => I('content','','trim'),
                'name' => I('name','','trim'),
                'mobile' => I('mobile','','trim'),
                'email' => I('email','','trim'),
                'user_id' => $this->user_id,
                'ip' => get_client_ip(),
                'ctime' => gmtime()
            );
            
            if(empty($data['title'])){
                $this->error('标题不能为空.');
            }
            if(empty($data['content'])){
                $this->error('内容不能为空.');
            }
            if(empty($data['name'])){
                $this->error('姓名不能为空.');
            }
            if(empty($data['mobile'])){
                $this->error('手机不能为空.');
            }
            
            if(M('guestbook')->add($data)){
                $this->success('添加反馈信息成功.');
            }else{
                $this->error('添加反馈信息失败.');
            }
        }
    }
    
    /** 历史充值订单  
     *  */
    function  recharge_history(){
        
        $this->display('index/member.recharge_history');
    }
    
}


?>