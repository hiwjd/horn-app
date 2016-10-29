<?php

class TestMessageCommand extends ConsoleKit\Command
{
    private $bodys = array(
        '{"type":"text","chat":{"id":"chat1"},"from":{"id":"2yZGG2d1ZjDTd5gqgz03ON2","name":"王剑冬"},"text":"你好啊你好啊你好啊你好啊你好啊你好啊zzz"}',
        '{"type":"file","chat":{"id":"chat1"},"from":{"id":"uid1","name":"张三"},"file":{"src":"http://127.0.0.1/","size":280,"name":"filename"}}',
        '{"type":"image","chat":{"id":"chat1"},"from":{"id":"uid1","name":"李四"},"image":{"src":"http://127.0.0.1/a.png","height":20,"width":20,"size":300}}',
        '{"type":"request_chat","from":{"id":"uid1","name":"王五"},"cmd":{"chat":{"id":"chat1"},"uids":["rBBn5on5DjepB8fZD4yUf3K"]}}',
        '{"type":"join_chat","from":{"id":"rBBn5on5DjepB8fZD4yUf3K","name":"王五"},"cmd":{"chat":{"id":"chat1"}}}'
    );

    public function execute(array $args, array $options = array())
    {
        $this->writeln('hello world!', ConsoleKit\Colors::GREEN);

        $client = new GuzzleHttp\Client();
        $url = "http://app.horn.com:9092/api/message";

        for($i=0; $i<1; $i++) {
            $options = array(
                "body" => $this->randomBody()//'{"type":"text","text":"你好啊你好啊你好啊你好啊你好啊你好啊<'.$i.'>"}'
            );
            $res = $client->request("POST", $url, $options);
            $body = $res->getBody()->getContents();
            $this->writeln($body, ConsoleKit\Colors::GREEN);
        }
    }

    private function randomBody() {
        $i = rand(0,4);
        return $this->bodys[$i];
    }
}