<?php

if (version_compare(PHP_VERSION, '5.6.0', '<')) {exit("Sorry, this version of PHPMailer will only run on PHP version 5.6 or greater!\n");}

/**
 * 素材管理接口(原多媒体文件接口) 
 */
class WxApiMedia
{

    /**
     * 新增临时素材
     * @param [type]  $access_token [description]
     * @param [type]  $type         [description]
     * @param [type]  $file_path    [description]
     * @param [type]  $file_name    设置上传文件名称 不含有扩展名
     * @param integer $time_out     [代理请求超时时间(秒)]
     */
    public static function UploadTemporary($access_token, $type, $file_path, $file_name , $time_out = 20)
    {
        /*
            图片（image）: 2M，支持PNG\JPEG\JPG\GIF格式
            语音（voice）：2M，播放长度不超过60s，支持AMR\MP3格式
            视频（video）：10MB，支持MP4格式
            缩略图（thumb）：64KB，支持JPG格式
         */
        $arrType = array('image', 'voice', 'video', 'thumb');

        if (!in_array($type, $arrType)) { exit("type 参数 必须是'image','voice','video','thumb'");}
        $url  = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token=' . $access_token . '&type=' . $type;
        $curlfile = self::CurlInfo($file_path, $file_name);
        $data = array('media' => $curlfile);

        return WxApiHttp::Upload($url, $data, $time_out);
    }

