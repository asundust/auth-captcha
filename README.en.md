Sliding captcha for laravel-admin auth, Multiple platform support
======
Sliding captcha for laravel-admin auth, Multiple platform support

**For more details, please read [README.md](README.md)**

### Screen Shot
![img](https://github.com/asundust/images/blob/master/images/auth-captcha-screenshot.png?raw=true)


### Installation
```
composer require asundust/auth-captcha
```


### Configuration
- In the extensions section of the `config/admin.php` file, add configurations
```
'extensions' => [
    'auth-captcha' => [
        // set to false if you want to disable this extension
        'enable' => true,
        'provider' => 'xxxxxx',
        // style of captcha
        'style' => 'xxxxxx',
        // configuration
        'appid' => env('AUTH_CAPTCHA_APPID'),
        'secret' => env('AUTH_CAPTCHA_SECRET'),
        'secret_key' => env('AUTH_CAPTCHA_SECRET_KEY'),
        'ext_config' => [],
    ],
]
```

- In the `.env` file, add configurations
```
AUTH_CAPTCHA_APPID=xxxxxx
AUTH_CAPTCHA_SECRET=xxxxxx
```

- In the `resources/lang/zh-CN(example).json` file, add configurations
```
"Sliding validation failed. Please try again.": "滑动验证未通过，请重试。",
"Please complete the validation.": "请完成验证。",
"Config Error.": "配置错误。"
```


### Usage
Open your login page in your browser

### Notices for upgrading
[UPGRADE.md](UPGRADE.md)

### Change Log
[CHANGE_LOG.md](CHANGE_LOG.md)

### License
[The MIT License (MIT)](https://opensource.org/licenses/MIT)

