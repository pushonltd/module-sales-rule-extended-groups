<?php
declare(strict_types=1);

namespace PushON\SalesRuleExtendedGroups\Plugin\SalesRule\ResourceModel\Rule\Collection;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection;
use Magento\SalesRule\Model\ResourceModel\Rule\DateApplier;
use PushON\SalesRuleExtendedGroups\Api\Data\RuleInterface;

class ApplyAllGroupsFilter
{
    /**
     * @var mixed[]
     */
    private array $associatedEntitiesMap;

    public function __construct(
        DataObject                         $associatedEntityMap,
        private readonly DateApplier       $dateApplier,
        private readonly TimezoneInterface $date,
    ) {
        $this->associatedEntitiesMap = $associatedEntityMap->getData();
    }

    /**
     * Filters the collection of SalesRules to include rules which have flag all_groups = 1
     *
     * The method overrides/copies the filter applier from the module Magento_SalesRule,
     *  and re-implements filter by customer group:
     *  - (original behavior) Returns SalesRule if it's assigned to provided CustomerGroup and CustomerGroup
     *          is not excluded in provided Website
     *  - (new behavior) Returns SalesRule if it has flag all_groups = 1
     *
     * @param Collection  $subject
     * @param \Closure    $proceed
     * @param int         $websiteId
     * @param int         $customerGroupId
     * @param string|null $now
     * @return Collection
     * @throws LocalizedException
     * @see Collection::addWebsiteGroupDateFilter
     */
    public function aroundAddWebsiteGroupDateFilter(
        Collection $subject,
        \Closure   $proceed,
        $websiteId,
        $customerGroupId,
        $now = null
    ) {
        if (!$subject->getFlag('website_group_date_filter')) {
            if ($now === null) {
                $now = $this->date->date()->format('Y-m-d');
            }

            $subject->addWebsiteFilter($websiteId);
            $entityInfo = $this->_getAssociatedEntityInfo('customer_group');
            $connection = $subject->getConnection();

            // Subquery: Groups which are directly assigned to the rule, and excluded by website, should be filtered out
            $selectLinkedGroupsIncluded = $connection->select()
                ->from(['customer_group_ids' => $subject->getTable($entityInfo['associations_table'])], [])
                ->columns('COUNT(*)')
                ->joinLeft(
                    ['cgw' => $subject->getTable('customer_group_excluded_website')],
                    $connection->quoteInto(
                        'customer_group_ids.' . $entityInfo['entity_id_field'] .
                            ' = cgw.' . $entityInfo['entity_id_field']
                        . ' AND ? = cgw.website_id',
                        $websiteId
                    ),
                    []
                )
                ->where(
                    'customer_group_ids.' . $entityInfo['rule_id_field'] .
                    ' = main_table.' . $entityInfo['rule_id_field']
                )
                ->where(
                    'customer_group_ids.' . $entityInfo['entity_id_field'] . ' = ?',
                    (int)$customerGroupId,
                    \Zend_Db::INT_TYPE
                )
                ->where('cgw.website_id IS NULL');

            // Subquery: Groups, excluded by website, should be filtered out
            //      Use case: filtering out Rules, which have "all_groups = 1"
            $selectAllGroupsExcluded = $connection->select()
                ->from(['cgw' => $subject->getTable('customer_group_excluded_website')], [])
                ->columns('COUNT(*)')
                ->where('cgw.website_id = ?', $websiteId, \Zend_Db::INT_TYPE)
                ->where('cgw.customer_group_id = ?', $customerGroupId, \Zend_Db::INT_TYPE);

            $subject->getSelect()
                ->where(
                    '(((' . $selectLinkedGroupsIncluded . ') > 0) ' .
                    ' OR (main_table.all_groups = 1 AND (' . $selectAllGroupsExcluded . ') = 0))'
                );

            $this->dateApplier->applyDate($subject->getSelect(), $now);

            $subject->addIsActiveFilter();

            $subject->setFlag('website_group_date_filter', true);
        }

        return $subject;
    }

    /**
     * @param string $entityType
     * @return mixed[]
     * @throws LocalizedException
     */
    protected function _getAssociatedEntityInfo(string $entityType): array
    {
        if (isset($this->associatedEntitiesMap[$entityType])) {
            return $this->associatedEntitiesMap[$entityType];
        }

        throw new LocalizedException(
            __('There is no information about associated entity type "%1".', $entityType)
        );
    }

    public function afterLoad(
        Collection $subject,
        Collection $result,
    ): Collection {
        if ($subject->isLoaded()) {
            return $result;
        }

        foreach ($result->getItems() as $item) {
            $extension = $item->getData('extension_attributes') ?? [];
            if (is_array($extension)) {
                $extension['all_groups'] = (bool)$item->getData(RuleInterface::FIELD_ALL_GROUPS);
                $item->setData('extension_attributes', $extension);
            }

        }
        return $result;
    }
}
