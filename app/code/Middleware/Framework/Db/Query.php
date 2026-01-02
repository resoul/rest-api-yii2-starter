<?php
namespace Middleware\Framework\Db;

use yii\db\Query as BaseQuery;
use yii\db\Expression;

/**
 * Extended Query Builder with additional convenience methods
 *
 * Usage:
 * ```php
 * $query = new Query();
 * $users = $query->from('users')
 *     ->whereLike('name', 'John')
 *     ->whereIn('status', [1, 2])
 *     ->whereBetween('created_at', $start, $end)
 *     ->latest('created_at')
 *     ->paginate(1, 20);
 * ```
 */
class Query extends BaseQuery
{
    public function whereLike(string $column, string $value, bool $caseSensitive = false): static
    {
        $operator = $caseSensitive ? 'like binary' : 'like';
        return $this->andWhere([$operator, $column, $value]);
    }

    /**
     * Add OR LIKE condition
     *
     * @param string $column
     * @param string $value
     * @param bool $caseSensitive
     * @return static
     */
    public function orWhereLike(string $column, string $value, bool $caseSensitive = false): static
    {
        $operator = $caseSensitive ? 'like binary' : 'like';
        return $this->orWhere([$operator, $column, $value]);
    }

    /**
     * Add NOT LIKE condition
     *
     * @param string $column
     * @param string $value
     * @return static
     */
    public function whereNotLike(string $column, string $value): static
    {
        return $this->andWhere(['not like', $column, $value]);
    }

    /**
     * Add IN condition
     *
     * @param string $column
     * @param array $values
     * @return static
     */
    public function whereIn(string $column, array $values): static
    {
        if (empty($values)) {
            return $this->andWhere('1=0'); // Always false
        }
        return $this->andWhere(['in', $column, $values]);
    }

    /**
     * Add NOT IN condition
     *
     * @param string $column
     * @param array $values
     * @return static
     */
    public function whereNotIn(string $column, array $values): static
    {
        if (empty($values)) {
            return $this; // No effect
        }
        return $this->andWhere(['not in', $column, $values]);
    }

    /**
     * Add BETWEEN condition
     *
     * @param string $column
     * @param mixed $from
     * @param mixed $to
     * @return static
     */
    public function whereBetween(string $column, $from, $to): static
    {
        return $this->andWhere(['between', $column, $from, $to]);
    }

    /**
     * Add NOT BETWEEN condition
     *
     * @param string $column
     * @param mixed $from
     * @param mixed $to
     * @return static
     */
    public function whereNotBetween(string $column, $from, $to): static
    {
        return $this->andWhere(['not between', $column, $from, $to]);
    }

    /**
     * Add IS NULL condition
     *
     * @param string $column
     * @return static
     */
    public function whereNull(string $column): static
    {
        return $this->andWhere([$column => null]);
    }

    /**
     * Add IS NOT NULL condition
     *
     * @param string $column
     * @return static
     */
    public function whereNotNull(string $column): static
    {
        return $this->andWhere(['not', [$column => null]]);
    }

    /**
     * Add date comparison condition
     *
     * @param string $column
     * @param string $operator
     * @param string $date
     * @return static
     */
    public function whereDate(string $column, string $operator, string $date): static
    {
        return $this->andWhere([$operator, new Expression("DATE($column)"), $date]);
    }

    /**
     * Add year comparison condition
     *
     * @param string $column
     * @param int $year
     * @return static
     */
    public function whereYear(string $column, int $year): static
    {
        return $this->andWhere(['=', new Expression("YEAR($column)"), $year]);
    }

    /**
     * Add month comparison condition
     *
     * @param string $column
     * @param int $month
     * @return static
     */
    public function whereMonth(string $column, int $month): static
    {
        return $this->andWhere(['=', new Expression("MONTH($column)"), $month]);
    }

    /**
     * Add day comparison condition
     *
     * @param string $column
     * @param int $day
     * @return static
     */
    public function whereDay(string $column, int $day): static
    {
        return $this->andWhere(['=', new Expression("DAY($column)"), $day]);
    }

    /**
     * Order by column descending (latest first)
     *
     * @param string $column
     * @return static
     */
    public function latest(string $column = 'created_at'): static
    {
        return $this->orderBy([$column => SORT_DESC]);
    }

    /**
     * Order by column ascending (oldest first)
     *
     * @param string $column
     * @return static
     */
    public function oldest(string $column = 'created_at'): static
    {
        return $this->orderBy([$column => SORT_ASC]);
    }

    /**
     * Randomly order results
     *
     * @return static
     */
    public function inRandomOrder(): static
    {
        return $this->orderBy(new Expression('RAND()'));
    }

    /**
     * Add JSON contains condition
     *
     * @param string $column
     * @param mixed $value
     * @return static
     */
    public function whereJsonContains(string $column, $value): static
    {
        $json = json_encode($value);
        return $this->andWhere(new Expression("JSON_CONTAINS($column, :value)", [':value' => $json]));
    }

    /**
     * Add JSON length condition
     *
     * @param string $column
     * @param string $operator
     * @param int $length
     * @return static
     */
    public function whereJsonLength(string $column, string $operator, int $length): static
    {
        return $this->andWhere([$operator, new Expression("JSON_LENGTH($column)"), $length]);
    }

    /**
     * Get first result or null
     *
     * @return array|null
     */
    public function first(): ?array
    {
        return $this->limit(1)->one();
    }

    /**
     * Get first result or throw exception
     *
     * @return array
     * @throws \yii\web\NotFoundHttpException
     */
    public function firstOrFail(): array
    {
        $result = $this->first();
        if ($result === null) {
            throw new \yii\web\NotFoundHttpException('Record not found');
        }
        return $result;
    }

    public function paginate(int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 100));

        $total = $this->count();
        $offset = ($page - 1) * $perPage;

        $items = $this->limit($perPage)->offset($offset)->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
            'has_more' => ($page * $perPage) < $total,
        ];
    }

    /**
     * Get chunk of results and process with callback
     *
     * @param int $size
     * @param callable $callback
     * @return bool
     */
    public function chunk(int $size, callable $callback): bool
    {
        $page = 1;

        do {
            $results = $this->paginate($page, $size);

            if (empty($results['items'])) {
                break;
            }

            if ($callback($results['items'], $page) === false) {
                return false;
            }

            $page++;
        } while ($results['has_more']);

        return true;
    }

    /**
     * Get only specified columns
     *
     * @param array|string $columns
     * @return static
     */
    public function only($columns): static
    {
        if (!is_array($columns)) {
            $columns = func_get_args();
        }
        return $this->select($columns);
    }

    /**
     * Add full-text search condition
     *
     * @param array|string $columns
     * @param string $query
     * @param string $mode 'natural' or 'boolean'
     * @return static
     */
    public function whereFullText($columns, string $query, string $mode = 'natural'): static
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        $columnsStr = implode(',', $columns);
        $modeStr = $mode === 'boolean' ? 'IN BOOLEAN MODE' : 'IN NATURAL LANGUAGE MODE';

        return $this->andWhere(
            new Expression("MATCH($columnsStr) AGAINST(:query $modeStr)", [':query' => $query])
        );
    }
}