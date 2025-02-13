<?php
/******************Version******************************/
                //V2.12.9 experimental    //2.12.8 added empty fav or love msg if list is empty
/************* Universal constants********************************************************************/
    $limit_size_setting = "20";//server limit // if you are using url file then currently file size is limited to 20mb & if you are also using relaytive path means download & then upload then limit is 50mb
    $telegram_max_file_size = "50";
    $base_site_url = "https://www.xvideos.com";
    //$token = '5502034647:AAGQx84fiC_ksQNCdABcxasdaszzzxxxxxxxx'; 
    $token = $_ENV["token"];
    $input_logs = true; $input_logs_filename = "log1.txt";                              //this will store complete input
    $filtered_input_logs = true; $filtered_input_logs_file_name = "log_filtered.txt";   //this will store search results
    $fevouriteButton = true; $user_fevourites = "log_user_fevourites.txt"; $fileIDandUniqueID = "log_fileIDandUniqueID.txt";          //this will store file ID and unique ID so when you will make anything fevourite it will find its file_ID from unique ID(can't send file id in callbck) and store in file
    $pExtension = ".m3u";
    $override_total_matches = 0; //or null or 0//so that when someone run start command it will show only first $override_total_matches matches
    $offline_for_maintainence = false;
    $php_version = 7; // use integers only //use 7 if you will face any error on 8
    $send_photo_or_video = "video"; //acceptable inputs = "Video" or "Photo" //note: do not give any space
    global $limit_size_setting; global $telegram_max_file_size; global $base_site_url; global $token; global $pExtension;
/************ ***************************************************************************************/
/********************** check if code should be run of not********************************************/
    $input  = file_get_contents('php://input');
    if (empty($input)) {    die("Input data missing.");    }
/****************************************************************************************************/
/***********************Check if it is msg or callback by bot****************************************/
    $data = json_decode($input);
    if(empty($data->message)){ 
            $filtered_input_logs = false;   //this will be the query request so this is not required
            $callback_query_decider = true;
            $callback_query_id = $data->callback_query->id;
            $chat_id = $data->callback_query->from->id;                             //'chat_id' => $data['callback_query']['from']['id']    //$chat_id = $data->callback_query->message->chat->id;
            $text = $data->callback_query->data;                                    //'text' =>$update['callback_query']['data']            //$text = $data->callback_query->message->reply_markup->inline_keyboard[0][1]->callback_data;
            $reply_to_message_id = $data->callback_query->message->message_id;
                                $command_type_decider = false;
                                if ($php_version >=8){  if (str_starts_with($text, '/Normal_') || str_starts_with($text, '/HD_') || str_starts_with($text, '/Related_')) { $command_type_decider = true; }    }
                                else { if ((strpos($text, '/Normal_') !== false) or (strpos($text, '/HD_') !== false) or (strpos($text, '/Related_') !== false) or (strpos($text, '/Page_') !== false) or (strpos($text, '/LOVE_') !== false) or (strpos($text, '/LIKE_') !== false)) { $command_type_decider = true;  }                }            
            }
    else{   //its not empty & its a normal request
            $callback_query_decider = false;
            $chat_id = $data->message->chat->id;
            $text = $data->message->text;
            $reply_to_message_id = $data->message->message_id;
            $UserNameF_Name = $data->message->from->first_name.
            @$UserNameL_Name = $data->message->from->last_name; //sometime user lastname is empty
            $date_timestamp = $data->message->date;}
    if(empty($chat_id)){ die("Correct data is missing"); }
/************ ***************************************************************************************/
/**********************Offline for Maintainence msg**************************************************/
if($offline_for_maintainence == true){ curlCommand(false,"https://api.telegram.org/bot$token/sendMessage?chat_id=$chat_id&text=Offline for maintainence. Try again after few hours.🥲"); die(0);}
/****************************************************************************************************/
/*********************** input logs *****************************************************************/
//if you wants to see logs for future plans to upgrade bot
    if($filtered_input_logs == true) {
        date_default_timezone_set("Asia/Calcutta");
        $decoded_date =  date("Y-m-d H:i:s", $date_timestamp);
        $input_filtered = "Date:".$decoded_date."\tName:".$UserNameF_Name.@$UserNameL_Name."\tText:".$text;
        $inputoldandnew = file_get_contents($filtered_input_logs_file_name).$input_filtered.PHP_EOL;//PHP_EOL
        file_put_contents($filtered_input_logs_file_name, $inputoldandnew);
    }
    if($input_logs == true) {
        $inputoldandnew = file_get_contents($input_logs_filename).$input.PHP_EOL.PHP_EOL; //PHP_EOL
        file_put_contents($input_logs_filename, $inputoldandnew);
    }
/************ ***************************************************************************************/
/***********************check if request is made from http / https server****************************/
    function isSecure() {           //will return 1 if https
      return
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || $_SERVER['SERVER_PORT'] == 443;
    }
