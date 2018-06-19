<?php
/**
 * BBS控制器
 * @author Abiao
 * @copyright 2016
 */
namespace Home\Controller;
use Think\Controller;
class BbsController extends FrontendController{
	var $_mod=null; 
	var $_user_mod=null; 
	var $_bbscate_mod=null;
	var $_reply_mod=null;
    var $_config =null;
	
    function __construct(){
        parent::__construct();
        $this->BbsController();
    }
    
    function BbsController(){
        $this->_mod=D('Bbs');  
        $this->_bbscate_mod=D('Admin/bbscategory');
        $this->_user_mod=D('user'); 
        $this->_reply_mod=D('bbs_reply'); 
        $this->_config=M('config');
        $this->assign('top_name','社区');
        if(session('?user_id')){  
	       	$user=$this->_user_mod->field('user_name,nick_name,email,phone')->where('user_id='.session('user_id'))->find();          
	       	$this->assign('user',$user);  
       	}
    }
    
    /** 首页 */
    function index(){ 
       $where=array(
       		'if_show'=>1, 
       );
       $cate_id=I('cate_id',0,'intval');
       if($cate_id){
       		$where['cate_id']=array('exp',' ='.$cate_id.' or parent_id='.$cate_id);
       }else{
       		$where['parent_id']=0;
       }
       $catelist=$this->_bbscate_mod->where($where)->order('sort_order desc')->select();   #echo M()->getLastsql();
       $this->assign('_list',$catelist);
       
       $bbswhere=array('if_show'=>1);
       if($cate_id){ 
       		$this->_bbscate_mod->_get_children_cate_id($cate_ids,$cate_id);
       		$cate_ids=join(",",$cate_ids); 
       		$bbswhere['cate_id']=array('exp','in ('.$cate_ids.')');
       }
       if(I('hotbbs')){
	       $bbslist=$this->_mod->where($bbswhere)
	       ->order('reply_num desc')
	       ->limit(0,10)
	       ->select(); 
       }else{
	       $bbslist=$this->_mod->where($bbswhere)
	       ->order('bbs_id desc')
	       ->limit(0,10)
	       ->select();
       }
       
       foreach ($bbslist as $key => $val){
       	  $bbslist[$key]['user']=$this->_user_mod->getByUser_id($val['user_id']);
       	  $bbslist[$key]['content']=htmlspecialchars_decode($bbslist[$key]['content']);
       	  $bbslist[$key]['cate_name']=$this->_bbscate_mod->where('cate_id='.$val['cate_id'])->getField('cate_name');
       	  $reply=$this->_reply_mod->where('bbs_id='.$val['bbs_id'].' and if_show=1')->order('ctime asc')->limit(5)->select();
       	  foreach ($reply as $k=>$v){
       	  	 $reply[$k]['user']=$this->_user_mod->getByUser_id($val['user_id']);
       	  }
       	  $bbslist[$key]['reply']=$reply;
       } 
       // dump($bbslist);
       $this->assign('_bbslist',$bbslist);
       $this->assign('_config',$this->_config->find());
       
       $this->display('./bbs.index'); 
    }    
    /** 选择圈子 */
    function select(){ 
       if(session('?user_id')){  
	       	$user=$this->_user_mod->field('user_name,nick_name,email,phone,city')->where('user_id='.session('user_id'))->find();          
	       	$this->assign('user',$user);   
       }else{
       		redirect(U('Index/user_login'));
       }
       $cate_list=$this->_bbscate_mod->get_category(0,true,1); //dump($cate_list);
       $this->assign('cate_list',$cate_list);
       $this->display('./bbs.select'); 
    }     

