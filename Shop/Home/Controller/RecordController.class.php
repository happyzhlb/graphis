<?php
/**
 * Record控制器
 * @author Abiao
 * @copyright 2016
 */
namespace Home\Controller;
use Think\Controller;
class RecordController extends FrontendController{
	var $_mod=null; 
	var $_user_mod=null; 
	var $_recordcate_mod=null;
	var $_reply_mod=null;
    var $_config =null;
	
    function __construct(){
        parent::__construct();
        $this->RecordController();
    }
    
    function RecordController(){
        $this->_mod=D('Record');  
        $this->_recordcate_mod=D('Admin/recordcategory');
        $this->_user_mod=D('user'); 
        $this->_reply_mod=D('record_reply'); 
        $this->_config=M('config');
        $this->assign('top_name','社区');
        if(session('?user_id')){  
	       	$user=$this->_user_mod->field('user_name,nick_name,email,phone')->where('user_id='.session('user_id'))->find();          
	       	$this->assign('user',$user);  
       	}
    }
    
    /** 首页 */
    function index(){ 
    	$where['if_show']=1;
    	$where['user_id']=$this->visitor->get('user_id');
    	$datelist=$this->_mod->query("SELECT DISTINCT FROM_UNIXTIME(ctime,'%Y-%m-%d') as ctime FROM `rj_record` where if_show=1 and user_id=".$where['user_id']." ORDER BY ctime asc");
    	foreach ($datelist as $key => $val){
    		$datelist[$key]['item']=$this->_mod->where($where)->limit('0,20')->order('record_id asc')->select();
    		$datelist[$key]['year']=date('Y',strtotime($val['ctime']));
    		$datelist[$key]['month']=date('m',strtotime($val['ctime']));
    		$datelist[$key]['date']=date('d',strtotime($val['ctime']));
    	}
       	//$list=$this->_mod->where($where)->limit('0,20')->order('record_id asc')->select();
       	$this->assign('_list',$datelist); //dump($datelist);
       	$this->display('./record.index'); 
    }    
    

    /** 发布记录 */
    function add(){ 
        $user_id=$this->visitor->get('user_id');
    	if(!$user_id){ 
    		$this->redirect(U('Index/user_login','',''));
    		$this->error('请先登录.');
    		return false;
    	}
        if(IS_POST){
    		$cate_id=I('cate_id',1,'intval');
	    	if(empty($cate_id)){
	    		$this->error('ID错误.');
	    		return false;
	    	}  
	    	$content=I('content','','trim');
	    	$title=I('title','record','trim');
	    	$data=array(
	    		'title'=>$title,
	    		'content'=>$content,
	    		'user_id'=>$user_id, 
	    		'cate_id'=>$cate_id,
	    		'photo'=>I('photo'),
	    		'ctime'=>gmtime(),
	    		'if_show'=>1
	    	); 
	    	$res=$this->_mod->add($data);  
	    	if($res){ 
	    		$this->success('恭喜，发布成功.',U('index',array('cate_id'=>$cate_id)));
	    	}else{
	    		$this->error('发布失败.');
	    	}
    	}else{ 
       		$this->display('./record.add'); 
    	} 
    }   

 
    /** 编辑记录 */
    function edit(){ 
        $user_id=$this->visitor->get('user_id');
    	if(!$user_id){ 
    		$this->redirect(U('Index/user_login','',''));
    		$this->error('请先登录.');
    		return false;
    	}
    	$record_id=I('record_id',0,'intval');
    	if(empty($record_id)){
    		$this->error('ID错误.');
    		return false;
    	}   
	    $list=$this->_mod->where('record_id='.$record_id)->find();
	    $this->assign('list',$list);	   	
        if(IS_POST){ 
	    	$content=I('content','','trim');
	    	$title=I('title','record','trim');
	    	$data=array(
	    		'title'=>$title,
	    		'content'=>$content,
	    		'user_id'=>$user_id,  
	    		'photo'=>I('photo'),
	    		'ctime'=>gmtime(),
	    		'if_show'=>1
	    	); 
	    	$res=$this->_mod->where('record_id='.$record_id)->save($data);  
	    	if($res){ 
	    		$this->success('恭喜，发布成功.',U('index'));
	    	}else{
	    		$this->error('发布失败.');
	    	}
    	}else{ 
       		$this->display('./record.edit'); 
    	} 
    }   
       
    /** 删除 */
    function del(){
        $user_id=$this->visitor->get('user_id');
    	if(!$user_id){ 
    		$this->redirect(U('Index/user_login','',''));
    		$this->error('请先登录.');
    		return false;
    	} 
    	$record_id=I('record_id',0,'int');
    	$res=$this->_mod->where('record_id='.$record_id)->delete();
    	if($res){ $this->success('删除成功.');}else{$this->error('删除失败.'); }
    }
    
}


?>