<?php

use backend\models\Apple;
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
            'id',
            'color',
            [
                'attribute' => 'appeared_at',
                'format' => 'datetime',
                'filter' => false,
            ],
            [
                'attribute' => 'fell_at',
                'format' => 'datetime',
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
                                'class' => 'form-control form-control-sm d-inline-block',
                                'style' => 'width: 80px; margin-left: 5px;',
                                'placeholder' => '%',
                                'data-id' => $model->id,
                            ]);
                            $html .= Html::button('Съесть', [
                                'class' => 'btn btn-success btn-sm apple-eat',
                                'data-id' => $model->id,
                                'style' => 'margin-left: 5px;',
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

$(document).on('click', '.apple-eat', function() {
    var btn = $(this);
    var id = btn.data('id');
    var percentInput = $('input[data-id="' + id + '"]');
    var percent = parseFloat(percentInput.val());
    
    if (!percent || percent <= 0 || percent > 100) {
        alert('Введите корректный процент от 1 до 100');
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
});
JS;

$this->registerJs($js);
?>
