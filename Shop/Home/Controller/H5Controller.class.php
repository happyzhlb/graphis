<?php
/**
 * 产品详情控制器
 * @author Abiao
 * @copyright 2017
 */
namespace Home\Controller;
use Think\Controller;
use Behavior\AgentCheckBehavior;
class H5Controller extends FrontendController{
    var $_goods_mod = null;   
    function __construct(){
        parent::__construct();
        $this->GoodsController();
        C('TMPL_ACTION_ERROR','./h5.error');
    }
    function GoodsController(){
        $this->_goods_mod = D('Admin/Goods'); 
    }
    
    /** 产品详细页 */
    function goodslist(){
        $article_mod = M('article');
        
        $article_id =I('article_id','','intval'); 
        if(empty($article_id)){
            $article_id = $article_mod->where('if_show=1')->getField('article_id');
            $jumpUrl = U('goodslist',array('article_id'=>$article_id));
             
            $this->assign('jumpUrl',$jumpUrl);
            $this->assign('article_id',$article_id);
            $this->error('article_id不能为空.');
            
        }
        
        $article = $article_mod 
                ->where(array('article_id'=>$article_id,'if_show'=>1))
                ->field('article_id,cate_id,title,cutline,content,if_show,is_top,ctime,collect_num,view_num,photo,photo0')
                ->find(); 
        if(empty($article)){
            $this->error('专题不存在或者已经禁止展示.');
        }
        
        $goods_ids = M('article_goods')->field('goods_id,orderNum')->where(array('article_id'=>$article_id))->select();
        
        $goods_arr = array();
        foreach ($goods_ids as $key => $val){
            $goods_arr[$key] = $val['goods_id']; 
        }
        
        $where = array(
            'is_on_sale'=>1,
            'goods_id'=> array('in',$goods_arr)
        );
        
        $article['goods'] = M('goods')
            ->field('goods_id,goods_name,goods_img,cate_id,market_price,price,click_count,goods_desc')
            ->where($where)->select();
        if($_GET['debug']){
             echo M()->getLastsql();
             dump($article);
        }
        $this->assign('article',$article);
        
        $comment_num = M('article_comments')->where(array('article_id'=>$article_id))->count(); 
        $this->assign('comment_num',$comment_num);
        
        $this->display('./h5.goodslist');
    } 
    
    
    /** 下载 APP  */
    function download($type='ymg280'){  //dump($_SERVER['HTTP_USER_AGENT']);dump(isMobile());
        if($type=='ymg280'){
            $apple_url = 'https://itunes.apple.com/us/app/yun-ma-gou-bei-yun-huai-yun/id1120949170?l=zh&ls=1&mt=8';
            $android_url = 'https://www.ymg280.com/Uploads/apk/YunMaGou.apk';
            $yingyongbao_url = 'http://a.app.qq.com/o/simple.jsp?pkgname=com.yunmagou.yunmagou';
        }else{
            $apple_url = 'https://itunes.apple.com/us/app/yun-ma-gou-bei-yun-huai-yun/id1120949170?l=zh&ls=1&mt=8';
            $android_url = 'https://www.ymg280.com/Uploads/apk/YunMaGou.apk';
            $yingyongbao_url = 'https://itunes.apple.com/cn/app/id1223231656?mt=8';
        }
        
        //app分享下载统计
        $this->appdownloadcount($type);
        
        if(isIphone()){ 
           header("location:{$yingyongbao_url}");
        }else{
           header("location:{$yingyongbao_url}");
        }  
    }
    
    
    /** 博客h5详情页 */
    function blogdetail(){
        $bid=I('id',0,'intval');
        $blog_mod = M('blog');
        $where = array(array('bid'=>$bid));   
        
        //一分钟算一次浏览
        if(!cookie('bid_'.date('YmdHi'))){
            $blog_mod->where($where)->setInc('clicks');  
            cookie('bid_'.date('YmdHi'),1);
        }
        
        $blog = $blog_mod->where('bid='.$bid)->find();
        $blog['cate_name']=getNameById('cate_name','blogcate','cate_id',$blog['cate_id']);
        $blog['relateCatename'] =  D('Admin/Blogcate')->getRelateCatename($blog['cate_id']);
        $this->assign('blog',$blog);
        $seo=array(
            'name'=>$blog['title'].' - '.$blog['cate_name'],
            'keywords'=>$blog['cate_name'],
        );
        $prev_list=$blog_mod->where('bid>'.$bid)->order('bid asc')->find();
        $next_list=$blog_mod->where('bid<'.$bid)->order('bid desc')->find();
        $this->assign('prev_list',$prev_list);
        $this->assign('next_list',$next_list); 
        $this->display('./h5.blogdetail');
    }
    