    /**
     * 获取临时素材
     * @param [type]  $access_token [description]
     * @param [type]  $media_id     [description]
     * @param integer $time_out     [description]
     * 如果下载的是视频 则根据video_url下载视频
     */
    public static function GetTemporary($access_token, $media_id, $file_path, $time_out = 20)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token=' . $access_token . '&media_id=' . $media_id;
        $result = WxApiHttp::Download($url, null, $time_out);
        if(substr($result, 2, 7) == 'errcode'){ return $result; }//获取有错误
        if(substr($result, 2, 9) == 'video_url'){
            $videurl = json_decode($result,true)['video_url'];
            $result = WxApiHttp::Download($videurl, null,$time_out);
            WxApiHttp::Save($file_path,$result);
        }else{
          WxApiHttp::Save($file_path,$result);
        }
        return 0;
    }

     /**
     * 高清语音素材获取接口 (此接口还未测试)
     * @param [type]  $access_token [description]
     * @param [type]  $media_id     [description]
     * @param [type]  $file_path     [description]
     * @param integer $time_out     [description]
     */
    public static function GetTemporaryJssdk($access_token, $media_id, $file_path, $time_out = 20)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/media/get/jssdk?access_token=' . $access_token . '&media_id=' . $media_id;
        // $stream = WxApiHttp::Download($url, null, $time_out);
        // WxApiHttp::Save($file_path,$stream);
        return 0;
    }

    /**
     * 新增永久图文素材
     * @param [type]  $access_token [description]
     * @param [type]  $news         [图文消息组]
     * @param integer $time_out     [代理请求超时时间(毫秒)]
     */
    public static function UploadNews($access_token,array $news, $time_out = 10)
    {
        $url  = 'https://api.weixin.qq.com/cgi-bin/material/add_news?access_token=' . $access_token;
        $data = array('articles' => $news);
        return WxApiHttp::Post($url, $data,  $time_out);
    }

    /**
     * 上传图文消息内的图片获取URL
     * #
     * @param [type]  $access_token [description]
     * @param [type]  $file_path    上传文件路径 只能是jpg/png
     * @param [type]  $file_name    文件名称
     * @param integer $time_out     [description]
     */
    public static function UploadNewsImg($access_token, $file_path, $file_name , $time_out = 30)
    {
        $url  = 'https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token=' . $access_token;
        $curlfile = self::CurlInfo($file_path, $file_name);
        $data = array('media' => $curlfile);
        return WxApiHttp::Upload($url, $data, $time_out);
    }

    /**
     * 新增其他类型永久素材（不包括视频）
     * #
     * @param [type]  $access_token [description]
     * @param [type]  $type         ['image', 'voice', 'thumb']                   
     * @param [type]  $file_path    [上传文件路径]
     * @param [type]  $file_name    [上传文件名称]
     * @param integer $time_out     代理请求超时时间
     */
    public static function UploadForever($access_token, $type, $file_path, $file_name , $time_out = 30)
    {
        $arrType = array('image', 'voice', 'thumb');
        if (!in_array($type, $arrType)) { exit("type 参数 必须是'image','voice','thumb'");}

        $curlfile = self::CurlInfo($file_path, $file_name);
        $data = array('media' => $curlfile);
        
        $url  = 'https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=' . $access_token;// . '&type='. $type
        return WxApiHttp::Upload($url, $data, $time_out);
    }
    /**
     * 新增其他类型永久素材（视频）
     * #
     * @param [type]  $access_token [description]
     * @param [type]  $title        [description]
     * @param [type]  $introduction [description]
     * @param [type]  $file_path    [上传文件路径]
     * @param [type]  $file_name    [上传文件名称]
     * @param integer $time_out     代理请求超时时间
     */
    public static function UploadForeverVideo($access_token, $title , $introduction, $file_path , $file_name , $time_out = 30)
    {
        $curlfile = self::CurlInfo($file_path, $file_name);
        $data = array('media' => $curlfile);
        $data['description'] = '{"title":"' . $title . '", "introduction":"' . $introduction . '"}';
        $url  = 'https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=' . $access_token . '&type=video';
        return WxApiHttp::Upload($url, $data, $time_out);
    }
    /**
     * 获取永久图文素材
     * #
     * @param [type]  $access_token [description]
     * @param [type]  $media_id     [description]
     * @param integer $time_out     [代理请求超时时间]
     */
    public static function GetForeverNews($access_token, $media_id, $time_out = 10)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/material/get_material?access_token=' . $access_token;
        $data = array('media_id' => $media_id);
        return WxApiHttp::Post($url, $data, $time_out);

    }
    /**
     * 获取永久素材 声音 图片
     * #
     * @param [type] $access_token [description]
     * @param [type] $media_id     [description]
     * @param [type] file_path
     * @param [type] $stream       [description]
     */
    public static function GetForever($access_token, $media_id, $file_path, $time_out = 30)
    {
        $url  = 'https://api.weixin.qq.com/cgi-bin/material/get_material?access_token=' . $access_token;
        $data = array('media_id' => $media_id);
        $result = WxApiHttp::Download($url, $data, $time_out);
        if(substr($result, 2, 7) == 'errcode'){ return $result; }//获取有错误
        WxApiHttp::Save($file_path,$result);
        return 0;
    }
    /**
     * 获取永久视频素材
     * #
     * @param [type] $access_token [description]
     * @param [type] $media_id     [description]
     */
    public static function GetForeverVideo($access_token, $media_id, $time_out = 10)
    {
        $url  = 'https://api.weixin.qq.com/cgi-bin/material/get_material?access_token=' . $access_token;
        $data = array('media_id' => $media_id);
        return WxApiHttp::Post($url, $data, $time_out); 
    }
    

    /**
     * 删除永久素材
     * #
     * @param [type]  $access_token [description]
     * @param [type]  $media_id     [description]
     * @param integer $time_out     [代理请求超时时间(毫秒)]
     */
    public static function DeleteForever($access_token, $media_id, $time_out = 10)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/material/del_material?access_token=' . $access_token;
        $data = array('media_id' => $media_id);
        return WxApiHttp::Post($url, $data, $time_out); 
    }

    /**
     * 修改永久图文素材
     * @param [type]    $access_token [description]
     * @param [type]    $media_id     [要修改的图文消息的id]
     * @param [type]    $index        [要更新的文章在图文消息中的位置(多图文消息时，此字段才有意义)，第一篇为0]
     * @param NewsModel $news         [图文素材]
     * @param integer   $time_out     [代理请求超时时间(毫秒)]
     */
    public static function UpdateForeverNews($access_token, $media_id, $index,WxApiModelNews $news, $time_out = 10)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/material/update_news?access_token=' . $access_token;
        $data = array('media_id' => $media_id,'index' =>$index ,'articles'=> $news);
        return WxApiHttp::Post($url, $data,  $time_out);
    }

    /**
     * 获取素材总数
     * 永久素材的总数，也会计算公众平台官网素材管理中的素材
     * 图片和图文消息素材(包括单图文和多图文)的总数上限为5000，其他素材的总数上限为1000
     * @param [type] $access_token [description]
     */
    public static function GetCount($access_token, $time_out = 10)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/material/get_materialcount?access_token=' . $access_token;
        return WxApiHttp::Get($url,  $time_out);
    }


    /**
     * 获取素材列表
     * #
     * @param [type]  $access_token [description]
     * @param [type]  $type         [素材的类型，图片(image)、视频(video)、语音 (voice)、图文 (news)]
     * @param [type]  $offset       [description]
     * @param [type]  $count        [description]
     * @param integer $time_out     [description]
     */
    public static function GetList($access_token, $type, $offset, $count, $time_out = 10)
    {
        $arrType=['image','video','voice','news'];
        if (!in_array($type, $arrType)) {
            throw new Exception("type 参数 必须是'image','voice','video','news'");

        }
        $url  = "https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=" . $access_token;
        $data = array('type' => $type, 'offset' => $offset, 'count' => $count);
        return WxApiHttp::Post($url, $data,  $time_out);
    }

    private static function CurlInfo($file_path,$file_name)
    {
        $curlfile = new CURLFile($file_path);
        //$curlfile->setMimeType();
        $curlfile->setPostFilename($file_name);
        return $curlfile;
    }

}

// 图文消息模型
class WxApiModelNews
{
    // 标题
    public $title;

    // 图文消息缩略图的media_id，可以在基础支持上传多媒体文件接口中获得
    public $thumb_media_id;

    // 图文消息的作者
    public $author;

    // 图文消息的摘要，仅有单图文消息才有摘要，多图文此处为空。如果本字段为没有填写，则默认抓取正文前64个字。
    public $digest;

    // 是否显示封面，1为显示，0为不显示
    public $show_cover_pic;

    // 图文消息的具体内容，支持HTML标签，必须少于2万字符，小于1M，且此处会去除JS,涉及图片url必须来源 "上传图文消息内的图片获取URL"接口获取。外部图片url将被过滤。
    public $content;
    // 图文消息的原文地址，即点击“阅读原文”后的URL
    public $content_source_url;

    public function __toString()
    {
        return json_decode($this);
    }
}
