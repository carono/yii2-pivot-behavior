`PivotSaveBehavior` is a Yii2 `Behavior` intended to simplify saving many-to-many relations by collecting “pivot targets” into an internal buffer and writing pivot rows automatically after the owner ActiveRecord is inserted/updated.[1]

## What it does
- Intercepts assignment to a configured attribute (via `__set`) and treats the assigned value as a list of related models/IDs to be linked through a pivot table.[1]
- Converts assigned values into an internal array of models (`$_pivots`), optionally preprocessing input via `prepareValues` closure.[1]
- On owner save, it either calls a custom `savePivots` closure or subscribes to `EVENT_AFTER_INSERT` / `EVENT_AFTER_UPDATE` and then persists links.[1]

## Requirements
- The owner model must provide `addPivot()` and `deletePivots()` methods (recommended via `carono\yii2pivot\PivotTrait`), otherwise the behavior throws an exception during saving.[2][1]
- The behavior needs `pivotClass` (pivot ActiveRecord class name) and `modelClass` (related model class name) to resolve and save links.[1]

## Key properties (from code)
- `attribute`: virtual attribute name used to accept pivot input; writing to it triggers pivot buffering.[1]
- `modelClass`: class of the related models being linked (used to `findOne()` numeric IDs).[1]
- `pivotClass`: pivot AR class that represents the join table between owner and related models.[1]
- `deletePivotsBeforeSave` (bool): when enabled, clears existing pivot links for this owner before adding new ones.[1]
- `inverseInsertPivot` (bool): if enabled, calls `addPivot` on the related model instead of the owner (useful when the “direction” matters).[1]
- `prepareValues` (Closure|null): preprocesses incoming values before they’re resolved into models. [1]
- `savePivots` (Closure|null): fully custom save handler (bypasses default event binding + default saving). [1]

## How saving works
- When you assign values, the behavior stores related models in `$_pivots` (numbers are treated as PKs and resolved via `modelClass::findOne($id)`).[1]
- After insert/update, `savePivots()` deletes old pivot rows (if `deletePivotsBeforeSave=true`) and then creates new pivot links using `addPivot()` for each stored model.[2][1]

## Usage examples
### 1) Attach to an owner ActiveRecord
```php
use yii\db\ActiveRecord;
use carono\yii2pivot\PivotTrait;

class Company extends ActiveRecord
{
    use PivotTrait;

    public function behaviors(): array
    {
        return [
            'directorsPivot' => [
                'class' => PivotSaveBehavior::class,
                'attribute' => 'directors',              // virtual attribute
                'modelClass' => User::class,             // related model
                'pivotClass' => PvCompanyDirector::class, // pivot AR
                'deletePivotsBeforeSave' => true,
            ],
        ];
    }
}
```
This matches the behavior’s expectation that the owner can `addPivot()` / `deletePivots()` (e.g., via `PivotTrait`).[2][1]

### 2) Save pivot links by assigning IDs
```php
$company = Company::findOne(1);
$company->directors = [10, 12, 15]; // user IDs
$company->save(); // afterUpdate triggers pivot rewrite
```
Numeric values are resolved using `modelClass::findOne()` and then linked through the configured `pivotClass`.[1]

### 3) Preprocess input (e.g., CSV -> array)
```php
'prepareValues' => function (array $values) {
    // accept "1,2,3" or [1,2,3]
    $v = $values[0] ?? $values;
    return is_string($v) ? preg_split('~\s*,\s*~', trim($v), -1, PREG_SPLIT_NO_EMPTY) : $values;
},
```
The closure is applied before the behavior converts values into model instances.[1]

If you share the pivot AR class (e.g., `PvCompanyDirector`) and an example owner model, a more exact README-style description (including relation definitions and pivot attributes) can be generated.

[1](https://ppl-ai-file-upload.s3.amazonaws.com/web/direct-files/attachments/151648782/b9bc9fc6-9ad3-4868-8154-9b09111927e5/PivotSaveBehavior.php)
[2](https://www.yiiframework.com/extension/carono/yii2-migrate)
[3](https://forum.yiiframework.com/t/yii2-gridview-to-display-pivot-table-data/128633)
[4](https://stackoverflow.com/questions/51067512/how-to-prevent-save-from-a-behavior-in-yii2)
[5](https://www.yiiframework.com/extension/la-haute-societe/yii2-save-relations-behavior)
[6](https://framework579.rssing.com/chan-6060844/all_p102.html)
[7](https://www.yiiframework.com/doc/api/2.0/yii-behaviors-attributesbehavior)
[8](https://learn.microsoft.com/en-us/answers/questions/5182767/pivot-tables-how-to-remove-items-from-pivot-table)
[9](https://www.yiiframework.com/doc/guide/2.0/en/concept-behaviors)
[10](https://www.yiiframework.com/doc/api/2.0/yii-behaviors-attributebehavior)
[11](https://www.reddit.com/r/CompetitiveTFT/comments/1c9wxkg/is_it_ever_correct_to_pivot_off_of_hero_trait/)
[12](https://www.yiiframework.com/doc/guide/2.0/ru/db-active-record)
[13](https://www.yiiframework.com/doc/api/2.0/yii-behaviors-attributetypecastbehavior)
[14](https://www.youtube.com/watch?v=ShlArTuHKQ8)
[15](https://www.yiiframework.com/extension/save-relations-ar-behavior)
[16](https://github.com/yiisoft/yii2/blob/master/framework/behaviors/AttributeBehavior.php)
[17](https://brasil.mapbiomas.org/wp-content/uploads/sites/4/2023/08/Irrigation-Appendix-C8.docx.pdf)
[18](https://3790458.fs1.hubspotusercontent-na1.net/hubfs/3790458/2025Udemy-Business-Course.pdf)
[19](http://stuff.cebe.cc/yii2docs/yii-behaviors-attributebehavior.html)
[20](https://www.instagram.com/reel/DQ9Ddl1EinD/)
[21](https://www.yiiframework.com/doc/guide/2.0/ru/concept-behaviors)