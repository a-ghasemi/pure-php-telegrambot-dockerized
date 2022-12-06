<?php

namespace App\Bot\General;

use App\Models\BotConnection;
use GuzzleHttp\Client;


#sends requests to telegram server
class TelegramPost
{
    protected $bot;
    protected $httpClient;

    public function __construct(BotConnection $bot)
    {
        $this->bot = $bot;
        $this->httpClient = new Client(['exceptions' => false, 'verify' => false]);
    }

    public function send($mode, $command, $params){
        return ($mode == 'json') ?
            $this->sendPostRequest($command, $params)
            : $this->sendGetRequest($command, $params);
    }

    protected function sendGetRequest($command, $params)
    {
        $result = $this->httpClient->get($this->createUrl($command), ['form_params' => $params]);

        $content = $result->getBody()->getContents();
        return [
            'status' => $result->getStatusCode(),
            'message' => json_decode($content, true)
        ];
    }

//------------------------------------------------------------------------------
    public function getPhotoRequest($bot, $file_id, $image_type = 'all', $dest_folder = null, $dest_file = null)
    {
        try {
            $tmp = [
                'file_id' => $file_id,
            ];

            $ret = $this->sendPostRequest($bot, "getFile", $tmp);

            if ($ret['status'] != 200) {
                mylog('Get File From Telegram','error', ['get file from telegram', 'bot_id: ' . $bot->id, 'file_id: ' . $file_id, $ret]);
                return;
            }

            if (!$ret['message']['ok']) {
                mylog('Get File From Telegram','error', ['get file from telegram', 'bot_id: ' . $bot->id, 'file_id: ' . $file_id, $ret]);
                return;
            }
            $client = new Client(['exceptions' => false, 'verify' => false]);
            $res = $client->get(API_URL_TELEGRAM_FILE . $bot->robotoken . '/' . $ret['message']['result']['file_path']);

//            if($res->getStatusCode() == '200') $webhook->update_status('trigged');
            if ($res->getStatusCode() != 200) {
                mylog('Get File From Telegram','error', ['get file from telegram by path', 'bot_id: ' . $bot->id, 'file_id: ' . $file_id, $ret, $res]);
                return;
            }
        } catch (\Exception $e) {
            getFormedError($e);
        }

        $content = $res->getBody()->getContents();


        $state = 200;
        $maxSize = null;
        $imageExt = 'png';
        $image_type = \App\Models\ImageType::FindBySlug($image_type);
        $image_sizes = (is_null($image_type->size_list))?null:explode('|',$image_type->size_list);
        $data = [];
        $path = $image_type->folder;
        $fullpath = public_path($path);


        $tmpImageName = time() . sha1(rand(1, 1000));
        file_put_contents("/tmp/$tmpImageName.$imageExt", $content);
        if (!is_null($image_type->size_list))
//            ImageWork::make("/tmp/$tmpImageName.$imageExt", ['width' => $image_type->max_width, 'height' => $image_type->max_height])->save("/tmp/$tmpImageName.$imageExt");
            IntImage::make("/tmp/$tmpImageName.$imageExt")->resize($image_type->max_width, $image_type->max_height)->save("/tmp/$tmpImageName.$imageExt");

        $fileReq = pathToUploadedFile("/tmp/$tmpImageName.$imageExt");
        $rec = \App\Models\Image::whereByTypeKey(['all', $image_type->slug])->where('md5', md5_file($fileReq))->where('folder', $path)->first();

        if (!empty($rec)) $data = $rec->id;
        else {
            $imageName = time() . sha1(rand(1, 1000));
//                    $imageExt = $fileReq->extension();
//            ImageWork::make("/tmp/$tmpImageName.$imageExt")->save($path . $imageName . '.' . $imageExt);
            IntImage::make("/tmp/$tmpImageName.$imageExt")->save($path . $imageName . '.' . $imageExt);
            if (!empty($image_sizes))
                foreach ($image_sizes as $imgsize) {
                    $imgsize = explode(',', $imgsize);
                    if ($imgsize[0] * $imgsize[1] > 0)
//                        ImageWork::make("/tmp/{$tmpImageName}.$imageExt", ['width' => $imgsize[0], 'height' => $imgsize[1]])->save($path . $imageName . "_{$imgsize[0]}_{$imgsize[1]}" . '.' . $imageExt);
                        IntImage::make("/tmp/{$tmpImageName}.$imageExt")->resize($imgsize[0], $imgsize[1])->save($path . $imageName . "_{$imgsize[0]}_{$imgsize[1]}" . '.' . $imageExt);
                }
            $fileReq = pathToUploadedFile($path . $imageName . '.' . $imageExt);

            $tmp['mimetype'] = $fileReq->getMimeType();
            $tmp['filesize'] = $ret['message']['result']['file_path'];
            $tmp['orginal_name'] = $fileReq->getClientOriginalName();
            $tmp['size'] = getimagesize($fileReq);

            if ($fileReq && $fileReq->isFile() && !$fileReq->isExecutable()) {
                $request_arr = [
                    'folder'       => $path,
                    'filename'     => $imageName,
                    'fileext'      => $imageExt,
                    'storage_disk'  => null,
                    'filesize'     => $tmp['filesize'],
                    'width'        => $tmp['size'][0],
                    'height'       => $tmp['size'][1],
                    'mimetype'     => $tmp['mimetype'],
                    'md5'          => null,
                    'orginal_name' => $tmp['orginal_name'],
                    'type'         => $image_type->id,
                    'owner_id'     => null,
                ];

                $ret = \App\Models\Image::create($request_arr);
                if (isset($ret)) $data = $ret->id; else $state = 500;
            } else $state = 500;

        }

        return ['status' => $state, 'data' => $data];
    }