/************ ***************************************************************************************/
/***********Creation of VLC playable file if file size is beyond the limits**************************/
    function VLCfile($vName,$vContent,$pExtension){
        $file = clean($vName).$pExtension;
        $txt = fopen($file, "w") or die("Unable to open file!");
        $vContent2 = "#EXTM3U\r\n#EXTINF:1, $vName\r\n$vContent";
        fwrite($txt, $vContent2);
        fclose($txt);
        
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename='.basename($file));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        header("Content-Type: text/plain");
        //readfile($file);
    }
/************ ***************************************************************************************/
/************ ********OfflienCURL send local file****************************************************/

        function localCURL($url,$data,$FILENAME){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');      
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            // Create CURLFile
            $finfo = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $FILENAME);
            $cFile = new CURLFile($FILENAME, $finfo);
            $a2=array("document"=>$cFile);
            $data = array_replace($data,$a2);
            // Add CURLFile to CURL request
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                $result = curl_exec($ch);
                return $result;
        } 
/************ ***************************************************************************************/
/************ ************************Curl Online****************************************************/
    function curlCommand($initialCall = false, $URL, $extra = null) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $URL);
            //curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            
            //to get the file size
			if($initialCall == true){
                    curl_setopt($ch, CURLOPT_HEADER,1);//HEADER REQURIED
                    curl_setopt($ch, CURLOPT_NOBODY,1); // NO CONTENT BODY, DO NOT DOWNLOAD ACTUAL FILE
			    }
			    
            $response = curl_exec($ch);

            //to get file size
			if($initialCall == true){
				$CONTENT_LENGTH= curl_getinfo($ch,CURLINFO_CONTENT_LENGTH_DOWNLOAD); // PARSES THE RESPONSE HEADER AND GET FILE SIZE IN BYTES and -1 ON ERROR.
				$bytes = number_format($CONTENT_LENGTH / 1048576, 0) ; //file size in mb
				if($bytes >= $GLOBALS['limit_size_setting']){    return " \nRequested file size is = $bytes MB \n Download it manually = <a href='$URL'>Click Here</a>";  } //if the file size is big return file size with identifier [in this case "data " is identifier for confitional statement 
		        elseif($bytes >= $telegram_max_file_size){    return " \nRequested file size is = $bytes MB \n Download it manually = <a href='$URL'>Click Here</a>";  } 
				else { return "Something went wrong in cURL \n File size = $bytes MB \n Direct link = $URL";  }
            }
            curl_close($ch);  
            return $response;
                }
/************ ***************************************************************************************/
/**********************************cURL Multi********************************************************/
            /*
             * fetch all urls in parallel,
             * warning: all urls must be unique..
             * @param array $urls_unique
             *            urls to fetch
             * @param int $max_connections
             *            (optional, default 100) max simultaneous connections
             *            (some websites will auto-ban you for "ddosing" if you send too many requests simultaneously,
             *            and some wifi routers will get unstable on too many connectionis.. )
             * @param array $additional_curlopts
             *            (optional) set additional curl options here, each curl handle will get these options
             * @throws RuntimeException on curl_multi errors
             * @throws RuntimeException on curl_init() / curl_setopt() errors
             * @return array(url=>response,url2=>response2,...)
             */
function curl_fetch_multi_2( array $urls_unique, int $max_connections = 25, array $additional_curlopts = null)
{
    $urls_unique = array_unique($urls_unique);
    $ret = array();
    $mh = curl_multi_init();
    // $workers format: [(int)$ch]=url
    $workers = array();
    $max_connections = min($max_connections, count($urls_unique));
    $unemployed_workers = array();
    for ($i = 0; $i < $max_connections; ++ $i) {
        $unemployed_worker = curl_init();
        if (! $unemployed_worker) {
            throw new \RuntimeException("failed creating unemployed worker #" . $i);
        }
        $unemployed_workers[] = $unemployed_worker;
    }
    unset($i, $unemployed_worker);

    $work = function () use (&$workers, &$unemployed_workers, &$mh, &$ret): void {
        assert(count($workers) > 0, "work() called with 0 workers!!");
        $still_running = null;
        for (;;) {
            do {
                $err = curl_multi_exec($mh, $still_running);
            } while ($err === CURLM_CALL_MULTI_PERFORM);
            if ($err !== CURLM_OK) {
                $errinfo = [
                    "multi_exec_return" => $err,
                    "curl_multi_errno" => curl_multi_errno($mh),
                    "curl_multi_strerror" => curl_multi_strerror($err)
                ];
                $errstr = "curl_multi_exec error: " . str_replace([
                    "\r",
                    "\n"
                ], "", var_export($errinfo, true));
                throw new \RuntimeException($errstr);
            }
            if ($still_running < count($workers)) {
                // some workers has finished downloading, process them
                // echo "processing!";
                break;
            } else {
                // no workers finished yet, sleep-wait for workers to finish downloading.
                // echo "select()ing!";
                curl_multi_select($mh, 1);
                // sleep(1);
            }
        }
        while (false !== ($info = curl_multi_info_read($mh))) {
            if ($info['msg'] !== CURLMSG_DONE) {
                // no idea what this is, it's not the message we're looking for though, ignore it.
                continue;
            }
            if ($info['result'] !== CURLM_OK) {
                $errinfo = [
                    "effective_url" => curl_getinfo($info['handle'], CURLINFO_EFFECTIVE_URL),
                    "curl_errno" => curl_errno($info['handle']),
                    "curl_error" => curl_error($info['handle']),
                    "curl_multi_errno" => curl_multi_errno($mh),
                    "curl_multi_strerror" => curl_multi_strerror(curl_multi_errno($mh))
                ];
                $errstr = "curl_multi worker error: " . str_replace([
                    "\r",
                    "\n"
                ], "", var_export($errinfo, true));
                throw new \RuntimeException($errstr);
            }
            $ch = $info['handle'];
            $ch_index = (int) $ch;
            $url = $workers[$ch_index];
            $ret[$url] = curl_multi_getcontent($ch);
            unset($workers[$ch_index]);
            curl_multi_remove_handle($mh, $ch);
            $unemployed_workers[] = $ch;
        }
    };
    $opts = array(
        CURLOPT_URL => '',
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_ENCODING => ''
    );
    if (! empty($additional_curlopts)) {
        // i would have used array_merge(), but it does scary stuff with integer keys.. foreach() is easier to reason about
        foreach ($additional_curlopts as $key => $val) {
            $opts[$key] = $val;
        }
    }
    foreach ($urls_unique as $url) {
        while (empty($unemployed_workers)) {
            $work();
        }
        $new_worker = array_pop($unemployed_workers);
        $opts[CURLOPT_URL] = $url;
        if (! curl_setopt_array($new_worker, $opts)) {
            $errstr = "curl_setopt_array failed: " . curl_errno($new_worker) . ": " . curl_error($new_worker) . " " . var_export($opts, true);
            throw new RuntimeException($errstr);
        }
        $workers[(int) $new_worker] = $url;
        curl_multi_add_handle($mh, $new_worker);
    }
    while (count($workers) > 0) {
        $work();
    }
    foreach ($unemployed_workers as $unemployed_worker) {
        curl_close($unemployed_worker);
    }
    curl_multi_close($mh);
    return $ret;
}
function clean($string) {
   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}
