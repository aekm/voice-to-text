# Installation
****
<p style="direction: rtl;text-align: right;font-family: IRANSansWeb">برای نصب و راه‌اندازی ابتدا وارد فایل core/config.php شوید و محل پروژه را مشخص کنید.</p>

````
for localhost: define('URL','http://localhost/your_folder_name/')

for server: define('URL','https://your_domain.tld/')
````
<br>
<br>
<p style="direction: rtl;text-align: right;font-family: IRANSansWeb">سپس وارد فایل core/model.php شوید و مشخصات دیتابیس و کانکشن را بنویسید.</p>

````
$servername = 'localhost';  //your servername
$username = 'root';         //your database username
$password = '';             //your user's password
$dbname = 'voice';          //your database name
````

# Usage
****

<p style="direction: rtl;text-align: right;font-family: IRANSansWeb">در فایل model_index.php فانکشن‌های مربوط به تبدیل صوت به متن و همچنین متن به صوت قرار دارد که می‌توانید جهت استفاده از api_key دریافتی از <a href="https://github.com/nevisa-team/nevisa-doc">سرور نویسا</a> در پروژه خود استفاده کنید. </p>


Login_Neivsa Function:
```
$credentials = array(
'username_or_phone_or_email' => "use_your_username",
'password' => "use_your_password",
);

// curl connection
$ch = curl_init();
// set curl url connection
$curl_url = "https://accounting.persianspeech.com/account/login";
// request config
curl_setopt($ch, CURLOPT_URL, $curl_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, $credentials);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// server response
$server_result = curl_exec($ch);
curl_close($ch);

// convert server response to json object like
$jsonData = json_decode($server_result, true);
self::sessionInit();
self::sessionSet('auth_token',$jsonData['user']['token']);
return $jsonData;
```

<p style="direction: rtl;text-align: right;font-family: IRANSansWeb">بعد از دریافت Token و Api Key و با استفاده از فانکشن nevisa_voice در فایل model_index.php می‌توانید فایل صوتی خود را به متن تبدیل کنید و آن را در دیتابیس ذخیره کنید.</p>

Function:
```
        // call function to start session in core/model.php
        self::sessionInit();

        function setInterval($f, $milliseconds, $addiotionalArgs)
        {
            $seconds = (int)$milliseconds / 1000;
            while (true) {
                $f($addiotionalArgs);
                sleep($seconds);
            }
        }

        $selectedFile = $_FILES['your_file_name'];
        if ($selectedFile["name"] != "") {
            $audio_upload_val = array(
                'api_key' => 'your_api_key',
                'auth_token' => 'your_auth_token',
                'save_transcription' => "true",
                'file' => new CurlFile($selectedFile['tmp_name'], $selectedFile['type'], $selectedFile['name'])
            );

            // Request Auth Header
            $header = array(
                'Authorization' => 'token ' . 'your_auth_token'
            );

            // curl connection
            $ch = curl_init();
            // set curl url connection
            $curl_url = "https://api.persianspeech.com/recognize-file";
            // request config
            curl_setopt($ch, CURLOPT_URL, $curl_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $audio_upload_val);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // server response
            $server_result = curl_exec($ch);
            curl_close($ch);

            // convert server response to json object like
            $jsonData = json_decode($server_result, true);

            setInterval(function ($jsonData) {
                self::sessionInit();
                $auth_t = self::sessionGet('auth_token');
                // request header
                $header = array(
                    'Authorization' => 'token ' . $auth_t
                );
                // curl connection
                $ch = curl_init();
                // set curl url connection
                $curl_url = "https://api.persianspeech.com";
                // request config
                curl_setopt($ch, CURLOPT_URL, $curl_url . $jsonData['progress_url']);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $server_result = curl_exec($ch);
                curl_close($ch);

                // convert server response to json object like
                $jsonData = json_decode($server_result, true);

                // check if file process is complete in each interval
                if ($jsonData['complete'] == true) {
                    echo json_encode($jsonData['result']['transcription']);
                    exit();
                }
            }, 5000, $jsonData);
        }
        else {

            // file is empty
            $response = array(
                'status' => "error",
                'message' => "you must select a file"
            );

            echo json_encode($response);

        }
```



