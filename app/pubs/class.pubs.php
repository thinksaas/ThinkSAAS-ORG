<?php 
defined('IN_TS') or die('Access Denied.');
class pubs extends tsApp{

	//构造函数
	public function __construct($db){
        $tsAppDb = array();
		include 'app/pubs/config.php';
		//判断APP是否采用独立数据库
		if($tsAppDb){
			$db = new MySql($tsAppDb);
		}
		parent::__construct($db);
	}

    /**
     * 解密userkey,加验证userid
     * @param $userkey
     * @return string
     */
    public function getUserId($userkey){
        include 'thinksaas/class.crypt.php';
        $crypt= new crypt();
        $userid = $crypt->decrypt($userkey,$GLOBALS['TS_SITE']['site_pkey']);
        $isUser = $this->findCount('user',array(
            'userid'=>$userid,
        ));
        if($isUser == 0){
            echo json_encode(array(
                'status'=> 0,
                'msg'=> '非法操作',
                'data'=> '',
            ));
            exit;
        }else{
            return $userid;
        }
    }

    /**
     * @param $string
     * @param string $action
     * @return string
     */
    public function strCode($string, $action = 'ENCODE'){
        $action != 'ENCODE' && $string = base64_decode($string);
        $code = '';
        $key = $GLOBALS['TS_SITE']['site_pkey'];
        $keyLen = strlen($key);
        $strLen = strlen($string);
        for ($i = 0; $i < $strLen; $i++) {
            $k = $i % $keyLen;
            $code .= $string[$i] ^ $key[$k];
        }
        return ($action != 'DECODE' ? base64_encode($code) : $code);
    }

    /**
     * @param $phone
     * @param $code
     * @return bool
     */
    public function verifyPhoneCode($phone, $code){
        $strPhoneCode = $this->find('phone_code',array(
            'phone'=>$phone,
        ));

        #空数据
        if($strPhoneCode==''){
            return false;exit;
        }

        #空验证码
        if($strPhoneCode['code']==''){
            return false;exit;
        }

        #手机验证码错误次数>=2
        if($strPhoneCode['nums']>=2){
            $this->update('phone_code',array(
                'phone'=>$phone,
            ),array(
                'code'=>'',
                'nums'=>0,
            ));
            return false;exit;
        }

        #手机验证码错误
        if($strPhoneCode['code']!=$code){
            $this->update('phone_code',array(
                'phone'=>$phone,
            ),array(
                'nums'=>$strPhoneCode['nums']+1,
            ));
            return false;exit;
        }
        return true;
    }

    /**
     * 获取评论列表
     *
     * @param [type] $ptable
     * @param [type] $pkey
     * @param [type] $pid
     * @param [type] $page      当前页码
     * @param [type] $lstart    每页显示条数
     * @param [type] $puid      当前项目的用户ID
     * @param integer $uid      当前登录的用户ID
     * @param integer $ismb     是否手机浏览
     * @return void
     */
    public function getCommentList($ptable,$pkey,$pid,$page,$lstart,$puid,$uid=0,$ismb=0){
        $arrComment = $this->findAll('comment',array(
            'ptable'=>$ptable,
            'pkey'=>$pkey,
            'pid'=>$pid,
            'referid'=>0,
        ),'addtime desc',null,$lstart.',15');

        foreach($arrComment as $key => $item){
            $arrComment[$key]['l'] = (($page-1) * 15) + $key + 1;
            $arrComment[$key]['user'] = aac('user')->getSimpleUser($item['userid']);
            $arrComment[$key]['content'] = tsDecode($item['content']);
            if($ismb){
                $arrComment[$key]['content'] = mobileHtml($arrComment[$key]['content']);
            }
            $arrComment[$key]['recomment'] = $this->recomment($item['commentid'],$puid,3,$uid,$ismb);
            $arrComment[$key]['recomment_num'] = $this->recommentNum($item['commentid']);

            $arrComment[$key]['zzuid'] = $puid;//作者ID

            $arrComment[$key]['iszz'] = 0;#作者1是0否
            if($item['userid']==$puid){
                $arrComment[$key]['iszz'] = 1;#作者1是0否
            }

            $arrComment[$key]['isdelete'] = 0;#删除权限1有0无
            if($uid && $uid==$item['userid']){
                $arrComment[$key]['isdelete'] = 1;#删除权限1有0无
            }

            $arrComment[$key]['iscomment'] = 0;#回复权限1有0无
            if($uid && $uid!=$item['userid']){
                $arrComment[$key]['iscomment'] = 1;#回复权限1有0无
            }


            $arrComment[$key]['datetime'] = date('m-d H:i',$item['addtime']);

        }

        return $arrComment;
    }

