<?php

/*
 * Symfony DataTables Bundle
 * (c) MikahDev Internetbureau B.V. - https://mikahdev.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MikahDev\DataTablesBundle\Adapter\Doctrine\ORM;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use MikahDev\DataTablesBundle\Column\AbstractColumn;
use MikahDev\DataTablesBundle\DataTableState;

/**
 * SearchCriteriaProvider.
 *
 * @author Niels Keurentjes <niels.keurentjes@mikahdev.com>
 */
class SearchCriteriaProvider implements QueryBuilderProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(QueryBuilder $queryBuilder, DataTableState $state)
    {
        $this->processSearchColumns($queryBuilder, $state);
        $this->processGlobalSearch($queryBuilder, $state);
    }

    private function processSearchColumns(QueryBuilder $queryBuilder, DataTableState $state)
    {
        foreach ($state->getSearchColumns() as $searchInfo) {
            /** @var AbstractColumn $column */
            $column = $searchInfo['column'];
            $search = $searchInfo['search'];

            if ('' !== trim($search) && null !== ($filter = $column->getFilter())) {
                $search = $queryBuilder->expr()->literal($search);
                $queryBuilder->andWhere(new Comparison($column->getField(), $filter->getOperator(), $search));
            }
        }
    }

    private function processGlobalSearch(QueryBuilder $queryBuilder, DataTableState $state)
    {
        if (!empty($globalSearch = $state->getGlobalSearch())) {
            $expr = $queryBuilder->expr();
            $comparisons = $expr->orX();
            foreach ($state->getDataTable()->getColumns() as $column) {
                if ($column->isGlobalSearchable() && !empty($column->getField()) && $column->isValidForSearch($globalSearch)) {
                    $comparisons->add(new Comparison($column->getLeftExpr(), $column->getOperator(),
                        $expr->literal($column->getRightExpr($globalSearch))));
                }
            }
            $queryBuilder->andWhere($comparisons);
        }
    }
}