    public function getFileRequest($bot, $file_id, $dest_folder = 'attachments/', $dest_file = null)
    {
        try {
            $tmp = [
                'file_id' => $file_id,
            ];

            $ret = $this->sendPostRequest($bot, "getFile", $tmp);
            if ($ret['status'] != 200) {
                mylog('Get File From Telegram','error', ['get file from telegram', 'bot_id: ' . $bot->id, 'file_id: ' . $file_id, $ret]);
                return;
            }

            if (!$ret['message']['ok']) {
                mylog('Get File From Telegram','error', ['get file from telegram', 'bot_id: ' . $bot->id, 'file_id: ' . $file_id, $ret]);
                return;
            }
            $client = new Client(['exceptions' => false, 'verify' => false]);
            $res = $client->get(API_URL_TELEGRAM_FILE . $bot->robotoken . '/' . $ret['message']['result']['file_path']);

            if ($res->getStatusCode() != 200) {
                mylog('Get File From Telegram','error', ['get file from telegram by path', 'bot_id: ' . $bot->id, 'file_id: ' . $file_id, $ret, $res]);
                return;
            }
        } catch (\Exception $e) {
            getFormedError($e);
        }

        $content = $res->getBody()->getContents();
        $file_ext = strtolower(substr($ret['message']['result']['file_path'],strrpos($ret['message']['result']['file_path'],'.')));

        $state = 200;
        $maxSize = null;
        $data = [];

        $fileName = time() . sha1(rand(1, 1000));
        file_put_contents(public_path($dest_folder.$fileName.$file_ext), $content);

        $fileReq = pathToUploadedFile(public_path($dest_folder.$fileName.$file_ext));

        $rec = \App\Models\Attachment::where('md5', md5_file($fileReq))->where('folder', $dest_folder)->first();

        if (!empty($rec)) $data = $rec->id;
        else {
            $fileExt = $fileReq->extension();

            $tmp['mimetype'] = $fileReq->getMimeType();
            $tmp['filesize'] = $ret['message']['result']['file_path'];
            $tmp['orginal_name'] = $fileReq->getClientOriginalName();

            if ($fileReq && $fileReq->isFile() && !$fileReq->isExecutable()) {
                $request_arr = [
                    'storage_disk'  => null,
                    'folder'       => $dest_folder,
                    'filename'     => $fileName,
                    'fileext'      => $fileExt,
                    'filesize'     => $tmp['filesize'],

                    'width'        => null,
                    'height'       => null,
                    'duration'     => null,
                    'thumb'        => null,

                    'mimetype'     => $tmp['mimetype'],
                    'md5'          => null,
                    'orginal_name' => $tmp['orginal_name'],
                    'type'         => null,
//                    'owner_id'     => null,
                ];

                $ret = \App\Models\Attachment::create($request_arr);
                if (isset($ret)) $data = $ret->id; else $state = 500;
            } else $state = 500;

        }

        return ['status' => $state, 'data' => $data];
    }
//------------------------------------------------------------------------------

    protected function sendPostRequest($command, $params)
    {
        $result = $this->httpClient->post($this->createUrl($command), ['form_params' => $params ]);

        $content = $result->getBody()->getContents();
        return [
            'status' => $result->getStatusCode(),
            'message' => json_decode($content, true)
        ];
    }


    private function createUrl(string $command):string{
        return config('telegram.api_url') . $this->bot->robot_token . '/' . $command;
    }
}
