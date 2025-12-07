<?php

namespace console\controllers;

use backend\models\Apple;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class AppleController extends Controller
{
    public function actionCheckRotten()
    {
        $this->stdout("Проверка яблок на испорченность...\n", Console::FG_YELLOW);

        $count = Apple::checkRottenApples();

        if ($count > 0) {
            $this->stdout("Обработано гнилых яблок: {$count}\n", Console::FG_GREEN);
        } else {
            $this->stdout("Гнилых яблок не найдено.\n", Console::FG_GREEN);
        }

        return ExitCode::OK;
    }
}

