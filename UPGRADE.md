**需要改动的升级将会在这里详细说明**

### v2.0.1 -> v2.0.2
- 新增滑动验证样式功能(无此选项或者不填写将会默认使用弹出)
- 具体参考[README.md#获取密钥](README.md#获取密钥)
```
'style' => 'xxxxxx',
```
结果如下：
```
'extensions' => [
    'auth-captcha' => [
        'enable' => true,
        'provider' => 'xxxxxx',
        'style' => 'xxxxxx',
        'appid' => env('AUTH_CAPTCHA_APPID'),
        'secret' => env('AUTH_CAPTCHA_SECRET'),
        'ext_config' => [],
    ],
]
```

- 另外需要新增翻译，在`resources/lang/zh-CN(example).json` 文件里加入如下配置。
```
"Please complete the validation.": "请完成验证。"
```
结果如下：
```
"Sliding validation failed. Please try again.": "滑动验证未通过，请重试。",
"Please complete the validation.": "请完成验证。"
```


### v1.0.3 -> v2.0.0
- 加入了滑动验证供应商的选择，请在在`config/admin.php` 文件里加入如下配置。
- 具体参考[README.md#获取密钥](README.md#获取密钥)
```
'provider' => 'xxxxxx',
```
结果如下
```
'extensions' => [
    'auth-captcha' => [
        'enable' => true,
        'provider' => 'xxxxxx',
        'appid' => env('AUTH_CAPTCHA_APPID'),
        'secret' => env('AUTH_CAPTCHA_SECRET'),
        'ext_config' => [],
    ],
]
```