# yii2-tools

AutoHandlerBehavior
-------------------
Define event handler in self class.

```php
class User extends ActiveRecord
{
    public function onBeforeSave($event)
    {
        // execute at event beforeSave
        // do someting
    }

    public function behaviors()
    {
        return [
            'dee\tools\AutoHandlerBehavior',
        ];
    }
}
```

State
-----
Save information of client(browser).
```php
// config
'components' => [
    ...
    'profile' => 'dee\tools\State',
]
```
## Usage
```php
// this information is unique per client.
Yii::$app->profile->address = 'Jl. Buntu No 3426 Lamongan';

echo Yii::$app->profile->address;
```