    /**
     * 获取评论数
     *
     * @param [type] $ptable
     * @param [type] $pkey
     * @param [type] $pid
     * @return void
     */
    public function getCommentNum($ptable,$pkey,$pid){
        $commentNum = $this->findCount('comment',array(
            'ptable'=>'group_topic',
            'pkey'=>'topicid',
            'pid'=>$pid,
            'referid'=>0,
        ));
        return $commentNum;
    }


    /**
     * 获取评论下的回复列表
     *
     * @param [type] $referid   上级评论ID
     * @param [type] $puid      当前项目用户ID
     * @param integer $num      调用条数
     * @param integer $uid      当前登录的用户ID
     * @param integer $ismb     是否手机浏览
     * @return void
     */
    function recomment($referid,$puid,$num=0,$uid=0,$ismb=0){

        if($num){
            $limit = $num;
        }else{
            $limit = null;
        }

        $arrComment = $this->findAll('comment',array(
            'referid'=>$referid,
        ),'addtime desc',null,$limit);

        foreach($arrComment as $key=>$item){
            $html = tsDecode($item['content']);
            if($ismb==1){
                $html = mobileHtml($html);
            }

            $arrComment[$key]['content'] = $html;

            $arrComment[$key]['user'] = aac('user')->getSimpleUser($item['userid']);
            $arrComment[$key]['datetime'] = date('m-d H:i',$item['addtime']);

            if($item['touserid']){
                $arrComment[$key]['touser'] = aac('user')->getSimpleUser($item['touserid']);
            }

            $arrComment[$key]['iszz'] = 0;#作者1是0否
            if($item['userid']==$puid){
                $arrComment[$key]['iszz'] = 1;#作者1是0否
            }

            $arrComment[$key]['isdelete'] = 0;#删除权限1有0无
            if($uid && $uid==$item['userid']){
                $arrComment[$key]['isdelete'] = 1;#删除权限1有0无
            }

            $arrComment[$key]['iscomment'] = 0;#回复权限1有0无
            if($uid && $uid!=$item['userid']){
                $arrComment[$key]['iscomment'] = 1;#回复权限1有0无
            }


        }

        return $arrComment;
    }

    function recommentNum($referid){
        $num = $this->findCount('comment',array(
            'referid'=>$referid,
        ));

        return $num;
    }


    /**
     * 删除评论
     *
     * @param [type] $ptable
     * @param [type] $pkey
     * @param [type] $pid
     * @param integer $commentid
     * @return void
     */
    public function delComment($ptable,$pkey,$pid,$commentid=0){

        if($commentid){
            $this->delete('comment',array(
                'commentid'=>$commentid,
            ));
            #删除回复
            $this->delete('comment',array(
                'referid'=>$commentid,
            ));
        }else{
            $this->delete('comment',array(
                'ptable'=>$ptable,
                'pkey'=>$pkey,
                'pid'=>$pid,
            ));
        }

        #统计评论数
        $count_comment = $this->findCount('comment',array(
            'ptable'=>$ptable,
            'pkey'=>$pkey,
            'pid'=>$pid,
        ));

        //更新评论数
        $this->update($ptable,array(
            $pkey=>$pid,
        ),array(
            'count_comment'=>$count_comment,
        ));

        return true;
    }

