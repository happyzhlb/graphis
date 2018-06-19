<?php
/**
 * Webapp我的专辑
 * @author Abiao
 * @copyright 2018
 */
namespace Home\Controller; 

use Think\Controller;
use Home\Controller\MemberController;
class MyalbumController extends MemberController{
	var $_myalbum_mod=null; 
    function __construct(){
        parent::__construct();
        $this->MyalbumController();
    }
    
    function MyalbumController(){
        $this->_myalbum_mod=D('myalbum'); 
    }
 
    /** 我的专辑首页 */
    function index(){  
        $p = I('p'); if($p&&!is_numeric($p)) $this->error('分页码只能是数字.');
        $where = array(
            'user_id' => $this->user_id, 
        );
        $count = $this->_myalbum_mod->where($where)->count();
        $this->assign('count',$count);
        $page = new \Think\Page($count,5); 
        $this->assign('page',$page->show()); 
        
        $list = $this->_myalbum_mod->alias('my')->join('__ALBUM__ a on a.id = my.album_id')
                ->field('a.*,my.code')
                ->where($where)->limit($page->firstRow.','.$page->listRows)
                ->order('ctime desc')
                ->select();   
        $this->assign('list',$list);
        
        $this->display('index/myalbum.index');
    }
     
    
    /** 专辑图片打赏   */
    function reward(){
        $album_id = I('id','','intval');
        if(empty($album_id)) $this->error('相册专辑ID不能为空.');
        $where = array('album_id'=>$album_id);
        $album_mod = M('album');
    
        if(!IS_POST){
       	  	 $user_agent = $_SERVER['HTTP_USER_AGENT'];
       	  	 if(empty($user_agent)) exit('Forbiden');
       	  	 $ip = get_client_ip();
       	  	 $cookie_album_id = cookie('cookie_'.$album_id.'_'.$ip.'_'.date('Ymd'));
       	  	 if(empty($cookie_album_id)){
       	  	     $ua_data = array(
       	  	         'album_id' => $album_id,
       	  	         'models_id' => (int)M('album')->where('id='.$album_id)->getField('models_id'),
       	  	         'user_id' => 0,
       	  	         'type' => 'reward',
       	  	         'ip' => get_client_ip(),
       	  	         'user_agent' => $user_agent,
       	  	         'ctime' => gmtime(),
       	  	         'view_num' => 1
       	  	     );
       	  	     M('accesslog')->add($ua_data);
       	  	     cookie('cookie_'.$album_id.'_'.$ip.'_'.date('Ymd'),$album_id);
       	  	 }else{
       	  	     $wh = array(
       	  	         'album_id'=>$album_id ,
       	  	         'ip' => $ip,
       	  	         '_string' => "DATE(FROM_UNIXTIME(ctime)) = DATE(NOW())"
       	  	 	  	);
       	  	     M('accesslog')->where($wh)->setInc('view_num');
       	  	 }
       	  	 $openid = I('openid','','trim');
       	  	 if(isWeixin() && empty($openid)){
       	  	     $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx2b8ec378bf9b09ac&redirect_uri=http%3A%2F%2Fwww.graphis.club&response_type=code&scope=snsapi_base&state=/album/reward/id/'.$album_id.'.html?code='.I('code').'&connect_redirect=1#wechat_redirect';
       	  	     redirect($url);
       	  	 }
    
    
       	  	 $this->assign('album_id',$album_id);
       	  	 $find  = $album_mod->where(array('id'=>$album_id))->field('title,reward_fee,pay_index')->find();
       	  	 $this->assign('title',$find['title']);
       	  	 $this->assign('pay_index',$find['pay_index']?$find['pay_index']:3);
       	  	 $album_mod->where(array('id'=>$album_id))->setInc('view_num');
       	  	 $isPay = 0;  //未打赏成功
       	  	 $wh = array( 
       	  	     'album_id' => $album_id,
       	  	     'user_id' => $this->user_id
       	  	 );
       	  	 $ro = M('myalbum')->where($wh)->find();
       	  	 if($ro){
       	  	     $isPay = 1;  //已经打赏成功
       	  	 }
       	  	 if(I('debug')=='true' || $find['reward_fee'] == 0) { $isPay = 1;  }
       	  	 $this->assign('isPay',$isPay);
       	  	 $this->assign('reward_fee',$find['reward_fee']?$find['reward_fee']*100:0);
       	  	 $count = M('picture')->field('id as ID,photo as url')->where($where)->count();
       	  	 $this->assign('count',$count);
    
       	  	 $recommand_album = M('ad')->where(array('pid=2'))->order('sort_order asc')->limit(2)->select();
       	  	 $img_td = '';
       	  	 $img_td .= '<tr>';
       	  	 foreach ($recommand_album as $key => $val){
       	  	     if($key>1 && ($key%2==0)) $img_td .= '</tr><tr>';
       	  	     $img_td .= '<td><a target="_blank" href="'.$val['url'].'"><img width="90%" src="'.getThumb($val['img'],'360x480').'" /><br><span style="color:#f2f2f2;">'.mb_substr($val['title'],0,10,'utf8').'</span></a></td>';
       	  	     if(($key+1)%2==0) $img_td .= '</tr>';
       	  	 }
       	  	 $img_td .= '</tr>';
       	  	 $this->assign('img_td',$img_td);       	  	  
       	  	 $this->display('index/myalbum.reward');
        }else{  //打赏图片列表
            //$json = '{"r":true,"i":[{"ID":"705438","url":"http://image.meituzz.com/image/36063_1491403271000_75dvsl4ujid7vv8orpss"},{"ID":"705439","url":"http://image.meituzz.com/image/36063_1491403271000_smiha8z0q5j2et9jrrhx"},{"ID":"705440","url":"http://image.meituzz.com/image/36063_1491403271000_at4yn8oow3l2sxbiy7r7"},{"ID":"705441","url":"http://image.meituzz.com/image/36063_1491403272000_7k275kmfl0jf7jxth3wj"},{"ID":"705442","url":"http://image.meituzz.com/image/36063_1491403272000_00tvra66rlv8ynqga0p5"},{"ID":"705443","url":"http://image.meituzz.com/image/36063_1491403272000_1recs35bm2eaibncgcf6"},{"ID":"705444","url":"http://image.meituzz.com/image/36063_1491403272000_lgg2ke1elilpm15sivb9"},{"ID":"705445","url":"http://image.meituzz.com/image/36063_1491403272000_9ie3m8i003uflypprc3w"},{"ID":"705446","url":"http://image.meituzz.com/image/36063_1491403273000_98jpe7inlj03geomdld3"},{"ID":"705448","url":"http://image.meituzz.com/image/36063_1491403273000_mdmdufn66cw2e2nni1j6"}]}';             
            $list = M('picture')->field('id as ID,photo as url')->where($where)->order('sort_order asc,id asc')->select(); 
            foreach ($list as $key => $val){
                //$list[$key]['url'] = getThumb($val['url'],'600x900');
            }
            $data = array(
                'r'=>true,
                'i'=>$list
            );
            $this->ajaxReturn($data);
        }
    }
    
    
 }
    
    
    ?>