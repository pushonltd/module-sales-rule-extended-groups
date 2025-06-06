<?php
declare(strict_types=1);

namespace PushON\SalesRuleExtendedGroups\Test\Integration\Fixture;

use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\GroupFactory;
use Magento\Customer\Model\ResourceModel\Group;
use Magento\Customer\Model\ResourceModel\GroupExcludedWebsite;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Fixture\DataFixtureInterface;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

class CustomerGroup implements RevertibleDataFixtureInterface
{
    public function __construct(
        private readonly GroupInterfaceFactory    $groupFactory,
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly StoreManagerInterface    $storeManager,
        private readonly DataObjectHelper         $dataObjectHelper,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function apply(array $data = []): ?DataObject
    {
        $group = $this->groupFactory->create();

        if (isset($data['is_excluded_website'])) {
            $group->getExtensionAttributes()
                ?->setExcludeWebsiteIds([$this->storeManager->getWebsite()->getId()]);
            unset($data['is_excluded_website']);
        }

        $this->dataObjectHelper->populateWithArray(
            $group,
            $data,
            GroupInterface::class
        );

        $group = $this->groupRepository->save($group);

        return new DataObject([
            'id'    => $group->getId(),
            'group' => $group
        ]);
    }

    /**
     * @inheritDoc
     */
    public function revert(DataObject $data): void
    {
        $this->groupRepository->delete($data->getData('group'));
    }
}
