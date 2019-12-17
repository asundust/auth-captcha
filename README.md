laravel-admin登陆 滑动验证插件
======
laravel-admin登陆 滑动验证插件

### 支持
- [顶象](https://www.dingxiang-inc.com/business/captcha):heavy_check_mark:
- [极验(账号试用申请一直无法通过,无奈)](http://www.geetest.com)
- [腾讯防水墙](https://cloud.tencent.com/document/product/1110/36839):heavy_check_mark:
- [Vaptcha](https://www.vaptcha.com):heavy_check_mark:（**轻量级业务是免费的，不过该验证码使用难度相对较高**）
- [网易](http://dun.163.com/product/captcha):heavy_check_mark:
- 有主流的未发现的，额外有需求的请[issue](https://github.com/asundust/auth-captcha/issues)


### 截图
![img](https://github.com/asundust/images/blob/master/images/auth-captcha-screenshot.png?raw=true)


### 安装
```
composer require asundust/auth-captcha
```


### 获取密钥

#### 顶象
- 配置代码如下
```
'auth-captcha' => [
    'enable' => true,
    'provider' => 'dingxiang',
    'style' => 'oneclick', // 弹出式: popup 嵌入式: embed 内联式: inline 触发式: oneclick (不填写默认popup)
    'appid' => {AppID},
    'secret' => {AppSecret},
    'ext_config' => [],
],
```
- 访问 [https://www.dingxiang-inc.com/business/captcha](https://www.dingxiang-inc.com/business/captcha)
- [官网文档配置DEMO](https://cdn.dingxiang-inc.com/ctu-group/captcha-ui/demo)
- [官网文档地址](https://www.dingxiang-inc.com/docs/detail/captcha)

#### 腾讯防水墙
- 配置代码如下
```
'auth-captcha' => [
    'enable' => true,
    'provider' => 'tencent',
    'appid' => {AppID},
    'secret' => {AppSecretKey},
],
```
- 新用户购买 [https://cloud.tencent.com/product/captcha](https://cloud.tencent.com/product/captcha)
- 新用户[官方使用文档地址](https://cloud.tencent.com/document/product/1110/36839)
- 老用户[官方使用文档地址](https://007.qq.com/captcha/#/gettingStart)
- [关于腾讯防水墙收费的声明(新用户终身免费5万次)](https://007.qq.com/help.html?ADTAG=index.head)

#### Vaptcha
- 配置代码如下
```
'auth-captcha' => [
    'enable' => true,
    'provider' => 'vaptcha',
    'style' => 'invisible', // 隐藏式(类似popup): invisible 点击式: click 嵌入式: embed (不填写默认invisible)
    'appid' => {VID},
    'secret' => {Key},
    'ext_config' => [],
],
```
- 访问 [https://www.vaptcha.com](https://www.vaptcha.com)
- 访问 [官方使用文档地址](https://www.vaptcha.com/document/install)


#### 网易易盾
- 配置代码如下
```
'auth-captcha' => [
    'enable' => true,
    'provider' => 'wangyi',
    'style' => '', // 注意后台申请的类型！！！ 常规弹出式: popup 常规嵌入式: embed 常规触发式: float 无感绑定按钮：bind 无感点击式: ''(留空，奇葩设定) (不填写默认popup)
    'appid' => {captchaId},
    'secret' => {secretId},
    'secret_key' => {secretKey}, // 这里多了一个额外参数，请注意！！！
    'ext_config' => [],
],
```
- 访问 [http://dun.163.com/product/captcha](http://dun.163.com/product/captcha)
- 访问 [官方使用文档地址](http://support.dun.163.com/documents/15588062143475712?docId=150401879704260608)


### 配置
- 在`config/admin.php` 文件里加入如下配置。
```
'extensions' => [
    'auth-captcha' => [
        // 禁用此插件设置为false
        'enable' => true,
        // 验证码供应商
        'provider' => 'xxxxxx',
        // 验证码样式，可无，默认为弹窗，参见官网文档（不支持“腾讯防水墙”）
        'style' => 'xxxxxx',
        // 密钥配置
        'appid' => env('AUTH_CAPTCHA_APPID'),
        'secret' => env('AUTH_CAPTCHA_SECRET'),
        'secret_key' => env('AUTH_CAPTCHA_SECRET_KEY'), // 网易易盾需要第三个参数！！！
        // 额外配置，参见官网文档（不支持“腾讯防水墙”）
        'ext_config' => [],
    ],
]
```

- 在`.env`文件下加入
```
AUTH_CAPTCHA_APPID=xxxxxx
AUTH_CAPTCHA_SECRET=xxxxxx
AUTH_CAPTCHA_SECRET_KEY=xxxxxx
```

- 在`resources/lang/zh-CN(example).json` 文件里加入如下配置。
```
"Sliding validation failed. Please try again.": "滑动验证未通过，请重试。",
"Please complete the validation.": "请完成验证。",
"Config Error.": "配置错误。"
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
- 加入更多滑动验证码（持续添加ing）:heavy_check_mark:
- 加入表单验证
- 验证码功能模块化，提供给Laravel项目内使用（该想法实现有点难度，看着办吧）

### 升级注意事项
[UPGRADE.md](UPGRADE.md)

### 更新日志
[CHANGE_LOG.md](CHANGE_LOG.md)

### 支持
如果觉得这个项目帮你节约了时间，不妨支持一下呗！

![alipay](https://raw.githubusercontent.com/asundust/images/master/images/pay_qrcode_alipay.png)
![wechat](https://raw.githubusercontent.com/asundust/images/master/images/pay_qrcode_wechat.png)

### License
[The MIT License (MIT)](https://opensource.org/licenses/MIT)