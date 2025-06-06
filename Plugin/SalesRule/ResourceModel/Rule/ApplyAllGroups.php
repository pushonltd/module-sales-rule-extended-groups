<?php
declare(strict_types=1);

namespace PushON\SalesRuleExtendedGroups\Plugin\SalesRule\ResourceModel\Rule;

use Magento\Framework\Model\AbstractModel;
use Magento\SalesRule\Model\ResourceModel\Rule;
use PushON\SalesRuleExtendedGroups\Api\Data\RuleInterface;

class ApplyAllGroups
{
    /**
     * @param Rule          $subject
     * @param AbstractModel $object
     * @return AbstractModel[]|null
     */
    public function beforeSave(
        Rule          $subject,
        AbstractModel $object
    ): ?array {
        if (!method_exists($object, 'setData')) {
            return null;
        }

        $extension =
            method_exists($object, 'getExtensionAttributes')
            ? $object->getExtensionAttributes()
            : $object->getData('extension_attributes');
        if (!$extension) {
            return null;
        }

        if (is_array($extension)) {
            $object->setData(
                RuleInterface::FIELD_ALL_GROUPS,
                (int)($extension[RuleInterface::FIELD_ALL_GROUPS] ?? false)
            );
        }

        if ($extension instanceof \Magento\SalesRule\Api\Data\RuleExtensionInterface) {
            $object->setData(
                RuleInterface::FIELD_ALL_GROUPS,
                (int)$extension->getAllGroups()
            );
        }

        return [$object];
    }

    public function afterLoad(
        Rule          $subject,
        Rule          $result,
        AbstractModel $object
    ): Rule {
        $extension = $object->getData('extension_attributes') ?? [];
        if (is_array($extension)) {
            $extension['all_groups'] = (bool)$object->getData(RuleInterface::FIELD_ALL_GROUPS);
            $object->setData('extension_attributes', $extension);
        }

        return $result;
    }
}
