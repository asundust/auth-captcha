Tencent sliding captcha for laravel-admin auth
======
Tencent sliding captcha for laravel-admin auth

laravel-admin登陆 腾讯滑动验证插件


### Screen Shot / 截图
![img](https://github.com/asundust/images/blob/master/images/auth-captcha-screenshot.png?raw=true)


### Secret Key / 密钥

Visit [https://007.qq.com/captcha/](https://007.qq.com/captcha/)

访问 [https://007.qq.com/captcha/](https://007.qq.com/captcha/)


### Installation / 安装

```
composer require asundust/auth-captcha
```


### Configuration / 配置

- In the extensions section of the `config/admin.php` file, add configurations

- 在`config/admin.php` 文件里加入如下配置。
```
'extensions' => [
     'auth-captcha' => [
         // set to false if you want to disable this extension
         // 禁用此插件设置为false
         'enable' => true,
         
         // configuration
         // 密钥配置
         'appid' => env('AUTH_CAPTCHA_APPID'),
         'secret' => env('AUTH_CAPTCHA_SECRET'),
    ],
]
```


- In the `.env` file, add configurations

- 在`.env`文件下加入
```
AUTH_CAPTCHA_APPID=xxxxxx
AUTH_CAPTCHA_SECRET=xxxxxx
```


- In the `resources/lang/zh-CN(example).json` file, add configurations

- 在`resources/lang/zh-CN(example).json` 文件里加入如下配置。
```
"Sliding validation failed. Please try again.": "滑动验证未通过，请重试。"
```


### Usage / 使用

Open your login page in your browser

在浏览器里打开laravel-admin登陆页

### Future / 未来

- ~~加入回车键监听~~:heavy_check_mark:

- 加入表单验证

- 加入更多滑动验证码


- ~~Add Enter Key to Monitor~~:heavy_check_mark:

- Add form validation

- Add more Sliding validation

### Change Log / 更新日志

[CHANGE_LOG.md](CHANGE_LOG.md)

### License

[The MIT License (MIT)](https://opensource.org/licenses/MIT)

