<?php

class model_index extends Model{


    function __construct()
    {
        parent::__construct();
    }

    # This function will save the voice of user in database and export audio file
    function save_voice(){
        $pth = 'audio';
        if(!file_exists($pth)){
            mkdir('audio');
        }
        $cookie = self::setCookie();
        $targetmain = 'audio/';
        $i=rand(1,99999);
        $temp = explode('.', $_FILES['audio-file']['name']);
        $name = $_FILES["audio-file"]["name"];
        $nameNew = 'voice_'.$cookie.'_'.$i.'.mp3';
        $target = $targetmain.$nameNew;

        move_uploaded_file($_FILES["audio-file"]["tmp_name"],$target);

        $sql = "INSERT INTO tbl_voice (src,cookie) VALUES (?,?)";
        $this->query($sql,array($target,$cookie));
    }


    # This function will get the voice data from DB to show it to user
    function Get_Data(){
        $cookie = self::setCookie();
        $audio = "SELECT * FROM tbl_voice WHERE cookie=? ORDER BY id DESC LIMIT 1";
        $result =  $this->select($audio,array($cookie),1);
        return $result;
    }


    # This function save the text that will come from nevisa_voice function in database
    function transform_voice_to_text($data){
        $cookie = self::setCookie();
        $audio = "SELECT * FROM tbl_voice WHERE cookie=? ORDER BY id DESC LIMIT 1";
        $result =  $this->select($audio,array($cookie),1);

        $sql = "UPDATE tbl_voice SET voice_text=? WHERE cookie=? and id=?";
        $this->query($sql,array($data,$cookie,$result['id']));
    }

    #In this function we connect and login to Nevisa API
    function Login_Neivsa(){

        // use your username and password for Nevisa API
        $credentials = array(
            'username_or_phone_or_email' => "aekm.ehsan",
            'password' => "Ehsan2020@",
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
    }


    # nevisa_voice function will send the user's voice to Nevisa API and get text from it
    function nevisa_voice($data){
        self::sessionInit();

        function setInterval($f, $milliseconds, $addiotionalArgs)
        {
            $seconds = (int)$milliseconds / 1000;
            while (true) {
                $f($addiotionalArgs);
                sleep($seconds);
            }
        }

        $selectedFile = $_FILES['audio-file'];
        if ($selectedFile["name"] != "") {
            $audio_upload_val = array(
                'api_key' => '8e5dfb6fe6e4e5ede422d2693854ab3c83c828b6',
                'auth_token' => $data['auth_token'],
                'save_transcription' => "true",
                'file' => new CurlFile($selectedFile['tmp_name'], $selectedFile['type'], $selectedFile['name'])
            );

            // Request Auth Header
            $header = array(
                'Authorization' => 'token ' . $data['auth_token']
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
                    // save text in database
                    $this->transform_voice_to_text($jsonData['result']['transcription']['text']);
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
    }


    // if you want to show the progress of transforming voice to text ypu can use this function
    function progress($task_id){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.persianspeech.com/celery-progress/'.$task_id.'/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }


    // This function will transform the text that has been saved in database to voice
    function ariana($data){
        $id = $data['id'];
        $text = $data['text'];
        $CONNECT = curl_init();

        $cookie = self::setCookie();


        // to get the number of user's voice
        $audio = "SELECT * FROM tbl_voice WHERE cookie=?";
        $res =  $this->select($audio,array($cookie));
        $tedade_voice_shoma = sizeof($res);
        $text = $text.' .این ضبط شماره '.$tedade_voice_shoma.' شماست';

        // connecting to API and sending data to get response
        $data_string = '{"APIKey":"65E84FWSPRS62LV",
                        "Text":"'.$text.'",
                        "Speaker":"Female1",
                        "PitchLevel":"0",
                        "PunctuationLevel":"0",
                        "SpeechSpeedLevel":"0",
                        "ToneLevel":"0",
                        "GainLevel":"0",
                        "BeginningSilence":"0",
                        "EndingSilence":"0",
                        "Quality":"normal",
                        "Base64Encode":"0",
                        "Format":"mp3"}';

        $ch = curl_init('http://api.farsireader.com/ArianaCloudService/ReadText/');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );

        $result = curl_exec($ch);



        // save the API's response as mp3 file in speak folder in root
        $outputfilename = 'speak/'.$id.'.mp3';
        $handle = fopen(trim($outputfilename), "wb");
        fwrite($handle, $result);
        fclose($handle);

        curl_close($ch);

        return $outputfilename;
    }



}