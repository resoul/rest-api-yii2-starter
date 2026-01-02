<?php
namespace Middleware\Framework\Db\Model;

use yii\db\ActiveRecord as BaseActiveRecord;
use yii\behaviors\TimestampBehavior;
use Ramsey\Uuid\Uuid;

abstract class ActiveRecord extends BaseActiveRecord
{
    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public static function findByUuid(string $uuid): ?static
    {
        return static::findOne(['uuid' => $uuid]);
    }

    public function beforeSave($insert): bool
    {
        if ($insert && $this->hasAttribute('uuid') && empty($this->uuid)) {
            $this->uuid = Uuid::uuid4()->toString();
        }
        return parent::beforeSave($insert);
    }

    public function fields(): array
    {
        $fields = parent::fields();
        unset($fields['created_at'], $fields['updated_at']);
        return $fields;
    }

    public function extraFields(): array
    {
        return [
            'created_at' => fn() => date('c', $this->created_at),
            'updated_at' => fn() => date('c', $this->updated_at),
        ];
    }
}