    /** wiki h5详情页 */
    function wikidetail(){
        $id=I('id',0,'intval');
        $wiki_mod = M('wiki');
        $where = array(array('id'=>$id));  
        
        //一分钟算一次浏览
        if(!cookie('wiki_id_'.date('YmdHi'))){
            $wiki_mod->where($where)->setInc('view_num');
            cookie('wiki_id_'.date('YmdHi'),1);
        }
        
        $wiki = $wiki_mod->where($where)->find(); 
        $wiki['cate_name']=getNameById('cate_name','wikicate','cate_id',$wiki['cate_id']);   
        $wiki['relateCatename'] =  D('Admin/Wikicate')->getRelateCatename($wiki['cate_id']);
        
        $this->assign('wiki',$wiki);  
        $prev_list=$wiki_mod->where('id>'.$id)->field('id,title')->order('id asc')->find();
        $next_list=$wiki_mod->where('id<'.$id)->field('id,title')->order('id desc')->find();
        $this->assign('prev_list',$prev_list);
        $this->assign('next_list',$next_list);
        
        //随机id
        $db_prefix = C('DB_PREFIX');
        $randid = M()->query(" SELECT (ROUND(   RAND() * ( (SELECT MAX(Id) FROM `{$db_prefix}wiki` where cate_id={$wiki['cate_id']})-(SELECT MIN(Id) FROM {$db_prefix}wiki where cate_id={$wiki['cate_id']}))   )  + (SELECT MIN(Id) FROM {$db_prefix}wiki where cate_id={$wiki['cate_id']}) ) as randid ");
        $randid = current(current($randid));
        $map['id'] = array('gt',$randid);
        $map['cate_id'] = $wiki['cate_id'];
        $recommend_list=$wiki_mod->where($map)->field('id,title')->order('id asc')->limit(5)->select();
        $this->assign('recommend_list',$recommend_list); 
        
        $this->display('./h5.wikidetail');
    }
    
    /** 食谱菜谱  h5详情页 */
    function recipedetail(){
        $id=I('id',0,'intval');
        $recipe_mod = M('recipe');
        $where = array(array('id'=>$id));  
        
        //一分钟算一次浏览
        if(!cookie('recipe_id_'.date('YmdHi'))){
            $recipe_mod->where($where)->setInc('view_num');
            cookie('recipe_id_'.date('YmdHi'),1);
        }
        
        $recipe = $recipe_mod->where($where)->find();  
        $recipe['cate_name']=getNameById('cate_name','recipecate','cate_id',$recipe['cate_id']);   
        $recipe['relateCatename'] =  D('Admin/Wikicate')->getRelateCatename($recipe['cate_id']);
        
        $this->assign('recipe',$recipe); 
        
        $prev_list=$recipe_mod->where('id>'.$id)->order('id asc')->find();
        $next_list=$recipe_mod->where('id<'.$id)->order('id desc')->find();
        $this->assign('prev_list',$prev_list);
        $this->assign('next_list',$next_list);
         
        $this->display('./h5.recipedetail');
    }
        
    
}
    
?>