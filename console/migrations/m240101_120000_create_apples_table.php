<?php

use yii\db\Migration;

class m240101_120000_create_apple_table extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%apple}}', [
            'id' => $this->primaryKey(),
            'color' => $this->string(50)->notNull(),
            'appeared_at' => $this->integer()->notNull(),
            'fell_at' => $this->integer()->null(),
            'status' => $this->smallInteger()->notNull()->defaultValue(1),
            'eaten_percent' => $this->decimal(5, 2)->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx_apple_status', '{{%apple}}', 'status');
        $this->createIndex('idx_apple_fell_at', '{{%apple}}', 'fell_at');
    }

    public function down()
    {
        $this->dropTable('{{%apple}}');
    }
}

