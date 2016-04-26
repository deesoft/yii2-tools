<?php

namespace dee\tools;

use Yii;
use yii\base\Object;
use yii\di\Instance;
use yii\web\Cookie;
use yii\base\InvalidCallException;
use yii\db\Connection;
use yii\db\Query;
use yii\web\Application as WebApplication;

/**
 * Description of State
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class State extends Object
{
    /**
     * @var Connection 
     */
    public $db = 'db';
    /**
     * @var string table name
     */
    public $tableName = '{{%dee_client}}';
    /**
     * @var string table name
     */
    public $cliFileKey = '@runtime/dee_client_id.data';
    /**
     * @var string
     */
    public $cookieKey = 'dee_state_id';
    /**
     * @var array
     */
    protected $_states;
    /**
     * @var integer
     */
    private $_id;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->db = Instance::ensure($this->db, Connection::className());
        $this->createTable();

        if (Yii::$app instanceof WebApplication) {
            $id = Yii::$app->getRequest()->getCookies()->getValue($this->cookieKey);
        } else {
            $file = Yii::getAlias($this->cliFileKey);
            $id = is_file($file) ? (int) file_get_contents($file) : null;
        }
        if ($id === null || !is_numeric($id) || ($this->_states = $this->getData($id)) === false) {
            $primary = $this->db->getSchema()->insert($this->tableName, [
                'create_at' => time(),
            ]);
            $id = $primary['id'];
            $this->_states = [];
        }

        if (Yii::$app instanceof WebApplication) {
            $cookie = new Cookie([
                'name' => $this->cookieKey,
                'value' => $id,
                'expire' => time() + 30 * 24 * 3600,
            ]);
            Yii::$app->getResponse()->getCookies()->add($cookie);
        } elseif (isset($file)) {
            file_put_contents($file, $id, LOCK_EX);
        }
        $this->_states['id'] = $this->_id = $id;
    }

    /**
     * Create table if not exists
     */
    protected function createTable()
    {
        if ($this->db->getSchema()->getTableSchema($this->tableName) === null) {
            $this->db->createCommand()
                ->createTable($this->tableName, [
                    'id' => 'pk',
                    'created_at' => 'integer',
                    'updated_at' => 'integer',
                    'data' => 'binary',
                ])->execute();
        }
    }

    protected function getData($id)
    {
        $data = (new Query())->select(['data'])
                ->from($this->tableName)
                ->where(['id' => $id])->scalar();
        if ($data !== false) {
            return empty($data) ? [] : unserialize($data);
        }
        return false;
    }

    protected function save()
    {
        $this->db->createCommand()->update($this->tableName, [
            'updated_at' => time(),
            'data' => [serialize($this->_states), \PDO::PARAM_LOB],
            ], ['id' => $this->_id])->execute();
    }

    public function get($name, $default = null)
    {
        return array_key_exists($name, $this->_states) ? $this->_states[$name] : $default;
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function set($name, $value)
    {
        if ($name == 'id') {
            throw new InvalidCallException('Setting read-only property: ' . get_class($this) . '::' . $name);
        }
        $this->_states[$name] = $value;
        $this->save();
    }

    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    public function __isset($name)
    {
        return isset($this->_states[$name]);
    }

    public function states($states = null)
    {
        if ($states === null) {
            return $this->_states;
        } else {
            unset($states['id']);
            foreach ($states as $key => $value) {
                $this->_states[$key] = $value;
            }
            $this->save();
        }
    }
}
