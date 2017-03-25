<?php 
ini_set( 'display_errors', 1 );

require_once __DIR__ . '/vendor/autoload.php';

$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('CHANNEL_SECRET')]);

$signature = $_SERVER["HTTP_" . \LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];
try {
  $events = $bot->parseEventRequest(file_get_contents('php://input'), $signature);
} catch(\LINE\LINEBot\Exception\InvalidSignatureException $e) {
  error_log("parseEventRequest failed. InvalidSignatureException => ".var_export($e, true));
} catch(\LINE\LINEBot\Exception\UnknownEventTypeException $e) {
  error_log("parseEventRequest failed. UnknownEventTypeException => ".var_export($e, true));
} catch(\LINE\LINEBot\Exception\UnknownMessageTypeException $e) {
  error_log("parseEventRequest failed. UnknownMessageTypeException => ".var_export($e, true));
} catch(\LINE\LINEBot\Exception\InvalidEventRequestException $e) {
  error_log("parseEventRequest failed. InvalidEventRequestException => ".var_export($e, true));
}

foreach ($events as $event) {
  if (!($event instanceof \LINE\LINEBot\Event\MessageEvent)) {
    error_log('Non message event has come');
    continue;
  }
  if (!($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage)) {
    error_log('Non text message has come');
    continue;
  }
  $getText = $event->gettext();
  $reply = getTalk($getText);
  $bot->replyText($event->getReplyToken(), $reply);
}

function getTalk ($text) {
	// A3RT TalkAPI
	$url = "https://api.a3rt.recruit-tech.co.jp/talk/v1/smalltalk";
	// ポストするデータ
	$data = [
		"apikey" => getenv('API_KEY'),
		"query" => $text
	];

	// セッションを初期化
	$conn = curl_init();
	// オプション
	curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($conn, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($conn, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($conn, CURLOPT_URL,  $url);
	curl_setopt($conn, CURLOPT_POST, true);
	curl_setopt($conn, CURLOPT_POSTFIELDS, $data);
	// 実行
	$res = curl_exec($conn);
	// close
	curl_close($conn);

	//$res = mb_convert_encoding($res,'UTF-8');
	$obj = json_decode($res, false);
	$reply = $obj->results[0]->reply;
	return $reply;
}

