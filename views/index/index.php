<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <!-- Required meta tags -->
    <base href="<?= URL ?>">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" integrity="sha384-gXt9imSW0VcJVHezoNQsP+TNrjYXoGcrqBZJpry9zJt8PCQjobwmhMGaDHTASo9N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="<?= URL ?>public/css/essential_audio.css">
    <title>ارسال صدا !</title>
    <style>
        @font-face {
            font-family: IRANSansWeb;
            src: url(<?= URL ?>fonts/ttf/IRANSansWebFaNum.ttf);
        }
        a,body,form,html,input,p,select,span,table,tbody,td,textarea,th,thead,tr{font-family:IRANSansWeb,serif}::-webkit-scrollbar{width:5px}::-webkit-scrollbar-thumb{background:#000}::-webkit-scrollbar-track{background:#9d9d9d}div.chatBox{width:100%;height:80vh;background-color:#eee;min-height:400px;display:flex;flex-direction:column}div.view{min-height:320px;height:100%;width:100%;overflow-y:scroll}div.action{min-height:80px;background-color:#222;width:100%;display:flex;position:relative}div.action .btn{width:100%;border-radius:0;text-align:center;background-color:#4ab82f}#record{z-index:2;cursor:pointer}#stop{opacity:0;position:absolute;right:0;width:100%;height:100%;z-index:1;cursor:pointer}div.action #stop.animation{opacity:1;background-color:#b9d98c;animation:loadingAnimation 1s infinite}div.action .btn:hover{background-color:#7df55f}.user_msg{display:flex;flex-direction:column;padding:20px;margin:10px 0}@keyframes loadingAnimation{0%{background-color:#b9d98c}100%{background-color:#3fa53d}}.text_voice{background:rgba(255,255,255,.29);padding:20px;font-size:.875rem;backdrop-filter:blur(3px)}.speak-user{display:none}
    </style>
</head>
<body style="background: url(<?= URL ?>public/img/4853433.jpg) no-repeat center;background-size: cover">

<div class="container">
    <div class="row" style="margin-top:20px;">
        <div class="col-md-1"></div>
        <div class="col-md-10 text-center">
            <h1>سامانه ارسال پیام صوتی</h1>
        </div>
        <div class="col-md-1"></div>
    </div>

    <div class="row">
        <div class="col-md-1"></div>
        <div class="col-md-10">
            <div class="chatBox">
                <div class="view" style="background: url(<?= URL ?>public/img/casual-life-3d-24.png) no-repeat center;background-size: contain"></div>
                <div class="action">
                    <button id="record" type="button" class="btn btn-light btn-record">ضبط صدا <i class="fa fa-microphone"></i></button>
                    <button id="stop" type="button" class="btn btn-light"></button>
                </div>
            </div>
        </div>
        <div class="col-md-1"></div>
    </div>

    <div style="width: 50px; height: 50px;"></div>


</div>

<script src="<?= URL ?>public/js/JQ.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>


<script>

    let touchEvent = 'ontouchstart' in window ? 'touchstart' : 'click';
    const recordButton = document.getElementById('record');
    const stopButton = document.getElementById('stop');

    navigator.permissions.query({name: 'microphone'}).then(function (result) {

        // اگر کاربر دسترسی داده باشد این اسکریپت شروع به ضبط صدا میکند
        if (result.state == 'granted')
        {
            recordButton.addEventListener(touchEvent,function (e){

                $('#stop').addClass('animation')
                stopButton.innerHTML = '<img src="public/img/recording.png"><span><a id="safeTimerDisplay"></a></span> | <a>توقف ارسال پیام</a>';
                $('#stop').css({'z-index':'2'});
                $('#record').css({'z-index':'1'});

                const handleSuccess = function(stream) {
                    const options = {mimeType: 'audio/webm'};
                    const recordedChunks = [];
                    const mediaRecorder = new MediaRecorder(stream, options);

                    // تنظیم شمارنده و مشخص کردن حداکثر زمان قابل ضبط
                    timer();
                    function timer(){
                        var sec = 0;
                        var timer = setInterval(function(){
                            console.log(sec);
                            stopButton.addEventListener(touchEvent, function() {
                                clearInterval(timer);
                                mediaRecorder.stop();
                                $('#stop').css({'z-index':'1'});
                                $('#record').css({'z-index':'2'});
                            });
                            document.getElementById('safeTimerDisplay').innerHTML='00:'+sec;
                            sec++;
                            if (sec > 30) {
                                clearInterval(timer);
                                recordButton.innerHTML = 'ضبط صدا <i class="fa fa-microphone"></i>';
                                mediaRecorder.stop();
                                $('#stop').css({'z-index':'1'});
                                $('#record').css({'z-index':'2'});
                            }
                        }, 1000);
                    }

                    mediaRecorder.addEventListener('dataavailable', function(e) {
                        if (e.data.size > 0) recordedChunks.push(e.data);
                    });

                    mediaRecorder.addEventListener('stop', function() {
                        const blob = new Blob(recordedChunks, {
                            'type': 'audio/mp3'
                        });

                        // در این اسکریپت صدای ضبط شده در سرور و دیتابیس ذخیره می‌شود
                        sendAudioFile(blob);
                        function sendAudioFile(file) {

                            const formData = new FormData();
                            formData.append('audio-file', file);

                            // آدرس url در واقع مربوط به فانکشن save در فایل controllers/index.php می‌باشد
                            $.ajax({
                                url: "index/save",
                                type: "POST",
                                data: formData,
                                contentType: false,
                                cache: false,
                                processData: false,
                                beforeSend: function () {

                                },
                                success: function (data) {
                                    // در صورت موفقیت آمیز بودن اطلاعات فایل صوتی ضبط شده رو دریافت و پخش میکنیم
                                    get_data();
                                },
                                error: function (e) {

                                }
                            });


                            function get_data(){
                                var target = $('.view');

                                var url = 'index/get_data';
                                var data = [];
                                $.post(url,data,function (msg){
                                    var item = '<audio class="audio-user '+msg['id']+'" controls="controls"><source src="<?= URL ?>'+msg['src']+'" type="audio/mpeg">Your browser does not support the HTML5 Audio element.</audio>';
                                    var it = '<div class="user_msg"><a><i class="fa fa-user"></i> <span>پیام شما: </span></a>'+item+'</div><p class="text_voice '+msg['id']+'"> <img src="<?= URL ?>public/img/icons8-typing.gif" style="height:40px;"> در حال تبدیل گفتار به نوشتار</p>';
                                    target.append(it);
                                    nevisa(blob,msg['id']);
                                    if (msg['voice_text']!=''){
                                        $('.text_voice.'+msg['id']).html(msg['voice_text']);
                                    }
                                    $('.audio-user.'+msg['id'])[0].play();

                                    target.animate({
                                        scrollTop:  $('.text_voice.'+msg['id']).offset().top
                                    }, '1000');

                                },'json')
                            }
                        }
                        $('#stop').removeClass('animation');
                        $('#stop').css({'z-index':'1'});
                        $('#record').css({'z-index':'2'});
                        recordButton.innerHTML = 'ضبط صدا <i class="fa fa-microphone"></i>';
                    });

                    mediaRecorder.start();
                };
                navigator.mediaDevices.getUserMedia({ audio: true, video: false })
                    .then(handleSuccess);




            })
        }

        // اگر کاربر دسترسی نداده باشد ابتدا درخواست دسترسی میدهیم و اگر قبول کرد این اسکریپت شروع به ضبط صدا میکند
        else if (result.state == 'prompt')
        {
            recordButton.addEventListener(touchEvent,function (e){
                $('#stop').addClass('animation')
                stopButton.innerHTML = '<img src="public/img/recording.png"><span><a id="safeTimerDisplay"></a></span> | <a>توقف ارسال پیام</a>';
                $('#stop').css({'z-index':'2'});
                $('#record').css({'z-index':'1'});
                const handleSuccess = function(stream) {
                    const options = {mimeType: 'audio/webm'};
                    const recordedChunks = [];
                    const mediaRecorder = new MediaRecorder(stream, options);

                    timer();
                    function timer(){
                        var sec = 0;
                        var timer = setInterval(function(){
                            console.log(sec);
                            stopButton.addEventListener(touchEvent, function() {
                                clearInterval(timer);
                                mediaRecorder.stop();
                                $('#stop').css({'z-index':'1'});
                                $('#record').css({'z-index':'2'});
                            });
                            document.getElementById('safeTimerDisplay').innerHTML='00:'+sec;
                            sec++;
                            if (sec > 30) {
                                clearInterval(timer);
                                recordButton.innerHTML = 'ضبط صدا <i class="fa fa-microphone"></i>';
                                mediaRecorder.stop();
                                $('#stop').css({'z-index':'1'});
                                $('#record').css({'z-index':'2'});
                            }
                        }, 1000);
                    }

                    mediaRecorder.addEventListener('dataavailable', function(e) {
                        if (e.data.size > 0) recordedChunks.push(e.data);
                    });

                    mediaRecorder.addEventListener('stop', function() {
                        const blob = new Blob(recordedChunks, {
                            'type': 'audio/mp3'
                        });
                        sendAudioFile(blob);
                        function sendAudioFile(file) {

                            const formData = new FormData();
                            formData.append('audio-file', file);
                            $.ajax({
                                url: "index/save",
                                type: "POST",
                                data: formData,
                                contentType: false,
                                cache: false,
                                processData: false,
                                beforeSend: function () {

                                },
                                success: function (data) {
                                    get_data();
                                },
                                error: function (e) {

                                }
                            });


                            function get_data(){
                                var target = $('.view');

                                var url = 'index/get_data';
                                var data = [];
                                $.post(url,data,function (msg){
                                    var item = '<audio class="audio-user '+msg['id']+'" controls="controls"><source src="<?= URL ?>'+msg['src']+'" type="audio/mpeg">Your browser does not support the HTML5 Audio element.</audio>';
                                    var it = '<div class="user_msg"><a><i class="fa fa-user"></i> <span>پیام شما: </span></a>'+item+'</div><p class="text_voice '+msg['id']+'"> <img src="<?= URL ?>public/img/icons8-typing.gif" style="height:40px;"> در حال تبدیل گفتار به نوشتار</p>';
                                    target.append(it);
                                    nevisa(blob,msg['id']);
                                    if (msg['voice_text']!=''){
                                        $('.text_voice.'+msg['id']).html(msg['voice_text']);
                                    }
                                    $('.audio-user.'+msg['id'])[0].play();

                                    target.animate({
                                        scrollTop:  $('.text_voice.'+msg['id']).offset().top
                                    }, '1000');

                                },'json')
                            }
                        }
                        $('#stop').removeClass('animation');
                        $('#stop').css({'z-index':'1'});
                        $('#record').css({'z-index':'2'});
                        recordButton.innerHTML = 'ضبط صدا <i class="fa fa-microphone"></i>';
                    });

                    mediaRecorder.start();
                };
                navigator.mediaDevices.getUserMedia({ audio: true, video: false })
                    .then(handleSuccess);




            })
        }
        else if (result.state == 'denied')
        {
        }
        result.onchange = function () {};
    });

    function pad(n) {
        return (n < 10 ? '0' : '') + n;
    }


    // با استفاده از ajax اطلاعات مورد نیاز یعنی token و api key را از طریق لاگین کردن به نویسا از فانکشن زیر دریافت میکنیم
    function nevisa(audio,id_audio){
        var url = "<?= URL ?>index/login_neivsa";
        var data = [];
        $.post(url,data,function(msg){
            var auth_token = msg['user']['token'];
            var api_key = msg['user']['nevisa_service_account']['current_service_record']['key'];

            // پس از لاگین کردن اطلاعات رو به فانکشن زیر میفرستیم تا تبدیل صوت به متن صورت بگیرد
            transform(audio,auth_token,api_key,id_audio);
        },'json')
    }

    // در این فانکشن تبدیل صوت به نوشتار صورت میگیرد
    function transform(audio,auth_token,api_key,id_audio){
        var url = "<?= URL ?>index/nevisa";
        const formData = new FormData();
        formData.append('audio-file', audio);
        formData.append('auth_token', auth_token);
        formData.append('api_key', api_key);
        $.ajax({
            url: url,
            type: "POST",
            data: formData,
            contentType: false,
            cache: false,
            processData: false,
            beforeSend: function () {

            },
            success: function () {
                get_data(id_audio);
            },
            error: function (e) {

            }
        });

    }

    // اطلاعات نوشتار دریافت شده از نویسا را دریافت میکنیم جهت شروع تبدیل متن به صوت
    function get_data(id_audio){
        var url = 'index/get_data';
        var data = [];
        $.post(url,data,function (msg){
            $('.text_voice.'+id_audio).html(msg['voice_text']);

            // متن تبدیل شده را به عنوان آرگومان در فانکشن زیر قرار میدهیم
            speak(msg['voice_text'],id_audio)
        },'json')
    }


    // در این فانکشن تبدیل متن به صوا شروع میشود
    function speak(text,id_audio){
        var url = 'index/ariana';
        var data = [];
        data.push({'name':'text','value':text});
        data.push({'name':'id','value':id_audio});

        $.post(url,data,function (msg){
            var audio = '<audio class="speak-user '+id_audio+'" controls="controls"><source src="<?= URL ?>'+msg+'" type="audio/mpeg">Your browser does not support the HTML5 Audio element.</audio>';
            // صوت را پخش میکنیم
            $('.text_voice.'+id_audio).append(audio);
            $('.speak-user.'+id_audio)[0].play();
        },'json')
    }

</script>

<script src="<?= URL ?>public/js/essential_audio.js"></script>


</body>
</html>