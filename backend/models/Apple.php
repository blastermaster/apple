<?php

namespace backend\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Exception;

class Apple extends ActiveRecord
{
    const int STATUS_ON_TREE = 1;
    const int STATUS_FELL = 2;
    const int STATUS_ROTTEN = 3;
    const int ROTTEN_HOURS = 5;

    private static $colors = ['green', 'red', 'yellow'];

    public static function tableName()
    {
        return '{{%apple}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function __construct($color = null, $config = [])
    {
        parent::__construct($config);
        if ($color !== null) {
            $this->color = $color;
        }
    }

    public function init()
    {
        parent::init();
        if ($this->isNewRecord) {
            if (empty($this->color)) {
                $this->color = self::generateRandomColor();
            }
            if (empty($this->appeared_at)) {
                $this->appeared_at = self::generateRandomAppearedAt();
            }
            if (empty($this->status)) {
                $this->status = self::STATUS_ON_TREE;
            }
            if ($this->eaten_percent === null) {
                $this->eaten_percent = 0;
            }
        }
    }

    public function rules()
    {
        return [
            [['color', 'appeared_at', 'status', 'eaten_percent'], 'required'],
            [['appeared_at', 'fell_at', 'status', 'created_at', 'updated_at'], 'integer'],
            [['eaten_percent'], 'number', 'min' => 0, 'max' => 100],
            [['color'], 'string', 'max' => 50],
            [['status'], 'in', 'range' => [self::STATUS_ON_TREE, self::STATUS_FELL, self::STATUS_ROTTEN]],
        ];
    }

    public function __get($name)
    {
        if ($name === 'size') {
            return 1 - ($this->eaten_percent / 100);
        }
        return parent::__get($name);
    }

    public function fallToGround()
    {
        return $this->fall();
    }

    public function afterFind()
    {
        parent::afterFind();
        $this->checkAndMarkRotten();
    }

    public function fall()
    {
        if ($this->status !== self::STATUS_ON_TREE) {
            throw new Exception('Яблоко уже упало');
        }

        $this->status = self::STATUS_FELL;
        $this->fell_at = time();

        return $this->save(false);
    }

    public function eat($percent)
    {
        if ($this->status === self::STATUS_ON_TREE) {
            throw new Exception('Невозможно съесть яблоко на дереве');
        }

        if ($this->isRotten()) {
            throw new Exception('Не надо съесть гнилое яблоко');
        }

        if ($this->eaten_percent >= 100) {
            throw new Exception('Яблоко уже съедено');
        }

        $newPercent = $this->eaten_percent + $percent;
        if ($newPercent > 100) {
            $newPercent = 100;
        }

        $this->eaten_percent = $newPercent;

        if (!$this->save(false)) {
            throw new Exception('Ошибка при сохранении яблока');
        }

        if ($this->eaten_percent >= 100) {
            $this->delete();
        }

        return true;
    }

    public function isOnTree()
    {
        return $this->status === self::STATUS_ON_TREE;
    }

    public function isFell()
    {
        return $this->status === self::STATUS_FELL;
    }

    public function isRotten()
    {
        if ($this->status === self::STATUS_ROTTEN) {
            return true;
        }

        return $this->checkAndMarkRotten();
    }

    private function checkAndMarkRotten()
    {
        if ($this->status === self::STATUS_FELL && $this->fell_at !== null) {
            $hoursOnGround = (time() - $this->fell_at) / 3600;
            if ($hoursOnGround >= self::ROTTEN_HOURS) {
                $this->status = self::STATUS_ROTTEN;
                $this->save(false);
                return true;
            }
        }

        return false;
    }

    public static function generateRandomColor()
    {
        return self::$colors[array_rand(self::$colors)];
    }

    public static function generateRandomAppearedAt()
    {
        $now = time();
        $randomDays = rand(0, 30);
        return $now - ($randomDays * 24 * 3600);
    }

    public static function generateRandomStatus()
    {
        $statuses = [self::STATUS_ON_TREE, self::STATUS_FELL];
        return $statuses[array_rand($statuses)];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'color' => 'Цвет',
            'appeared_at' => 'Появление',
            'fell_at' => 'Упало',
            'status' => 'Статус',
            'eaten_percent' => 'Съедено',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ];
    }
}

