<?php
declare(strict_types=1);

namespace PushON\SalesRuleExtendedGroups\Plugin\SalesRule\Rule;

use Magento\Framework\DataObject;
use Magento\SalesRule\Model\Rule;
use PushON\SalesRuleExtendedGroups\Api\Data\RuleInterface;

class PopulateAllGroupsFromRequest
{
    /**
     * @param Rule    $subject
     * @param Rule    $result
     * @param mixed[] $data
     * @return Rule
     */
    public function afterLoadPost(
        Rule  $subject,
        Rule  $result,
        array $data
    ) {
        if (!array_key_exists('all_groups', $data)) {
            return $result;
        }

        $extensionAttributes = $result->getData('extension_attributes') ?? [];

        $extensionAttributes[RuleInterface::FIELD_ALL_GROUPS] = (bool)$data['all_groups'];
        $result->setData('extension_attributes', $extensionAttributes);

        if ((bool)$data['all_groups']) {
            $result->unsetData(\Magento\SalesRule\Model\Data\Rule::KEY_CUSTOMER_GROUPS);
        }

        return $result;
    }

    /**
     * @param Rule       $subject
     * @param DataObject $dataObject
     * @return DataObject[]
     */
    public function beforeValidateData(
        Rule       $subject,
        DataObject $dataObject,
    ): array {
        if ((bool)$dataObject->getData('all_groups')) {
            $dataObject->unsetData(\Magento\SalesRule\Model\Data\Rule::KEY_CUSTOMER_GROUPS);
        }

        return [$dataObject];
    }
}
