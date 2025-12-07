<?php

namespace backend\tests\unit\models;

use backend\models\Apple;
use Codeception\Test\Unit;
use yii\db\Exception;

class AppleTest extends Unit
{
    protected $tester;

    public function testCreateAppleWithColor()
    {
        $apple = new Apple('green');
        $apple->save(false);

        verify($apple->color)->equals('green');
    }

    public function testCannotEatAppleOnTree()
    {
        $apple = new Apple('red');
        $apple->save(false);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Невозможно съесть яблоко на дереве');
        
        $apple->eat(50);
    }

    public function testSizeProperty()
    {
        $apple = new Apple('yellow');
        $apple->save(false);

        verify($apple->size)->equals(1.0);
    }

    public function testFallToGround()
    {
        $apple = new Apple('green');
        $apple->save(false);

        $apple->fallToGround();

        verify($apple->status)->equals(Apple::STATUS_FELL);
        verify($apple->fell_at)->notNull();
    }

    public function testEatAppleAfterFall()
    {
        $apple = new Apple('red');
        $apple->save(false);

        $apple->fallToGround();
        $apple->eat(25);

        verify($apple->eaten_percent)->equals(25);
        verify($apple->size)->equals(0.75);
    }

    public function testSizeAfterEating()
    {
        $apple = new Apple('yellow');
        $apple->save(false);

        verify($apple->size)->equals(1.0);

        $apple->fallToGround();
        $apple->eat(25);

        verify($apple->size)->equals(0.75);
    }
}

