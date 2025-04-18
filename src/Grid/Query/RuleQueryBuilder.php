<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\Grid\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use PrestaShop\PrestaShop\Core\Grid\Query\AbstractDoctrineQueryBuilder;
use PrestaShop\PrestaShop\Core\Grid\Query\DoctrineSearchCriteriaApplicatorInterface;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;

class RuleQueryBuilder extends AbstractDoctrineQueryBuilder
{
    /**
     * @var string
     */
    protected $dbPrefix;

    /**
     * @var int
     */
    protected $langId;

    /**
     * @param Connection $connection Database connection
     * @param string $dbPrefix Database prefix
     * @param DoctrineSearchCriteriaApplicatorInterface|null $searchCriteriaApplicator Search criteria applicator
     */
    public function __construct(
        Connection $connection,
        string $dbPrefix,
        ?DoctrineSearchCriteriaApplicatorInterface $searchCriteriaApplicator = null,
    ) {
        parent::__construct($connection, $searchCriteriaApplicator);
        $this->dbPrefix = $dbPrefix;
        $this->langId = (int) \Context::getContext()->language->id;
    }

    /**
     * Builds the search query for rules
     *
     * @param SearchCriteriaInterface|null $searchCriteria Search criteria
     *
     * @return QueryBuilder
     */
    public function getSearchQueryBuilder(?SearchCriteriaInterface $searchCriteria = null): QueryBuilder
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('r.*')
            ->addSelect('fosl.name as from_order_state_name')
            ->addSelect('tosl.name as to_order_state_name')
            ->from($this->dbPrefix . 'evo_statusflow_rule', 'r')
            // Jointure avec les statuts PrestaShop de départ et d'arrivée
            ->leftJoin(
                'r',
                $this->dbPrefix . 'order_state_lang',
                'fosl',
                'r.id_order_state_from = fosl.id_order_state AND fosl.id_lang = :langId'
            )
            ->leftJoin(
                'r',
                $this->dbPrefix . 'order_state_lang',
                'tosl',
                'r.id_order_state_to = tosl.id_order_state AND tosl.id_lang = :langId'
            )
            ->setParameter('langId', $this->langId)
            ->orderBy('r.id_order_state_from', 'ASC')
            ->addOrderBy('r.id_order_state_to', 'ASC');

        if ($searchCriteria) {
            $qb->setFirstResult($searchCriteria->getOffset())
                ->setMaxResults($searchCriteria->getLimit());

            $this->applyFilters($qb, $searchCriteria);
        }

        return $qb;
    }

    /**
     * Builds the count query for rules
     *
     * @param SearchCriteriaInterface|null $searchCriteria Search criteria
     *
     * @return QueryBuilder
     */
    public function getCountQueryBuilder(?SearchCriteriaInterface $searchCriteria = null): QueryBuilder
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('COUNT(r.id_rule)')
            ->from($this->dbPrefix . 'evo_statusflow_rule', 'r');

        if ($searchCriteria) {
            $this->applyFilters($qb, $searchCriteria);
        }

        return $qb;
    }

    /**
     * Applies filters to the query
     *
     * @param QueryBuilder $qb Query builder
     * @param SearchCriteriaInterface $criteria Search criteria
     *
     * @return void
     */
    private function applyFilters(QueryBuilder $qb, SearchCriteriaInterface $criteria): void
    {
        // Implémentation future pour les filtres
        $filters = $criteria->getFilters();

        foreach ($filters as $filterName => $filterValue) {
            switch ($filterName) {
                case 'id_rule':
                case 'delay_hours':
                    $qb->andWhere("r.{$filterName} = :{$filterName}")
                        ->setParameter($filterName, $filterValue);
                    break;

                case 'active':
                case 'auto_execute':
                    $qb->andWhere("r.{$filterName} = :{$filterName}")
                        ->setParameter($filterName, $filterValue);
                    break;

                case 'id_order_state_from':
                    $qb->andWhere('r.id_order_state_from = :id_order_state_from')
                        ->setParameter('id_order_state_from', $filterValue);
                    break;

                case 'id_order_state_to':
                    $qb->andWhere('r.id_order_state_to = :id_order_state_to')
                        ->setParameter('id_order_state_to', $filterValue);
                    break;

                case 'from_order_state_name':
                    $qb->andWhere('fosl.name LIKE :from_order_state_name')
                        ->setParameter('from_order_state_name', '%' . $filterValue . '%');
                    break;

                case 'to_order_state_name':
                    $qb->andWhere('tosl.name LIKE :to_order_state_name')
                        ->setParameter('to_order_state_name', '%' . $filterValue . '%');
                    break;

                default:
                    \PrestaShopLogger::addLog(
                        sprintf('Unknown filter name "%s" in RuleQueryBuilder', $filterName),
                        2,
                        null,
                        'EvoStatusFlow',
                        0,
                        true
                    );
                    break;
            }
        }
    }
}
