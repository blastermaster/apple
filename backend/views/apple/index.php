<?php

use backend\models\Apple;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\bootstrap5\LinkPager;
/** @var yii\web\View $this */
/** @var backend\models\AppleSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Яблоки';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="apple-index">

    <p>
        <?= Html::a('Создать яблоки', ['generate'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php Pjax::begin(['id' => 'apple-grid-pjax']); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'pager' => [
            'class' => LinkPager::class,
        ],
        'columns' => [
            [
                'attribute' => 'id',
            ],
            [
                'attribute' => 'color',
                'format' => 'raw',
                'value' => function ($model) {
                    $colors = [
                        'green' => ['name' => 'Зеленый', 'class' => 'success'],
                        'red' => ['name' => 'Красный', 'class' => 'danger'],
                        'yellow' => ['name' => 'Желтый', 'class' => 'warning'],
                    ];
                    $colorData = $colors[$model->color] ?? ['name' => $model->color, 'class' => 'secondary'];
                    return Html::tag('span', $colorData['name'], ['class' => 'badge bg-' . $colorData['class']]);
                },
            ],
            [
                'attribute' => 'appeared_at',
                'format' => 'datetime',
                'filter' => false,
            ],
            [
                'attribute' => 'fell_at',
                'format' => 'raw',
                'value' => function ($model) {
                    if ($model->fell_at === null) {
                        return '';
                    }
                    
                    $dateTime = Yii::$app->formatter->asDatetime($model->fell_at);
                    $result = $dateTime;
                    
                    if ($model->status === Apple::STATUS_FELL && !$model->isRotten()) {
                        $hoursOnGround = (time() - $model->fell_at) / 3600;
                        $remainingHours = Apple::ROTTEN_HOURS - $hoursOnGround;
                        
                        if ($remainingHours > 0) {
                            $hours = floor($remainingHours);
                            $minutes = floor(($remainingHours - $hours) * 60);
                            $result .= '<br><small class="text-muted">(до гниения осталось ' . $hours . 'ч ' . $minutes . 'м)</small>';
                        } else {
                            $result .= '<br><small class="text-danger">(должно было сгнить)</small>';
                        }
                    }
                    
                    return $result;
                },
                'filter' => false,
            ],
            [
                'attribute' => 'eaten_percent',
                'value' => function ($model) {
                    return number_format($model->eaten_percent, 2) . '%';
                },
                'filter' => false,
            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'value' => function ($model) {
                    $label = match ($model->status) {
                        Apple::STATUS_ON_TREE => 'Висит на дереве',
                        Apple::STATUS_FELL => 'Упало',
                        Apple::STATUS_ROTTEN => 'Гнилое',
                    };
                    return Html::tag('span', $label, ['class' => 'badge bg-' . ($model->status == Apple::STATUS_ON_TREE ? 'primary' : ($model->status == Apple::STATUS_FELL ? 'success' : 'danger'))]);
                },
            ],
            [
                'label' => 'Действия',
                'format' => 'raw',
                'value' => function ($model) {
                    $html = '';
                    
                    if ($model->status == Apple::STATUS_ON_TREE) {
                        $html .= Html::button('Упасть', [
                            'class' => 'btn btn-primary btn-sm apple-fall',
                            'data-id' => $model->id,
                        ]);
                    }
                    
                    if ($model->status == Apple::STATUS_FELL) {
                        if (!$model->isRotten()) {
                            $html .= Html::textInput('percent', '', [
                                'class' => 'form-control m-1 percent-input form-control-xs d-inline-block',
                                'type' => 'number', 
                                'min' => 1,
                                'max' => 100,
                                'step' => 1,
                                'placeholder' => '%',
                                'data-id' => $model->id,
                                'data-eaten' => $model->eaten_percent,
                            ]);
                            $html .= Html::button('Съесть', [
                                'class' => 'btn btn-success m-1 btn-sm apple-eat',
                                'data-id' => $model->id
                            ]);
                            $html .= Html::button('Испортиться', [
                                'class' => 'btn btn-warning m-1 btn-sm apple-rotten',
                                'data-id' => $model->id
                            ]);
                        }
                    }
                    
                    return $html;
                },
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>

<?php
$fallUrl = Url::to(['apple/fall']);
$eatUrl = Url::to(['apple/eat']);
$rottenUrl = Url::to(['apple/rotten']);
$csrfToken = Yii::$app->request->csrfToken;

$js = <<<JS
$(document).on('click', '.apple-fall', function() {
    var btn = $(this);
    var id = btn.data('id');
    
    btn.prop('disabled', true);
    
    $.ajax({
        url: '{$fallUrl}',
        method: 'POST',
        data: {
            id: id,
            _csrf: '{$csrfToken}'
        },
        success: function(response) {
            if (response.success) {
                $.pjax.reload({container: '#apple-grid-pjax'});
            } else {
                alert(response.message);
                btn.prop('disabled', false);
            }
        },
        error: function() {
            alert('Ошибка при выполнении запроса');
            btn.prop('disabled', false);
        }
    });
});

function eatApple(id) {
    var btn = $('.apple-eat[data-id="' + id + '"]');
    var percentInput = $('input[data-id="' + id + '"]');
    var percent = parseFloat(percentInput.val());
    var eatenPercent = parseFloat(percentInput.data('eaten')) || 0;
    var remaining = 100 - eatenPercent;
    
    if (!percent || percent <= 0 || percent > 100) {
        alert('Введите корректный процент от 1 до 100');
        return;
    }
    
    if (percent > remaining) {
        alert('Нельзя съесть больше, чем осталось. Осталось: ' + remaining.toFixed(2) + '%');
        return;
    }
    
    btn.prop('disabled', true);
    
    $.ajax({
        url: '{$eatUrl}',
        method: 'POST',
        data: {
            id: id,
            percent: percent,
            _csrf: '{$csrfToken}'
        },
        success: function(response) {
            if (response.success) {
                if (response.deleted) {
                    alert('Яблоко полностью съедено и удалено');
                }
                $.pjax.reload({container: '#apple-grid-pjax'});
            } else {
                alert(response.message);
                btn.prop('disabled', false);
            }
        },
        error: function() {
            alert('Ошибка при выполнении запроса');
            btn.prop('disabled', false);
        }
    });
}

$(document).on('click', '.apple-eat', function() {
    var id = $(this).data('id');
    eatApple(id);
});

$(document).on('keypress', 'input[data-id]', function(e) {
    if (e.which === 13) {
        var id = $(this).data('id');
        eatApple(id);
    }
});

$(document).on('click', '.apple-rotten', function() {
    var btn = $(this);
    var id = btn.data('id');
    
    btn.prop('disabled', true);
    
    $.ajax({
        url: '{$rottenUrl}',
        method: 'POST',
        data: {
            id: id,
            _csrf: '{$csrfToken}'
        },
        success: function(response) {
            if (response.success) {
                $.pjax.reload({container: '#apple-grid-pjax'});
            } else {
                alert(response.message);
                btn.prop('disabled', false);
            }
        },
        error: function() {
            alert('Ошибка при выполнении запроса');
            btn.prop('disabled', false);
        }
    });
});
JS;

$this->registerJs($js);
?>
