<?php
/**
 * Created by Yii2 Gii.
 * User: Yarmaliuk Mikhail
 * Date: 12.12.2017
 * Time: 09:21
 */

namespace Kakadu\Yii2Helpers\Logs\Search;

use Kakadu\Yii2Helpers\Logs\Models\Log;
use Kakadu\Yii2Helpers\Traits\DataProviderTrait;
use MP\ExtendedApi\ModelSearchInterface;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

/**
 * Class    ProductSearch
 * @package Kakadu\Yii2Helpers\Logs\Search
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 *
 * LogSearch represents the model behind the search form about Log.
 *
 * @see     Log
 */
class LogSearch extends Log implements ModelSearchInterface
{
    use DataProviderTrait;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules[] = ['id', 'integer'];

        return $rules;
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search(array $params = []): ActiveDataProvider
    {
        $dataProvider = $this->getDataProvider();

        /** @var ActiveQuery $query */
        $query = $dataProvider->query;

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'category_id' => $this->id,
        ]);

        $query->andFilterWhere(['like', 'id', $this->project])
            ->andFilterWhere(['like', 'title', $this->level])
            ->andFilterWhere(['like', 'title', $this->prefix])
            ->andFilterWhere(['like', 'title', $this->message])
            ->andFilterWhere(['like', 'barCode', $this->category]);

        $this->andFilterByTimestamp($query, 'log_time');

        return $dataProvider;
    }

    /**
     * @inheritdoc
     */
    public function getDataProvider(): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query'      => self::find(),
            'pagination' => [
                'defaultPageSize' => 20,
                'pageSizeLimit'   => [
                    0, 20, 50, 100,
                ],
            ],
        ]);
    }
}
