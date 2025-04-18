<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\Grid\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use PrestaShop\PrestaShop\Core\Grid\Query\AbstractDoctrineQueryBuilder;
use PrestaShop\PrestaShop\Core\Grid\Query\DoctrineSearchCriteriaApplicatorInterface;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;

class LogQueryBuilder extends AbstractDoctrineQueryBuilder
{
    /**
     * Database table prefix
     */
    protected $dbPrefix;

    /**
     * @param Connection $connection
     * @param string $dbPrefix
     * @param DoctrineSearchCriteriaApplicatorInterface|null $searchCriteriaApplicator
     */
    public function __construct(
        Connection $connection,
        string $dbPrefix,
        ?DoctrineSearchCriteriaApplicatorInterface $searchCriteriaApplicator = null,
    ) {
        parent::__construct($connection, $searchCriteriaApplicator);
        $this->dbPrefix = $dbPrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchQueryBuilder(?SearchCriteriaInterface $searchCriteria = null): QueryBuilder
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('l.*')
            ->from($this->dbPrefix . 'evo_statusflow_log', 'l');

        if ($searchCriteria !== null) {
            // Add search criteria for pagination and ordering
            $this->applyFilters($qb, $searchCriteria);

            if ($searchCriteria->getOrderBy()) {
                $qb->orderBy(
                    'l.' . $searchCriteria->getOrderBy(),
                    $searchCriteria->getOrderWay()
                );
            } else {
                // Default ordering by date, newest first
                $qb->orderBy('l.date_add', 'DESC');
            }

            // Add pagination
            if ($searchCriteria->getLimit() !== null) {
                $qb->setMaxResults($searchCriteria->getLimit());
            }

            if ($searchCriteria->getOffset() !== null) {
                $qb->setFirstResult($searchCriteria->getOffset());
            }
        } else {
            // Default ordering when no search criteria provided
            $qb->orderBy('l.date_add', 'DESC');
        }

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function getCountQueryBuilder(?SearchCriteriaInterface $searchCriteria = null): QueryBuilder
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('COUNT(l.id_log)')
            ->from($this->dbPrefix . 'evo_statusflow_log', 'l');

        if ($searchCriteria !== null) {
            $this->applyFilters($qb, $searchCriteria);
        }

        return $qb;
    }

    /**
     * Apply filters to query builder
     *
     * @param QueryBuilder $qb
     * @param SearchCriteriaInterface $criteria
     */
    private function applyFilters(QueryBuilder $qb, SearchCriteriaInterface $criteria): void
    {
        $filters = $criteria->getFilters();

        foreach ($filters as $filterName => $filterValue) {
            switch ($filterName) {
                case 'id_log':
                case 'object_id':
                case 'id_rule':
                    if (is_numeric($filterValue)) {
                        $qb->andWhere("l.{$filterName} = :{$filterName}")
                            ->setParameter($filterName, (int) $filterValue);
                    }
                    break;

                case 'log_type':
                case 'object_type':
                    $qb->andWhere("l.{$filterName} = :{$filterName}")
                        ->setParameter($filterName, $filterValue);
                    break;

                case 'log_message':
                    $qb->andWhere("l.{$filterName} LIKE :{$filterName}")
                        ->setParameter($filterName, '%' . $filterValue . '%');
                    break;

                case 'date_add':
                    if (isset($filterValue['from'])) {
                        $qb->andWhere('l.date_add >= :date_from')
                            ->setParameter('date_from', $filterValue['from']);
                    }

                    if (isset($filterValue['to'])) {
                        $qb->andWhere('l.date_add <= :date_to')
                            ->setParameter('date_to', $filterValue['to']);
                    }
                    break;
            }
        }
    }
}
