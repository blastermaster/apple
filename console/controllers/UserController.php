<?php

namespace console\controllers;

use common\models\User;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class UserController extends Controller
{
    public function actionCreate($username = null, $email = null, $password = null)
    {
        if ($username === null) {
            $username = $this->prompt('Логин:', ['required' => true]);
        }

        if ($email === null) {
            $email = $this->prompt('Email:', ['required' => true, 'validator' => function ($input, &$error) {
                if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Некорректный email';
                    return false;
                }
                return true;
            }]);
        }

        if ($password === null) {
            $password = $this->prompt('Пароль:', ['required' => true]);
        }

        if (User::findByUsername($username)) {
            $this->stdout("Пользователь с логином '{$username}' уже существует.\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }

        if (User::find()->where(['email' => $email])->exists()) {
            $this->stdout("Пользователь с '{$email}' уже существует.\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $user = new User();
        $user->username = $username;
        $user->email = $email;
        $user->setPassword($password);
        $user->generateAuthKey();
        $user->status = User::STATUS_ACTIVE;

        if ($user->save()) {
            $this->stdout("Пользователь '{$username}' успешно добавлен.\n", Console::FG_GREEN);
            return ExitCode::OK;
        }

        $this->stdout("Ошибка создания пользователя.\n", Console::FG_RED);
        foreach ($user->errors as $attribute => $errors) {
            foreach ($errors as $error) {
                $this->stdout("  - {$attribute}: {$error}\n", Console::FG_RED);
            }
        }

        return ExitCode::DATAERR;
    }
}

