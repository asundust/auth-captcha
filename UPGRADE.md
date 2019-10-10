**需要改动的升级将会在这里详细说明**

### v1.0.3 -> v2.0.0
- 加入了滑动验证供应商的选择，请在在`config/admin.php` 文件里加入如下配置。
```
'provider' => 'tencent', // 目前可选的有`tencent`、`dingxiang`
```
结果如下
```
'extensions' => [
     'auth-captcha' => [
         'enable' => true,
         'provider' => 'tencent',
         'appid' => env('AUTH_CAPTCHA_APPID'),
         'secret' => env('AUTH_CAPTCHA_SECRET'),
    ],
]
```