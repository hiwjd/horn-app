<?php
namespace Horn;

use Psr\Log\LoggerInterface;

/**
 * 操作标签的类
 * 涉及的表：
 *     tags 标签元数据
 *     visitor_tags 访客的标签
 */
class Tag
{

    private $logger;
    private $db;

    public function __construct(LoggerInterface $logger, Db $db)
    {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function get($oid)
    {
        $this->logger->info("Tag.get oid[$oid]");
        $sql = "select id,name,color,ref_num from tags where oid=?";
        $tags = $this->db->GetRows($sql, array($oid));
        if(!is_array($tags)) {
            $tags = array();
        }
        return $tags;
    }

    public function getByVisitor($oid, $vid)
    {
        $this->logger->info("Tag.getByVisitor oid[$oid] vid[$vid]");
        $sql = "select t.id,t.name,t.color from visitor_tags vt left join tags t on vt.tag_id = t.id where vt.oid=? and vt.vid=?";
        $tags = $this->db->GetRows($sql, array($oid, $vid));
        if(!is_array($tags)) {
            $tags = array();
        }
        return $tags;
    }

    /**
     * 增加标签的元数据
     * @param  [type] $oid   组织ID
     * @param  [type] $name  标签
     * @param  [type] $color 颜色
     * @param  [type] $sid   操作的客服ID
     * @return [type]        true成功/TagException
     */
    public function add($oid, $name, $color, $sid)
    {
        $this->logger->info("Tag.add oid[$oid] name[$name] color[$color] sid[$sid]");
        $sql = "insert into tags(oid,name,color,sid) values (?,?,?,?)";
        if($this->db->Exec($sql, array($oid, $name, $color, $sid)) == 1) {
            $this->logger->info(" -> 成功");
            return true;
        }

        $this->logger->info(" -> 失败");
        throw new TagException("添加失败");
    }

    /**
     * 编辑标签
     * @param  [type] $oid   组织ID
     * @param  [type] $tagId 标签ID
     * @param  [type] $name  标签
     * @param  [type] $color 颜色
     * @return [type]        true成功/TagException
     */
    public function edit($oid, $tagId, $name, $color)
    {
        $this->logger->info("Tag.edit oid[$oid] tagId[$tagId] color[$color]");
        $sql = "update tags set name=?, color=? where id = ?";
        if($this->db->Exec($sql, array($name, $color, $tagId)) >= 0) {
            $this->logger->info(" -> 成功");
            return true;
        }

        $this->logger->info(" -> 失败");
        throw new TagException("编辑失败");
    }

    /**
     * 删除标签
     * @param  [type] $oid   组织ID
     * @param  [type] $tagId 标签ID
     * @return [type]        true成功/TagException
     */
    public function delete($oid, $tagId)
    {
        $this->logger->info("Tag.delete oid[$oid] tagId[$tagId]");
        $sql = "delete from tags where id = ?";
        $affectNum = $this->db->Exec($sql, array($tagId));

        if($affectNum == 0) {
            $this->logger->info(" -> 删除标签元数据失败");
            throw new TagException("删除失败");
        }

        $sql = "delete from visitor_tags where tag_id = ?";
        $affectNum = $this->db->Exec($sql, array($tagId));
        if($affectNum >= 0) {
            $this->logger->info(" -> 成功");
            return true;
        }

        $this->logger->info(" -> 删除访客标签失败");
        throw new TagException("添加失败");
    }

    /**
     * 贴标签
     * 保证visitor_tags表存在oid,vid,tagId的记录
     * 统计tag被使用的次数
     * @param  [type] $oid     组织ID
     * @param  [type] $vid     访客ID
     * @param  [type] $tagId   标签ID
     * @param  [type] $sid     添加的客服ID
     * @return [type]          true成功/TagException
     */
    public function attach($oid, $vid, $tagId, $sid)
    {
        $this->logger->info("Tag.attach oid[$oid] vid[$vid] tagId[$tagId] sid[$sid]");
        $sql = "select count(1) from visitor_tags where oid=? and vid=? and tag_id=?";
        $ct = $this->db->GetNum($sql, array($oid, $vid, $tagId));

        if($ct > 0) {
            // 访客已经存在这个标签了，什么都不做
            $this->logger->info(" -> 访客已经有这个标签");
            return true;
        }

        $sql = "insert into visitor_tags(oid,vid,tag_id,sid) values (?,?,?,?)";
        if($this->db->Exec($sql, array($oid, $vid, $tagId, $sid)) == 0) {
            $this->logger->info(" -> 贴标签失败");
            return false;
        }

        $sql = "update tags set ref_num=ref_num+1 where id=?";
        $this->db->Exec($sql, array($tagId));

        $this->logger->info(" -> 贴标签失败");
        throw new TagException("贴标签失败");
    }

    /**
     * 撕标签
     * 删除visitor_tags中的记录
     * @param  [type] $oid   组织ID
     * @param  [type] $vid   访客ID
     * @param  [type] $tagId 标签ID
     * @return [type]        true成功/TagException
     */
    public function detach($oid, $vid, $tagId)
    {
        $this->logger->info("Tag.detach oid[$oid] vid[$vid] tagId[$tagId]");
        $sql = "delete from visitor_tags where oid=? and vid=? and tag_id=?";
        if($this->db->Exec($sql, array($oid, $vid, $tagId)) == 1) {
            $this->logger->info(" -> 成功");
            return true;
        }

        $this->logger->info(" -> 撕标签失败");
        throw new TagException("撕标签失败");
    }

}