/****************************************************************************************************/
/****************** conditional statements **********************************************************/
        if ($text == '/start'){
            //$xv_first_url = $base_site_url;
            $text = "Welcome to adult 18+ Bot.\n<b>LEAVE THE BOT IMMEDIATELY IF YOU ARE UNDER 18.</b>\nClick on\n1./home to view homepage content.\n2.Type any word in chat to view the type of video you want.\n3./help click on help button for help."
                    ."\n4. Click single time on buttons <i>(eg-Normal, HD, Related, Next, Home, Previous)</i>. <b><u>Do not click multiple times.</u></b>"
                    ."\nHAVE FUN\n<tg-spoiler>This bot is for educational purpose only.</tg-spoiler>";
            $switchCondition = 4;
            $parse_mode = "html";
        }
        elseif ($text == '/help'){
            $text = 'In Development';
            $switchCondition = 4;
            $parse_mode = "html";
        }
        elseif ((substr($text, 0, 6) == "/LIKE_") or (substr($text, 0, 6) == "/LOVE_")){
            $likeORlove = "LOVE";
            
            if(substr($text, 0, 6) == "/LIKE_"){ $likeORlove = "LIKE";$text1 = str_replace("/LIKE_","",$text); }
            elseif(substr($text, 0, 6) == "/LOVE_"){ $likeORlove = "LOVE";$text1 = str_replace("/LOVE_","",$text); }
            $switchCondition = 6;
            $parse_mode = "html";
        } 
        elseif ($text == '/home'){
            $xv_first_url = $base_site_url;
            $text = 'first condition';
            $switchCondition = 1;
        }
        elseif (($text == '/favourite') OR ($text == '/love')){
            $switchCondition = 7;
        }
        elseif(($callback_query_decider == true) && ($command_type_decider == false)){
            //$text = str_replace("/cbq","",$text);
            //$text = str_replace("%26","&",$text);
            $pageNum = explode('/', $text);
            $pageNum = $pageNum[4];
            if($text == '/start'){ $xv_first_url = $base_site_url; }
            elseif(!empty($pageNum)) {  $xv_first_url = $base_site_url.'/new/'.$pageNum; }
            else{ $xv_first_url = $text; }
            $switchCondition = 1;
        }
        elseif($text[0] != "/") {
            $text1 = str_replace(' ','+',$text);
            $xv_first_url = $base_site_url."/?k=".$text1;
            $text = '2nd condition';
            $switchCondition = 2;
        }
        elseif(substr($text, 0, 8) == "/Normal_") {
            $text = str_replace("/Normal_","",$text)."/v";
            $xv_first_url = $base_site_url."/".$text;
            //$text = '3rd condition';
            $Quality = "LOW";
            $switchCondition = 3;
        }
        elseif(substr($text, 0, 4) == "/HD_") {
            $text = str_replace("/HD_","",$text)."/v";
            $xv_first_url = $base_site_url."/".$text;
            //$text = '3rd condition';
            $Quality = "HD";
            $switchCondition = 3;
        }
        elseif((substr($text, 0, 4) == "/get") OR (substr($text, 0, 7) == "/remove")) {
            //$text1 = str_replace("","",$text);
            if(substr($text, 0, 6) == "/getL_"){$text1 = str_replace("/getL_","",$text); $deciderrr = 1;}
            if(substr($text, 0, 6) == "/getF_"){$text1 = str_replace("/getF_","",$text); $deciderrr = 2;}
            if(substr($text, 0, 9) == "/removeL_"){$text1 = str_replace("/removeL_","",$text); $deciderrr = 3;}
            if(substr($text, 0, 9) == "/removeF_"){$text1 = str_replace("/removeF_","",$text); $deciderrr = 4;}
            $switchCondition = 8;
        }
        elseif(substr($text, 0, 6) == "/Page_") {
            $text = str_replace("/Page_","",$text);
            $get_string = parse_url($text, PHP_URL_QUERY);
            parse_str($get_string, $get_array);
            $text1 = $get_array['k']; $pageNum = $get_array['p'] ;
            $text_without_space = str_replace(" ","+",$text);
            $xv_first_url = $text_without_space."%26p=".$pageNum;
            $switchCondition = 2;
        }
        elseif(substr($text, 0, 5) == "data ") {
            $text = str_replace("data ","",$text);
            $text = "File size is grater then $limit_size_setting MB (requested file size is $text MB ). Try download the file that is lower then $limit_size_setting MB. In future we will try to increase size limit. As of know its my hobby project so no intention to increase the file size. Hope you understand";
            $switchCondition = 4;
            }
        else if(substr($text, 0, 9) == "/Related_") {
            $text = str_replace("/Related_","",$text)."/v";
            $xv_first_url = $base_site_url."/".$text;
            $switchCondition = 5;
        }
