<?php
/**
 * WEBAPP程序默认控制器
 * @author Abiao
 * @copyright 2018
 */
namespace Webapp\Controller;
use Think\Controller;
class IndexController extends FrontendController {
    public function index(){
        //$this->oauth(); 
        $album_mod=M('album');
//         $ads = M('ad')->where(array('status'=>1))->limit('0,6')->order('ad_id asc')->select(); 
//         $this->assign('ads',$ads); 
//         $where=array(
//         'if_show' => 1, 
//         );
//         $albumlist = $album_mod->where($where)->limit('0,8')->order('is_recommend desc,total_reward_times desc,ctime desc')->select();
//         $this->assign('albumlist',$albumlist);
         
        //模特数量
        $where=array(
            'if_show' => 1,
        );
        $modelslist = M('models')->field('id,title,ctime,photo,labels,cate_id')->where($where)->limit('0,60')->order('is_recommend desc,id desc')->select();
        foreach ($modelslist as $key => $val){
            $modelslist[$key]['album_num'] = $album_mod->where(array('models_id'=>$val['id']))->count();
        }
        $this->assign('modelslist',$modelslist); 
        
        $this->display('./index.index');
    }
    
    /** 用户登录 */
    function login(){
        if(!IS_POST){
            $this->display('./index.login');
        }else{
            parent::login();
        }
    }
    
    function user_login(){
        $this->login();    
    }
    
    /** 用户注册 */
    function entry(){
        $this->display('./index.entry');
    }
    
    /** 用户指南 */
    function guide(){
        $this->display('./index.guide');
    }
    
    /** 联系我们 */
    function contact(){
        $this->display('./index.contact');
    }
    
}