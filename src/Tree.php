<?php // CODE BY HW
namespace Hwphp;

class Tree {

    /**
     * 主键Id
     * @var string
     */
    protected $idKey = 'id';

    /**
     * 父级Id
     * @var string
     */
    protected $pidKey = 'pid';

    /**
     * 顶级父Id初始值
     * @var int
     */
    protected $pidInitial = 0;

    /**
     * 子级数组的键名
     * @var string
     */
    protected $childKey = 'children';

    /**
     * 是否追加级别字段
     * @var bool
     */
    protected $appendLevel = false;

    /**
     * 级别字段的键名
     * @var string
     */
    protected $levelKey = '_level';

    /**
     * 级别的初始值
     * @var int
     */
    protected $levelInitial = 0;

    /**
     * 是否追加元素的索引字段
     * @var bool
     */
    protected $appendIdx = false;

    /**
     * 当前元素的索引的键名
     * @var string
     */
    protected $idxKey = '_idx';

    /**
     * 当前元素所属数组最大索引的键名
     * @var string
     */
    protected $idxMaxKey = '_idxMax';

    /**
     * 用于处理元素数据的回调函数
     * @var mixed|null
     */
    protected $callbackFunction;

    /**
     * 数据数组
     * @var array
     */
    protected $data;

    /**
     * 获取树形结构数据的静态方法
     * @param array $data 要处理的数组
     * @param array $options 选项
     * @param mixed $cb 处理数据的回调函数
     * @return array
     */
    public static function get($data, $options = [], $cb = null)
    {
        return (new self($data, $options, $cb))->getTree();
    }

    /**
     * 构造方法
     * @param array $data 要处理的数组
     * @param array $options 选项
     * @param mixed $cb 处理数据的回调函数
     */
    public function __construct($data, $options = [], $cb = null)
    {
        $this->data = $data;
        $this->callbackFunction = $cb;

        foreach($options as $optionKey => $optionValue) {
            property_exists($this, $optionKey) && $this->{$optionKey} = $optionValue;
        }
    }

    /**
     * 获取属性结构数据
     * @return array
     */
    public function getTree()
    {
        $items = array_column($this->data, null, $this->idKey);

        $tree = [];
        foreach($items as $item) {
            $id = $item[$this->idKey];
            $pid = $item[$this->pidKey];
            if (isset($items[$pid])) {
                $items[$pid][$this->childKey][$id] = &$items[$id];
            } elseif ($pid == $this->pidInitial) {
                $tree[$id] = &$items[$id];
            }
        }

        return $this->handleTree($tree, $this->levelInitial);
    }

    /**
     * 对树形结构数据递归进行处理
     * @param array $items 树形结构数据
     * @param int $level 级别
     * @return array
     */
    protected function handleTree($items, $level = 0)
    {
        $idx = 0;
        $newTree = [];
        $length = count($items);
        foreach($items as $item) {
            $idx++;

            if (isset($item[$this->childKey]) && is_array($item[$this->childKey])) {
                $item[$this->childKey] = $this->handleTree($item[$this->childKey], $level + 1);
            } else {
                $item[$this->childKey] = [];
            }

            if ($this->appendLevel) {
                $item[$this->levelKey] = $level;
            }

            if ($this->appendIdx) {
                $item[$this->idxKey] = $idx;
                $item[$this->idxMaxKey] = $length;
            }

            if ($this->callbackFunction) {
                $newTree[] = call_user_func($this->callbackFunction, $item);
            } else {
                $newTree[] = $item;
            }
        }

        return $newTree;
    }
}