/****************************************************************************************************/
/********************************* switch based on input*********************************************/
switch ($switchCondition) {
  case "1":
                //it will scrap the HOMEPAGE page and will send the thumbs with video scrap links
                $init = curlCommand(false,$xv_first_url);
                $re_thumb = '/<div class="microthumb-border"><\/div><\/div><div class="thumb"><a href="(.*)"><img src="(.*)data-src="(.*?)" data-idcdn="(.*)" title="(.*?)">(.*?) <span class="duration">(.*?)<\/span><\/a><\/p>/m';
                preg_match_all($re_thumb, $init, $matches);
                $total_matches = sizeof($matches[3]);
                if(!empty($override_total_matches)){ $total_matches = $override_total_matches;  }
                for ($x = 0; $x < $total_matches; $x++) {
                                    $thumb = $matches[3][$x];
                                    $title = $matches[6][$x];
                                    $duration = $matches[7][$x];
                                    $vid_id = $matches[1][$x];
                                    $chunks = explode('/', $vid_id);
                                    $vid_id2 = $chunks[1];
                                    if(strtolower($send_photo_or_video) == "video"){    $chunks_thumb = explode('/', $thumb);
                                    $thumb = $chunks_thumb[0]."//".$chunks_thumb[2]."/".$chunks_thumb[3]."/videopreview/".$chunks_thumb[5]."/".$chunks_thumb[6]."/".$chunks_thumb[7]."/".$chunks_thumb[8]."_169.mp4"; }
                            		$data = [ 
                            		            'chat_id' => $chat_id,
                            		            'reply_to_message_id' => $reply_to_message_id,
                                                strtolower($send_photo_or_video) => $thumb,  //replace with your image/video url
                                                'caption' =>"<b>Title :</b> $title \n\r <b>Duration :</b> $duration" ,//'caption' => "<b>View Video</b>:\n\r 1. ⬇️/Normal_$vid_id2 \n\r 2. ⬇️/HD_$vid_id2 \n\r 3. Related Video: /Related_$vid_id2 \n\r<b>Title :</b> $title \n\r <b>Duration :</b> $duration" ,
                                                //'caption' => "<b>View Video</b>: /click_$vid_id2 \n\r <b>Title :</b> $title \n\r <b>Duration :</b> $duration" ,
                                                'parse_mode' => 'html', //Optional. Mode for parsing entities in the video caption. See formatting options for more details.
                                            ]; 
                                    $keyboard = [ "inline_keyboard" => [[
                                                                [ "text" => "Normal",    "callback_data" => "/Normal_$vid_id2" ],
                                                                [ "text" => "HD", "callback_data" => "/HD_$vid_id2" ],
                                                                [ "text" => "Related Videos", "callback_data" => "/Related_$vid_id2"]
                                                            ]]
                                    ];
                                    $keyboard = json_encode($keyboard);
                                   $url_co[] = "https://api.telegram.org/bot$token/send".ucfirst($send_photo_or_video)."?". http_build_query($data)."&reply_markup=".$keyboard;
                                }
                            $init_final = curl_fetch_multi_2($url_co);       //print_r( $init_final );
                               /****************** Next Page buttons ***********/ 
                                //str_contains only available in php 8+ //for next page buttons
                                $next_page = false;
                                if ($php_version >=8){  if (str_contains($init, 'next-page')) { $next_page = true; }    }
                                else { if (strpos($init, 'next-page') !== false) { $next_page = true;  }                }
                                if($next_page == true){
                                if(empty($pageNum)) { $pageNum="0"; }
                                $nextP = $pageNum + 1;
                                //$prevP = $pageNum - 1 ;
                                if($pageNum>=2){ $prevP = $pageNum - 1; $prevP = $base_site_url."/new/".$prevP; $prevPtitle = "Previous Page"; } else { $prevP = "/home"; $prevPtitle = "Home Page"; }
                                $keyboard_page = [ "inline_keyboard" => [[
                                                                            [ "text" => "Next Page",    "callback_data" => $base_site_url."/new/".$nextP ],
                                                                            [ "text" => $prevPtitle, "callback_data" => $prevP ]
                                                                        ]]   
                                    ];
                            $keyboard_page = json_encode($keyboard_page);
                            $url_ee = "https://api.telegram.org/bot$token/sendMessage?chat_id=$chat_id&text=­&reply_markup=".$keyboard_page;
                        $init = curlCommand(false,$url_ee); 
                                }
                    /**************************next page button end *************/
                    
    break;

  case "2":
                //it will scrap the SEARCH crateria page and will send the thumbs with video scrap links links
                $init = curlCommand(false,$xv_first_url,"","");
                $re_thumb = '/<div class="thumb"><a href="(.*)"><img src="(.*)data-src="(.*?)" data-idcdn="(.*)" title="(.*?)">(.*?) <span class="duration">(.*?)<\/span><\/a><\/p>/m';
                preg_match_all($re_thumb, $init, $matches);
                $total_matches = sizeof($matches[3]); 
                if(!empty($override_total_matches)){ $total_matches = $override_total_matches;  }
                for ($x = 0; $x <= $total_matches; $x++) {
                                    $thumb = $matches[3][$x];
                                    $title = $matches[6][$x];
                                    $duration = $matches[7][$x];
                                    $vid_id = $matches[1][$x];
                                        $chunks = explode('/', $vid_id);
                                        $vid_id2 = $chunks[1];
                                    if(strtolower($send_photo_or_video) == "video"){    $chunks_thumb = explode('/', $thumb);
                                    $thumb = $chunks_thumb[0]."//".$chunks_thumb[2]."/".$chunks_thumb[3]."/videopreview/".$chunks_thumb[5]."/".$chunks_thumb[6]."/".$chunks_thumb[7]."/".$chunks_thumb[8]."_169.mp4"; }
                            		$data = [ 
                            		            'chat_id' => $chat_id,
                            		            'reply_to_message_id' => $reply_to_message_id,
                                                 strtolower($send_photo_or_video) => $thumb,  //replace with your image/video url
                                                'caption' =>"<b>Title :</b> $title \n\r <b>Duration :</b> $duration" ,//'caption' => "<b>View Video</b>:\n\r 1. ⬇️/Normal_$vid_id2 \n\r 2. ⬇️/HD_$vid_id2 \n\r 3. Related Video: /Related_$vid_id2 \n\r<b>Title :</b> $title \n\r <b>Duration :</b> $duration" ,
                                                //'caption' => "<b>View Video</b>: /click_$vid_id2 \n\r <b>Title :</b> $title \n\r <b>Duration :</b> $duration" ,
                                                'parse_mode' => 'html', //Optional. Mode for parsing entities in the video caption. See formatting options for more details.
                                            ]; 
                                    $keyboard = [ "inline_keyboard" => [[
                                                                [ "text" => "Normal",    "callback_data" => "/Normal_$vid_id2" ],
                                                                [ "text" => "HD", "callback_data" => "/HD_$vid_id2" ],
                                                                [ "text" => "Related Videos", "callback_data" => "/Related_$vid_id2"]
                                                            ]]
                                    ];
                                    $keyboard = json_encode($keyboard);
                                   $url_co[] = "https://api.telegram.org/bot$token/send".ucfirst($send_photo_or_video)."?". http_build_query($data)."&reply_markup=".$keyboard;
                                }
                            $init_final = curl_fetch_multi_2($url_co);
                               /****************** Next Page buttons ***********/ 
                                //str_contains only available in php 8+ //for next page buttons
                                $next_page = false;
                                if ($php_version >=8){  if (str_contains($init, 'next-page')) { $next_page = true; }    }
                                else { if (strpos($init, 'next-page') !== false) { $next_page = true;  }                }
                                if($next_page == true){
                                if(empty($pageNum)) { $pageNum="0"; }
                                $nextP = $pageNum + 1;
                                //$prevP = $pageNum - 1 ;
                                if($pageNum>=2){ $prevP = $pageNum - 1; $prevP = "/Page_".$base_site_url."/?k=$text1%26p=".$prevP; $prevPtitle = "Previous Page"; } else { $prevP = "/home"; $prevPtitle = "Home Page"; }
                                $keyboard_page = [ "inline_keyboard" => [[
                                                                            [ "text" => "Next Page",    "callback_data" => "/Page_".$base_site_url."/?k=".$text1."%26p=".$nextP ],
                                                                            [ "text" => $prevPtitle, "callback_data" => $prevP ]
                                                                        ]]   
                                    ];
                            $keyboard_page = json_encode($keyboard_page);
                            $url_ee = "https://api.telegram.org/bot$token/sendMessage?chat_id=$chat_id&text=­&reply_markup=".$keyboard_page;
                        $init = curlCommand(false,$url_ee); 
                        //echo $init;
                        //print_r($keyboard_page);
                                }
                    /**************************next page button end *************/

    break;
  case "3":
                //sending video link
                $text = $xv_first_url;
                $init = curlCommand(false,$xv_first_url,"","");
                $re_vid = '/<script>(.*?)<\/script>/m'; //for all the scripts in page
                $re_vid_low = '/setVideoUrlLow\(\'(.*?)\'\);/m';
                $re_vid_high = '/setVideoUrlHigh\(\'(.*?)\'\);/m';
                $re_vid_title = '/setVideoTitle\(\'(.*?)\'\);/m';
                
                preg_match_all($re_vid_low, $init, $matchesLow);
                preg_match_all($re_vid_high, $init, $matchesHigh);
                preg_match_all($re_vid_title, $init, $matchesTitle);
                
                $re_vid_low_link = $matchesLow[1][0];
                $re_vid_high_link = $matchesHigh[1][0];
                $re_vid_title_text = $matchesTitle[1][0];
                
                if($Quality == "HD"){ $videoDecision = $re_vid_high_link; $videoDecisionText = "HD";}
                                else {$videoDecision = $re_vid_low_link; $videoDecisionText = "Normal";}
                                        $data = [ 
                        		            'chat_id' => $chat_id,
                        		            'reply_to_message_id' => $reply_to_message_id,
                                            'video' => $videoDecision,  //replace with your image url
                                            'caption' => "<b>Title :</b> $re_vid_title_text \n\r<b>Quality : </b>$videoDecisionText" ,
                                            'parse_mode' => 'html', //Optional. Mode for parsing entities in the video caption. See formatting options for more details.
                                        ];
                $url_co = "https://api.telegram.org/bot$token/sendVideo?". http_build_query($data);
                $init = curlCommand(false,$url_co,"","");
                $data = json_decode($init);
                $type = "video";/* v = video */ $sender_N = $data->result->chat->id; $file_id_N = $data->result->video->file_id;$file_unique_id_N = $data->result->video->file_unique_id; $caption_N = $data->result->caption;  // for fev button
                $sent_or_not = $data->ok;
                if($sent_or_not != 1) { 
                                        //get file size
                                        $init = curlCommand(true,$videoDecision);
                                        $HttpORs = isSecure();
                                        if ($HttpORs==1){ $scheme_vlc = "https://"; } else { $scheme_vlc = "http://"; }
                                        $data = [ 
                                            'chat_id' => $chat_id,
                                            'reply_to_message_id' => $reply_to_message_id,
                                            'parse_mode' => 'html',
                                            'caption' => "File Size exceeds the limit of ".$limit_size_setting.$init
                                                                                                                //'document' => $scheme_vlc.$_SERVER['HTTP_HOST'].parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)."?title_vlc=$re_vid_title_text&url_vlc=$videoDecision"
                                                                                                                //  'text' => $scheme_vlc.$_SERVER['HTTP_HOST'].parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)."?title_vlc=$re_vid_title_text&url_vlc=$videoDecision"
                                                                                                                //?title_vlc=asdghs&url_vlc=
                                        ];    
                                        $createFile = VLCfile($re_vid_title_text,$videoDecision,$pExtension); //old way         //$url_co = "https://api.telegram.org/bot$token/sendDocument?". http_build_query($data);          //$init = curlCommand(false,$url_co,"","");
                                        $url_co = "https://api.telegram.org/bot$token/sendDocument?";
                                        $init = localCURL($url_co,$data,clean($re_vid_title_text).$pExtension);
                                        unlink(clean($re_vid_title_text).$pExtension); //delete file after work is done
                                        /*********for fev button***************/
                                        $data = json_decode($init);       //print_r($data);
                                        $type = "document"; /* d= document */ $sender_N = $data->result->chat->id; $file_id_N = $data->result->video->file_id; $file_unique_id_N = $data->result->video->file_unique_id;$caption_N = $data->result->caption;
                }
                            if( $fevouriteButton == true){
                                        $keyboard_page = [ "inline_keyboard" => [[
                                                                    [ "text" => "Add to Fevourite\u{2b50}",    "callback_data" => "/LIKE_$file_unique_id_N" ],
                                                                    [ "text" => "Love It!\u{2764}", "callback_data" => "/LOVE_$file_unique_id_N" ]
                                                                ]]   
                                                        ];
                                        $keyboard_page = json_encode($keyboard_page);
                                        $url_ee = "https://api.telegram.org/bot$token/sendMessage?chat_id=$chat_id&text=­&reply_markup=".$keyboard_page."&reply_to_message_id=1";
                                        $init = curlCommand(false,$url_ee);
                                        $str_good_content_tracker = unserialize(file_get_contents($fileIDandUniqueID));
                                        $str_good_content_tracker[$file_unique_id_N]= [$file_unique_id_N => $file_id_N,"type"=>$type,$sender_N=>$caption_N]; //using chat id to get captions :)
                                        file_put_contents($fileIDandUniqueID,serialize($str_good_content_tracker));
                            }
                
    break;
  case "4":
                $data = [ 
                    'chat_id' => $chat_id,
                    'reply_to_message_id' => $reply_to_message_id,
                    'parse_mode' => $parse_mode,
                    'text' => $text
                ];
            $url_co = "https://api.telegram.org/bot$token/sendMessage?". http_build_query($data);
            $init = curlCommand(false,$url_co);
            //echo $init;
            
  break;
  case "5":
                //it will scrap the related url page and will send the thumbs with video scrap links
                $init = curlCommand(false,$xv_first_url);
                $re_thumb = '/var video_related=(.*?);window\.wpn_/m';
                preg_match_all($re_thumb, $init, $matches);
                $obj = json_decode($matches[1][0]);
                $total_matches = sizeof($obj);
                if(!empty($override_total_matches)){ $total_matches = $override_total_matches;  }
                for ($x = 0; $x < $total_matches; $x++) {
                                    $thumb = $obj[$x]->i;
                                    $title = $obj[$x]->t;
                                    $duration = $obj[$x]->d;
                                    $vid_id = $obj[$x]->u;
                                    $chunks = explode('/', $vid_id);
                                    $vid_id2 = $chunks[1];
                                    if(strtolower($send_photo_or_video) == "video"){    $chunks_thumb = explode('/', $thumb);
                                    $thumb = $chunks_thumb[0]."//".$chunks_thumb[2]."/".$chunks_thumb[3]."/videopreview/".$chunks_thumb[5]."/".$chunks_thumb[6]."/".$chunks_thumb[7]."/".$chunks_thumb[8]."_169.mp4"; }
                            		$data = [ 
                            		            'chat_id' => $chat_id,
                            		            'reply_to_message_id' => $reply_to_message_id,
                                                 strtolower($send_photo_or_video) => $thumb,  //replace with your image/video url
                                                'caption' =>"<b>Title :</b> $title \n\r <b>Duration :</b> $duration" ,//'caption' => "<b>View Video</b>:\n\r 1. ⬇️/Normal_$vid_id2 \n\r 2. ⬇️/HD_$vid_id2 \n\r 3. Related Video: /Related_$vid_id2 \n\r<b>Title :</b> $title \n\r <b>Duration :</b> $duration" ,
                                                //'caption' => "<b>View Video</b>: /click_$vid_id2 \n\r <b>Title :</b> $title \n\r <b>Duration :</b> $duration" ,
                                                'parse_mode' => 'html', //Optional. Mode for parsing entities in the video caption. See formatting options for more details.
                                            ]; 
                                    $keyboard = [ "inline_keyboard" => [[
                                                                [ "text" => "Normal",    "callback_data" => "/Normal_$vid_id2" ],
                                                                [ "text" => "HD", "callback_data" => "/HD_$vid_id2" ],
                                                                [ "text" => "Related Videos", "callback_data" => "/Related_$vid_id2"]
                                                            ]]
                                    ];
                                    $keyboard = json_encode($keyboard);
                        $url_co[] = "https://api.telegram.org/bot$token/send".ucfirst($send_photo_or_video)."?". http_build_query($data)."&reply_markup=".$keyboard;
                    }
                    
                    $init = curl_fetch_multi_2($url_co);
                    //print_r($init); //for checking errors by sending post request. Note: remove/exclude reply_to_message_id
    break;

      case "6":
                if(empty($callback_query_id)){$callback_query_id ='';} //it will supress the php notice
                $data = [ 
                    'chat_id' => $chat_id,
                    'reply_to_message_id' => $reply_to_message_id,
                    'parse_mode' => $parse_mode,
                    'callback_query_id' => $callback_query_id,
                    'text' => 'Added to '.$likeORlove."D LIBRARY."
                ];
                
                //extract file_id from unique ID
                $allocate_data = unserialize(file_get_contents($fileIDandUniqueID));
                $allocate_data = json_encode($allocate_data);
                $allocate_data1 = json_decode($allocate_data);
                $file_id = $allocate_data1->$text1->$text1;
                $type = $allocate_data1->$text1->type;
                $caption = $allocate_data1->$text1->$chat_id;
                                        $str_good_content_tracker = file_get_contents($user_fevourites);
                                        $str_good_content_tracker = json_decode($str_good_content_tracker,true);
                                        $str_good_content_tracker[$chat_id][$likeORlove][] = [$type.$caption=>$file_id];
                                        $str_good_content_tracker = json_encode($str_good_content_tracker);
                                        file_put_contents($user_fevourites,$str_good_content_tracker);

            if(empty($callback_query_id)){$url_co = "https://api.telegram.org/bot$token/sendMessage?". http_build_query($data);}
            else {$url_co = "https://api.telegram.org/bot$token/answerCallbackQuery?". http_build_query($data); }
            $init = curlCommand(false,$url_co);
            //echo $init;
            
  break;
      case "7":
          
          if($text=="/love"){
                $data = file_get_contents("$user_fevourites");
                $data_decoded = json_decode($data);
                $requested_user_data = $data_decoded->$chat_id->LOVE;
                $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($requested_user_data));
                $vid_num = 0;
                foreach($iterator as $key => $value) {
                    $vid_num = $vid_num + 1 ;
                    //$key = str_replace("Quality","\nQuality",$key);
                    $data_for_sending[] = "$vid_num.$key /getL_$vid_num\n  /removeL_$vid_num\n"; //$data_for_sending[] = "$vid_num.$key => $value\n"; no need to send file_id
                }
            $data2 = implode("\n",$data_for_sending);
          }
          if($text=="/favourite"){
                $data = file_get_contents("$user_fevourites");
                $data_decoded = json_decode($data);
                $requested_user_data = $data_decoded->$chat_id->LIKE;
                $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($requested_user_data));
                $vid_num = 0;
                foreach($iterator as $key => $value) {
                    $vid_num = $vid_num + 1 ;
                    $data_for_sending[] = "$vid_num.$key   /getF_$vid_num\n  /removeF_$vid_num\n"; //$data_for_sending[] = "$vid_num.$key => $value\n"; no need to send file_id
                }
            $data2 = implode("\n",$data_for_sending);
          }
          if(empty($data2)){ $data2 = "Add Something in $text first then try this command." ;}
  $data = [ 
        'chat_id' => $chat_id,
        'text' => $data2,
        'reply_to_message_id' => $reply_to_message_id,
        'parse_mode' => 'html',
    ];
          $url_co = "https://api.telegram.org/bot$token/sendMessage?". http_build_query($data);  
          $init = curlCommand(false,$url_co);
  break;
    case "8":
        $data1 = file_get_contents("$user_fevourites");
        $data_decoded = json_decode($data1);
        $text2 = $text1 - 1;
        if($deciderrr == 1){
            if(substr(key($data_decoded->$chat_id->LOVE[$text2]), 0, 5) == "video"){ $file_type_decider = 'video'; echo $file_type_decider;}
                else {$file_type_decider = 'document';echo $file_type_decider;}
            //echo $file_type_decider;
            $requested_user_data = $data_decoded->$chat_id->LOVE[$text2];
            $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($requested_user_data));
            foreach($iterator as $key => $value) {  $file_id = $value; }
            
            $title = key($data_decoded->$chat_id->LOVE[$text2]);
            $data = [ 'chat_id' => $chat_id, $file_type_decider => $file_id, 'parse_mode' => 'html', 'caption' => $title];
            $url_co = "https://api.telegram.org/bot$token/send".ucfirst($file_type_decider)."?". http_build_query($data);
        }
        elseif($deciderrr == 2) {
            if(substr(key($data_decoded->$chat_id->LIKE[$text2]), 0, 5) == "video"){ $file_type_decider = 'video'; /*echo $file_type_decider;*/}
                else {$file_type_decider = 'document';/*echo $file_type_decider;*/}
            //echo $file_type_decider;
            $requested_user_data = $data_decoded->$chat_id->LIKE[$text2];
            $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($requested_user_data));
            foreach($iterator as $key => $value) {  $file_id = $value; }
            
            $title = key($data_decoded->$chat_id->LIKE[$text2]);
            $data = [ 'chat_id' => $chat_id, $file_type_decider => $file_id, 'parse_mode' => 'html', 'caption' => $title];
            $url_co = "https://api.telegram.org/bot$token/send".ucfirst($file_type_decider)."?". http_build_query($data);
        }
        elseif($deciderrr == 3) {   
                                    $data_decoded = json_encode($data_decoded);
                                    $data_decoded = json_decode($data_decoded,true);
                                    array_splice($data_decoded[$chat_id]['LOVE'],$text2,1);
                                    $data_encoded = json_encode($data_decoded); file_put_contents($user_fevourites,$data_encoded); $msg = "Removed";
                                    $data = [ 'chat_id' => $chat_id,'text' => 'Removed','parse_mode' => 'html',];
                                    $url_co = "https://api.telegram.org/bot$token/sendMessage?". http_build_query($data);
        }
        elseif($deciderrr == 4) {   
                                    $data_decoded = json_encode($data_decoded);
                                    $data_decoded = json_decode($data_decoded,true);
                                    array_splice($data_decoded[$chat_id]['LIKE'],$text2,1);
                                    $data_encoded = json_encode($data_decoded); file_put_contents($user_fevourites,$data_encoded); $msg = "Removed";
                                    $data = [ 'chat_id' => $chat_id,'text' => 'Removed','parse_mode' => 'html',];
                                    $url_co = "https://api.telegram.org/bot$token/sendMessage?". http_build_query($data);
        }
        $init = curlCommand(false,$url_co);
        //echo $init;
    break;
  default:
    $text = "Something went wrong!!! \r\n Default condition";
                $data = [ 
                    'chat_id' => $chat_id,
                    'reply_to_message_id' => $reply_to_message_id,
                    'text' => $text
                ];
            $url_co = "https://api.telegram.org/bot$token/sendMessage". http_build_query($data);
            $init = curlCommand(false,$url_co,"","");
}
?>
