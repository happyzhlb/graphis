<?php
/**
 * 邮件模板控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class TemplateController extends BackendController{
    var $_mod = null;
    var $_user_mod = null;
    function __construct(){
        parent::__construct();
        $this->TemplateController();
    }
    function TemplateController(){
        $this->_mod = D('Template');
        $this->_user_mod =D('user');
    }
     
    
    /** 邮件模板列表 */
    function index(){  
        if($_GET['tm_subject']){
            $where['tm_subject'] = array('exp','like "%'.trim(I('tm_subject')).'%"');
        }    
        $count = $this->_mod->where($where)->count();   
        $page = new \Think\Page($count,20);
        $list = $this->_mod->where($where)->order('tm_id DESC')->limit($page->firstRow.','.$page->listRows)->select();
        $this->assign('list',$list);  
        $this->assign('page',$page->show());
        $this->display('./template.index');
    }
    
    /** 新增邮件模板 */
    function add(){
    	$this->_get_config();
        if(!IS_POST){ 
            $this->display('./template.form');
        }else{
            $data = array( 
            	'tm_type' => I('tm_type'),
                'tm_no' => trim(I('tm_no')),
                'tm_subject' => I('tm_subject'), 
            	'tm_content' => I('tm_content'),
            ); 
            $rules=array(
            	array('tm_type','require','类型不能为空！'), 
            	array('tm_no','require','键名不能为空！'), 
            	array('tm_subject','require','发送标题不能为空！'),
            	array('tm_content','require','发送内容不能为空！'),
            );
            $this->_mod->setProperty('_validate',$_validate); 
            if(!$this->_mod->create($data)){
                $this->error($this->_mod->getError());
                return;
            }     
            
             $res=$this->_mod->add($data); 
	            
            $err_arr=join(',',$err_arr); 
            $message='邮件模板添加成功.';
            $this->success($message,U('index'),1);
        }
    }
    
    /** 编辑邮件模板 */
    function edit(){
    	$this->_get_config();
        $item_id = I('id','','intval'); 
        $list = $this->_mod->find($item_id);
        if(!$list){
            $this->error('邮件模板不存在');
            return;
        }
        if(!IS_POST){     
            $this->assign('list',$list);
            $this->display('./template.edit');
        }else{
            $data = array(
                'tm_id' => $item_id,
            	'tm_type' => I('tm_type'),
                'tm_no' => trim(I('tm_no')),
                'tm_subject' => I('tm_subject'), 
            	'tm_content' => I('tm_content'),
            ); 
            $rules=array(
            	array('tm_type','require','类型不能为空！'), 
            	array('tm_no','require','键名不能为空！'), 
            	array('tm_subject','require','发送标题不能为空！'),
            	array('tm_content','require','发送内容不能为空！'),
            );
            if(!$this->_mod->validate($rules)->create($data)){
                $this->error($this->_mod->getError());
                return;
            } 
            $this->_mod->save($data);
            $this->success('邮件模板编辑成功',U('index'));
        }
    }
    
    /** 读取邮件模板配置文件 */
    function _get_config(){
        $template=C('template'); 
        $this->assign('template',$template);
    }
    
    /** 删除邮件模板 */
    function drop(){
        $item_id = trim(I('id'));
        if(empty($item_id)){
            $this->error('传入的ID有误，删除失败');
            return;
        }
        if(strpos($item_id,','))
            $item_id = explode(',',$item_id);
        if(is_array($item_id)){
            $where['tm_id'] = array('in',$item_id);
        }else{
            $where['tm_id'] = $item_id;
        }
        $list = $this->_mod->where($where)->select();
        if(!$list){
            $this->error('邮件模板不存在或已经删除');
            return;
        }
        $res=$this->_mod->where($where)->delete();   
        if(FALSE===$res){
            $this->error('邮件模板删除失败');
            return;
        }
        $this->success('邮件模板删除成功',U('index'));
    }  
}


?>