<?php
/**
 * Created by PhpStorm.
 * User: wangliang
 * Date: 2018/8/5
 * Time: 下午1:36
 */

namespace App\Handlers;


use Image;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class ImageUploadHandler
{
    // 只允许以下格式文件上传
    protected $allowed_ext = ['jpg', 'png', 'jif', 'jpeg'];

    public function qiniuSave($file, $folder, $file_prefix, $max_width=false)
    {
        // 七牛鉴权类
        // 用于签名的公钥和私钥
        $accessKey = config('qiniu.Access_Key');
        $secretKey = config('qiniu.Secret_Key');
        // 初始化签权对象
        $auth = new Auth($accessKey, $secretKey);
        $bucket = config('qiniu.Bucket_Name');
        $domain = config('qiniu.Domain');
        // 生成上传Token
        $token = $auth->uploadToken($bucket);
        // 构建 UploadManager 对象
        $uploadMgr = new UploadManager();
        // 构建存储的文件夹规则，值如：images/avatars/201709/21/
        // 文件夹切割能让查找效率更高。
        $folder_name = "images/$folder/" . date("Ym", time()) . '/' . date("d", time()) . '/';
        // 文件的上传路径
        $upload_path = $file->getPathname();
        // 获取文件的后缀名，因图片从剪切板里粘贴时后缀名为空，所以此处确保后缀一直存在
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'png';
        // 拼接文件名，加前缀是为了增加辨析度，前缀可以是相关数据模型的 ID
        $filename = $folder_name . $file_prefix . '_' . time() . '_' .str_random(10) . '.' . $extension;
        // 如果上传的不是图片将终止操作
        if (!in_array($extension, $this->allowed_ext)){
            return false;
        }
        $uploadMgr->putFile($token, $filename, $upload_path);
        $url = $domain . $filename;
        // 如果限制了图片宽度，就进行裁剪
        if ($max_width && $extension != 'gif') {
            $url = $domain . $filename . '/1/w/' . $max_width;
        }
        return $url;
    }

    public function save($file, $folder, $file_prefix, $max_width=false)
    {
        // 构建存储的文件夹规则，值如：uploads/images/avatars/201709/21/
        // 文件夹切割能让查找效率更高。
        $folder_name = "uploads/images/$folder/".date('Ym/d', time());

        // 文件具体存储的物理路径，`public_path()` 获取的是 `public` 文件夹的物理路径。
        // 值如：/home/vagrant/Code/larabbs/public/uploads/images/avatars/201709/21/
        $update_path = public_path(). '/'. $folder_name;

        // 获取文件的后缀名，因图片从剪贴板里黏贴时后缀名为空，所以此处确保后缀一直存在
        $extension = strtolower($file->getClientOriginalExtension())?:'png';

        // 拼接文件名，加前缀是为了增加辨析度，前缀可以是相关数据模型的 ID
        // 值如：1_1493521050_7BVc9v9ujP.png
        $filename = $file_prefix . '_' . time() . '_' . str_random(10) . '.' . $extension;

        // 如果上传的不是图片将终止操作

        if (!in_array($extension, $this->allowed_ext)){
            return false;
        }

        // 将图片移动到我们的目标存储路径中
        $file->move($update_path, $filename);

        // 如果限制了图片宽度，就进行裁剪
        if ($max_width && $extension != 'gif'){
            // 此类中封装的函数，用于裁剪图片
            $this->reduceSize($update_path . '/' . $filename, $max_width);
        }
        return [
            'path' => config('app.url') . "/$folder_name/$filename"
        ];
    }

    public function reduceSize($file_path, $max_width)
    {
        // 先实例化，传参是文件的磁盘物理路径
        $image = Image::make($file_path);

        // 进行大小调整的操作
        $image->resize($max_width, null, function ($constraint){
            // 设定宽度是 $max_width, 高度等比例缩放
            $constraint->aspectRatio();

            //防止裁剪图片尺寸变大
            $constraint->upsize();
        });

        // 对图片修改后进行保存
        $image->save();
    }
}