    /**
     * 删除点赞
     *
     * @param [type] $ptable
     * @param [type] $pkey
     * @param [type] $pid
     * @param integer $userid
     * @return void
     */
    public function delLove($ptable,$pkey,$pid,$userid=0){
        if($userid){
            $this->delete('love',array(
                'ptable'=>$ptable,
                'pkey'=>$pkey,
                'pid'=>$pid,
                'userid'=>$userid,
            ));
        }else{
            $this->delete('love',array(
                'ptable'=>$ptable,
                'pkey'=>$pkey,
                'pid'=>$pid,
            ));
        }
        
        return true;
    }

    /**
     * 添加项目数据到ts_group_topic表
     *
     * @param [type] $ptable
     * @param [type] $pkey
     * @param [type] $pid
     * @param string $pjson
     * @param [type] $groupid
     * @param [type] $userid
     * @param [type] $title
     * @param [type] $gaiyao
     * @return void
     */
    public function addPtable($ptable,$pkey,$pid,$pjson='',$groupid,$userid,$title,$gaiyao){
        $topicid = $this->create('group_topic',array(
            'ptable'=>$ptable,
            'pkey'=>$pkey,
            'pid'=>$pid,
            'pjson'=>$pjson,
            'groupid'=>$groupid,
            'userid'=>$userid,
            'title'=>$title,
            'gaiyao'=>$gaiyao,
            'isaudit'=>0,
            'addtime'=>time(),
            'uptime'=>time(),
        ));
        return $topicid;
    }

    /**
     * 更新项目数据到ts_group_topic表
     *
     * @param [type] $ptable
     * @param [type] $pkey
     * @param [type] $pid
     * @param string $pjson
     * @param [type] $title
     * @param [type] $gaiyao
     * @return void
     */
    public function editPtable($ptable,$pkey,$pid,$pjson='',$title,$gaiyao){
        $this->update('group_topic',array(
            'ptable'=>$ptable,
            'pkey'=>$pkey,
            'pid'=>$pid,
        ),array(
            'pjson'=>$pjson,
            'title'=>$title,
            'gaiyao'=>$gaiyao,
        ));
    }

    /**
     * 更新项目浏览数到ts_group_topic表
     *
     * @param [type] $ptable
     * @param [type] $pkey
     * @param [type] $pid
     * @param [type] $count_view
     * @return void
     */
    public function upPtableView($ptable,$pkey,$pid,$count_view){
        $this->update('group_topic',array(
            'ptable'=>$ptable,
            'pkey'=>$pkey,
            'pid'=>$pid,
        ),array(
            'count_view'=>$count_view,
        ));
    }

    /**
     * 更新项目评论数到ts_group_topic表
     *
     * @param [type] $ptable
     * @param [type] $pkey
     * @param [type] $pid
     * @param [type] $count_comment
     * @return void
     */
    public function upPtableComment($ptable,$pkey,$pid,$count_comment){
        if($ptable!='group_topic'){
            $this->update('group_topic',array(
                'ptable'=>$ptable,
                'pkey'=>$pkey,
                'pid'=>$pid,
            ),array(
                'count_comment'=>$count_comment,
                'uptime'=>time(),
            ));
        }
    }

    /**
     *  更新项目点赞数到ts_group_topic表
     *
     * @param [type] $ptable
     * @param [type] $pkey
     * @param [type] $pid
     * @param [type] $count_love
     * @return void
     */
    public function upPtableLove($ptable,$pkey,$pid,$count_love){
        if($ptable!='group_topic'){
            $this->update('group_topic',array(
                'ptable'=>$ptable,
                'pkey'=>$pkey,
                'pid'=>$pid,
            ),array(
                'count_love'=>$count_love,
            ));
        }
    }

    /**
     * 从ts_group_topic表删除项目数据
     *
     * @param [type] $ptable
     * @param [type] $pkey
     * @param [type] $pid
     * @return void
     */
    public function delPtable($ptable,$pkey,$pid){
        $this->delete('group_topic',array(
            'ptable'=>$ptable,
            'pkey'=>$pkey,
            'pid'=>$pid,
        ));
    }


}