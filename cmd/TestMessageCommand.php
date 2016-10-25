<?php

class TestMessageCommand extends ConsoleKit\Command
{
    private $bodys = array(
        '{"type":"text","chat":{"id":"chat1"},"from":{"id":"uid1","name":"uname1"},"text":"你好啊你好啊你好啊你好啊你好啊你好啊zzz"}',
        '{"type":"file","chat":{"id":"chat1"},"from":{"id":"uid1","name":"uname1"},"file":{"src":"http://127.0.0.1/","size":280,"name":"filename"}}',
        '{"type":"image","chat":{"id":"chat1"},"from":{"id":"uid1","name":"uname1"},"image":{"src":"http://127.0.0.1/a.png","height":20,"width":20,"size":300}}',
        '{"type":"event","chat":{"id":"chat1"},"from":{"id":"uid1","name":"uname1"},"event":{"type":"add"}}',
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
        $i = rand(0,3);
        return $this->bodys[0];
    }
}