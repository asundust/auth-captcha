laravel-admin登陆 滑动验证插件
======
laravel-admin登陆 滑动验证插件


### 截图
![img](https://github.com/asundust/images/blob/master/images/auth-captcha-screenshot.png?raw=true)


### 获取密钥

#### 腾讯防水墙
- 配置代码为`tencent`
- 购买 [https://007.qq.com/product.html?ADTAG=index.head](https://007.qq.com/product.html?ADTAG=index.head)
- 使用 [https://007.qq.com/captcha/](https://007.qq.com/captcha/)
- [关于腾讯防水墙收费的声明(终身免费5万次)](https://007.qq.com/help.html?ADTAG=index.head)

#### 顶象
- 配置代码为`dingxiang`
- 访问 [https://www.dingxiang-inc.com/business/captcha](https://www.dingxiang-inc.com/business/captcha)


### 安装
```
composer require asundust/auth-captcha
```


### 配置
- 在`config/admin.php` 文件里加入如下配置。
```
'extensions' => [
     'auth-captcha' => [
         // 禁用此插件设置为false
         'enable' => true,
         // 验证码供应商
         'provider' => 'tencent', // 目前可选的有`tencent`、`dingxiang`
         // 密钥配置
         'appid' => env('AUTH_CAPTCHA_APPID'),
         'secret' => env('AUTH_CAPTCHA_SECRET'),
         // 额外配置，参见官网文档（目前支持“顶象”）
         'ext_config' => [],
    ],
]
```

- 在`.env`文件下加入
```
AUTH_CAPTCHA_APPID=xxxxxx
AUTH_CAPTCHA_SECRET=xxxxxx
```

- 在`resources/lang/zh-CN(example).json` 文件里加入如下配置。
```
"Sliding validation failed. Please try again.": "滑动验证未通过，请重试。"
```

- 额外配置说明，参考顶象的一个配置
```
 'ext_config' => [
     'customLanguage' => [
         'init_inform' => '拖动一下',
         'slide_inform' => '向右向右',
     ],
 ],
```


### 使用
在浏览器里打开laravel-admin登陆页

### 未来
- ~~加入回车键监听~~:heavy_check_mark:
- 加入更多滑动验证码([~~腾讯防水墙~~](https://007.qq.com/product.html?ADTAG=index.head):heavy_check_mark:、[网易](http://dun.163.com/product/captcha)、[极验](http://www.geetest.com/)、[~~顶象~~](https://www.dingxiang-inc.com/business/captcha):heavy_check_mark:）【有主流的未发现的，额外有需求的请[issue](https://github.com/asundust/auth-captcha/issues)】
- 加入表单验证
- 验证码功能模块化，提供给Laravel项目内使用（该想法实现有点难度，看着办吧）

### 升级注意事项
[UPGRADE.md](UPGRADE.md)

### 更新日志
[CHANGE_LOG.md](CHANGE_LOG.md)

### License
[The MIT License (MIT)](https://opensource.org/licenses/MIT)