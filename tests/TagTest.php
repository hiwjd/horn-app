<?php
use PHPUnit\Framework\TestCase;
use Horn\Tag;
use Horn\Db;
use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;

class TagTest extends TestCase
{

    private $tag;
    private $data;

    public function setUp()
    {
        $logger = new Logger('app');
        $logger->pushHandler(new ErrorLogHandler());

        $dsn = 'mysql:host=127.0.0.1;dbname=horn';
        $user = 'root';
        $pass = 'rootMM123!@#';
        $db = new Db($logger, $dsn, $user, $pass);

        $this->tag = new Horn\Tag($logger, $db);

        $this->data = array(
            'oid' => 1,
            'sid' => '3rUUyOImiv0c2JKelNc',
            'vid' => 'SFnvhYMhKzIb9sIaVuvCN9H'
        );

        $db->Exec("truncate table tags");
        $db->Exec("truncate table visitor_tags");
    }

    public function tearDown()
    {
        $this->tag = null;
    }

    public function testAdd()
    {
        $data = $this->data;

        $res = $this->tag->add($data['oid'], "tag_a", "red", $data['sid']);
        $this->assertEquals(true, $res);
    }

    public function testEdit()
    {
        $data = $this->data;

        $res = $this->tag->edit($data['oid'], 1, "tag_b", $data['sid']);
        $this->assertEquals(true, $res);
    }

    public function testAttach()
    {
        $data = $this->data;

        $res = $this->tag->attach($data['oid'], $data['vid'], 1, $data['sid']);
        $this->assertEquals(true, $res);
    }

    public function testDetach()
    {
        $data = $this->data;

        $res = $this->tag->detach($data['oid'], $data['vid'], 1);
        $this->assertEquals(true, $res);
    }

    public function testDelete()
    {
        $data = $this->data;

        $res = $this->tag->delete($data['oid'], 1);
        $this->assertEquals(true, $res);
    }

}