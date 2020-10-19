**需要改动的升级将会在这里详细说明**

### v2.0.12 -> v2.0.13
- `重写登陆页`([点击直达](README.md#重写登陆页))和`注意事项`([点击直达](README.md#注意事项))的功能逻辑调整。无涉及`重写登陆页`和`注意事项`请忽略本更新。

### v2.0.1 -> v2.0.3
- 新增滑动验证样式功能(无此选项或者不填写将会默认使用弹出，例外：网易易盾是无感点击式)
- 具体参考[README.md#获取密钥](README.md#获取密钥)
```
'style' => 'xxxxxx',
```
另外如果是网易易盾的话，需要额外添加一条参数
```
'secret_key' => env('AUTH_CAPTCHA_SECRET_KEY'),
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
        'secret_key' => env('AUTH_CAPTCHA_SECRET_KEY'),
        'ext_config' => [],
    ],
]
```

- 另外需要新增翻译，在`resources/lang/zh-CN(example).json` 文件里加入如下配置。
```
"Please complete the validation.": "请完成验证。",
"Config Error.": "配置错误。"
```
结果如下：
```
"Sliding validation failed. Please try again.": "滑动验证未通过，请重试。",
"Please complete the validation.": "请完成验证。",
"Config Error.": "配置错误。"
```


### v1.0.3 -> v2.0.0
- 加入了滑动验证供应商的选择，请在在`config/admin.php` 文件里加入如下配置。
- 由于更改了命名空间，更新后可能需要执行`composer dump`操作才能正常允许。
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