<?php
# +----------------------------------------------------------------------
# | Author:Stark
# +----------------------------------------------------------------------
# | Date:2022/7/27
# +----------------------------------------------------------------------
# | Desc: php7 对MongoDB的基本操作
# +----------------------------------------------------------------------
class Nosql_NovelMongoDB
{
    private static $_obj = null;
    private $_manager = null;
    private $_client = null;

    private $_mongodbDatabase = 'dbName';
    private $_mongodbCollection = 'comment_novel_total';

    private function __construct()
    {
        $this->_manager = Lib_MongoDB::getInstance();
        $db = $this->_mongodbDatabase;
        $collection = $this->_mongodbCollection;
        $this->_client = $this->_manager->$db->$collection;
    }

    public static function getInstance()
    {
        if (is_null(self::$_obj)) {
            self::$_obj = new self();
        }
        return self::$_obj;
    }

    /**
     * 新增文档
     * @param array $comment
     * @return bool
     */
    public function add(array $comment)
    {
        $data = $this->_parseData($comment);
        $res = $this->_client->insertOne($data);
        return (bool)$res->getInsertedCount();
    }

    /**
     * 批量新增文档
     * @param array $data
     */
    public function addAll(array $data)
    {
        $insertData = [];
        foreach ($data as $datum) {
            $insertData[] = $this->_parseData($datum);
        }
        $this->_client->insertMany($insertData);
    }

    /**
     * 设置索引
     */
    public function init()
    {
        $db = $this->_mongodbDatabase;
        $this->_manager->$db->dropCollection($this->_mongodbCollection);
        $this->_manager->$db->createCollection($this->_mongodbCollection);
        $indexList = [
            ['create_time' => -1],
            ['delete_time' => -1],
            ['nid' => 1],
            ['status' => 1],
        ];
        foreach ($indexList as $index) {
            $this->_client->createIndex($index);
        }
    }

    /**
     * 入库前数据处理
     * 注意 value 数据类型，mongodb 查询比较严格
     *
     * @param array $comment
     * @return int[]
     */
    private function _parseData(array $comment)
    {
        return [
            // 原始数据
            'id' => (int)$comment['id'],
        ];
    }

    /**
     * 根据查询条件获取分页数据
     *
     * @param array $where
     * @param int $page
     * @param int $num
     * @return array
     */
    public function getListByConditionAndPage(array $where = [], int $page = 1, int $num = 10)
    {
        $client = $this->_client;
        $filterArr = [];
        //简单条件使用数组，$where给$filterArr赋值
        $filterArr['delete_time'] = 0;
        $filterArr['status'] = 2;

        //复杂条件使用$and
        $filterArr['$and'] = [
            ['create_time' => ['$gte' => (int)$where['begDate']]],
            ['create_time' => ['$lt' => (int)$where['endDate']]],
            ['status' => ['$ne' => 3 ]]
        ];

        $start = ($page - 1) * $num;
        $options = [
            'sort' => ['create_time' => -1],
            'skip' => $start,
            'limit' => $num,
        ];
        return $client->find($filterArr, $options)->toArray();
    }

    /**
     * 根据查询条件获取总条数
     *
     * @param array $where
     * @return int
     */
    public function getCommentCountByCondition(array $where = [])
    {

        $filter = [];
        $client = $this->_client;

        //简单条件使用数组，$where给$filterArr赋值
        $filter['delete_time'] = 0;
        $filter['status'] = 2;

        //复杂条件使用$and
        $filter['$and'] = [
            ['create_time' => ['$gte' => (int)$where['begDate']]],
            ['create_time' => ['$lt' => (int)$where['endDate']]],
            ['status' => ['$ne' => 3 ]]
        ];
        return $client->countDocuments($filter);
    }

    /**
     * 根据主键修改数据
     * @param object $_id mongoDB文档的主键
     * @param int $flag
     * @return int
     */
    public function updateFilter(object $_id, int $flag)
    {
        $update['filter_status'] = intval($flag);
        $count = 0;
        if ($update) {
            $query = [
                '_id' => $_id
            ];
            $res = $this->_client->updateOne($query, [
                '$set' => $update
            ]);
            $count = $res->getModifiedCount();
        }

        return $count;
    }

    /**
     * 可以用一个条件和多个条件进行检索
     * @param array $where
     * @param int $num 条数,一次读取多少取决于内存
     * @return mixed
     */
    public function getHistoryComment(array $where, $num = 1000)
    {
        $client = $this->_client;
        $filterArr = [];

        $filterArr['delete_time'] = 0;
        $filterArr['status'] = 1;
        $filterArr['gift_id'] = 0;

        if (isset($where['begDate']) && isset($where['endDate'])) {
            //大于等于and小于
            $filterArr['$and'] = [
                ['create_time' => ['$lt' => (int)$where['begDate']]],
                ['create_time' => ['$gte' => (int)$where['endDate']]],
                ['filter_status' => ['$exists' => false]]
            ];
        }

        $options = [
            'sort' => ['create_time' => -1],
            'skip' => 0,
            'limit' => $num,
        ];
        return $client->find($filterArr, $options)->toArray();
    }
}