    /** 发帖 */
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
	    	$title=I('title','','trim');
	    	$data=array(
	    		'title'=>$title,
	    		'content'=>$content,
	    		'user_id'=>$user_id, 
	    		'cate_id'=>$cate_id,
	    		'ctime'=>gmtime(),
	    		'if_show'=>1
	    	); 
	    	$res=$this->_mod->add($data);  
	    	if($res){
	    		$this->update_total_subject();
	    		$this->add_score($user_id,5);
	    		$this->success('恭喜，发布成功.',U('index',array('cate_id'=>$cate_id)));
	    	}else{
	    		$this->error('回复失败.');
	    	}
    	}else{ 
       		$this->display('./bbs.add'); 
    	} 
    }  
     
    /** 回帖 */
    function reply(){ 
        $user_id=$this->visitor->get('user_id');
    	if(!$user_id){
    		redirect(U('Index/user_login'));
    		return false;
    	}
    	if(IS_POST){
    		$bbs_id=I('bbs_id',0,'intval');
	    	if(empty($bbs_id)){
	    		$this->error('ID错误.');
	    		return false;
	    	}  
	    	$content=I('content','','trim'); 
	    	$data=array(
	    		'content'=>$content,
	    		'user_id'=>$user_id, 
	    		'bbs_id'=>$bbs_id,
	    		'ctime'=>gmtime()
	    	); 
	    	$res=$this->_reply_mod->add($data); 

	    	
	    	if($res){
				$this->update_reply_num($bbs_id);
				$this->add_score($user_id,2);
	    		$this->success('恭喜，回复成功啦.',U('show',array('bbs_id'=>$bbs_id)));
	    	}else{
	    		$this->error('回复失败.');
	    	}
    	}else{ 
       		$this->display('./bbs.add'); 
    	}
    }  
	
    /** 更新帖子回复数*/
    function update_reply_num($bbs_id){
    	if(empty($bbs_id)) return ;
    	$reply_num=$this->_reply_mod->where('bbs_id='.$bbs_id.' and if_show=1')->count();
    	$dt=array('reply_num'=>$reply_num);
    	$this->_mod->where('bbs_id='.$bbs_id)->save($dt); 
    }

    /** 更新帖子总数*/
    function update_total_subject(){ 
    	$total_subject=$this->_mod->where('if_show=1')->count();
    	$dt=array('total_subject'=>$total_subject); 
    	M('config')->where('id=1')->save($dt); 
    }
    
    /** 加积分*/ 
   function add_score($user_id,$num){
   	$data=array('score'=>array('exp','score+'.$num));
   	$this->_user_mod->where('user_id='.(int)$user_id)->save($data);
   } 
    
    
    /** 帖子内容 */
    function show(){ 
       if(session('?user_id')){  
       	$user=$this->_user_mod->field('user_name,nick_name,email,phone')->where('user_id='.session('user_id'))->find();          
       	$this->assign('user',$user);  
       }
       $bbs_id=I('bbs_id',0,'intval');
       $where=array('bbs_id'=>$bbs_id,'if_show'=>1);
       $list=$this->_mod->where($where)->find(); //dump($list);
       if($list){
       		$data=array(
       			'clicks'=>array('exp','clicks+1') 
       		);
       		//更新点击量统计
       		if($this->_mod->where($where)->save($data)){
       			$list['clicks']++;
       			$this->_config->where('id=1')->save(array('total_clicks'=>array('exp','total_clicks+1') ));
       			$this->_bbscate_mod->where('cate_id='.$list['cate_id'])->save(array('total_clicks'=>array('exp','total_clicks+1') ));
       		}
       }
       $list['cate']=$this->_bbscate_mod->where('cate_id='.$list['cate_id'])->find();
       $list['cate']['total_subject']=$this->_mod->where('cate_id='.$list['cate_id'].' ')->count();
       $list['user']=$this->_user_mod->getByUser_id($list['user_id']);
       $list['reply']=$this->_reply_mod->where(array('bbs_id='.$bbs_id))->limit(0,20)->select(); 
       foreach ($list['reply'] as  $key=>$val){
       		$list['reply'][$key]['user']=$this->_user_mod->where('user_id='.$val['user_id'])->find();
       }
       $this->assign('list',$list);  
       $this->display('./bbs.show'); 
    }   

    /** 帖子/圈子列表 */
    function quanlist(){   
       $where=array(
       		'if_show'=>1, 
       );
       $cate_id=I('cate_id',0,'intval'); 
       if($cate_id){
       		$cate=$this->_bbscate_mod->getByCate_id($cate_id);
       		$this->assign('cate',$cate);
       		$this->assign('top_name',$cate['cate_name']);
       		
       		$have_children=$this->_bbscate_mod->where('parent_id='.$cate_id)->find();
       		if($have_children){
       			$where['parent_id']=array('exp','='.$cate_id); 
       		}else{
       			$where['cate_id']=$cate_id;
       		}
       }else{
       		$where['parent_id']=0;
       }
       $catelist=$this->_bbscate_mod->where($where)->order('sort_order desc')->select(); 
       //echo M()->getLastsql();
       $this->assign('_list',$catelist);
       
       $bbswhere=array('if_show'=>1);
       if($cate_id){ 
       		$this->_bbscate_mod->_get_children_cate_id($cate_ids,$cate_id);
       		$cate_ids=join(",",$cate_ids); 
       		$bbswhere['cate_id']=array('exp','in ('.$cate_ids.')');
       }
       if(I('hotbbs')){
	       $bbslist=$this->_mod->where($bbswhere)
	       ->order('reply_num desc')
	       ->limit(0,10)
	       ->select(); 
       }else{
	       $bbslist=$this->_mod->where($bbswhere)
	       ->order('bbs_id desc')
	       ->limit(0,10)
	       ->select();
       }
       
       foreach ($bbslist as $key => $val){
       	  $bbslist[$key]['user']=$this->_user_mod->getByUser_id($val['user_id']);
       	  $bbslist[$key]['content']=htmlspecialchars_decode($bbslist[$key]['content']);
       	  $bbslist[$key]['cate_name']=$this->_bbscate_mod->where('cate_id='.$val['cate_id'])->getField('cate_name');
       	  $reply=$this->_reply_mod->where('bbs_id='.$val['bbs_id'].' and if_show=1')->order('ctime asc')->limit(5)->select();
       	  foreach ($reply as $k=>$v){
       	  	 $reply[$k]['user']=$this->_user_mod->getByUser_id($val['user_id']);
       	  }
       	  $bbslist[$key]['reply']=$reply;
       } 
       // dump($bbslist);
       $this->assign('_bbslist',$bbslist);
       $this->assign('_config',$this->_config->find());
       $this->display('./bbs.quanlist'); 
    }   
    
    /** 帖子搜索 */
    function search(){   
    	$where['if_show']=1;
    	$keywords=I('search_name','','trim');
    	$keywords=str_replace(' ', '%', $keywords);
    	$keywords=str_replace('"', '%', $keywords);
    	$keywords=str_replace("'", '%', $keywords);
    	if($keywords){
    		$where['title']=array('exp','like "%'.$keywords.'%"');
    	}
    	$list=$this->_mod->where($where)->limit(100)->order('bbs_id desc')->select(); 
       	foreach ($list as $key => $val){
       		$list[$key]['user']=$this->_user_mod->getByUser_id($val['user_id']); 
       	} 
       	$this->assign('_list',$list); 
       	$total=$this->_mod->where($where)->count();
       	$this->assign('total',$total);
    	$this->display('./bbs.search'); 
    }       
    
    /** 点赞*/
    function approve(){
    	$user_id=$this->visitor->get('user_id');
    	if(!$user_id){
    		$this->error('请先登录.');
    		return false;
    	} 
    	$bbs_id=I('bbs_id',0,'intval');
    	if(empty($bbs_id)){
    		$this->error('ID错误.');
    		return false;
    	}
    	$data=array(
    		'user_id'=>$user_id,
    		'approve'=>array('exp','approve+1') 
    	);
    	$res=$this->_mod->where('bbs_id='.$bbs_id)->save($data);
    	if($res){
    		//$this->success('点赞成功.');
    		echo $this->_mod->where('bbs_id='.$bbs_id)->getField('approve');
    	}else{
    		$this->error('系统错误，请重试.');
    	}
    }
    
}


?>