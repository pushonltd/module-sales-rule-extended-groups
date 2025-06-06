<?php
declare(strict_types=1);

namespace PushON\SalesRuleExtendedGroups\Test\Integration\Model;

use Magento\Framework\App\Area;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection;
use Magento\SalesRule\Test\Fixture\Rule as RuleFixture;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Interception\PluginList;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use PushON\SalesRuleExtendedGroups\Plugin\SalesRule\ResourceModel\Rule\Collection\ApplyAllGroupsFilter;
use PushON\SalesRuleExtendedGroups\Test\Integration\Fixture\CustomerGroup;

class RuleCollectionTest extends TestCase
{
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManager;

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->fixtures = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
    }

    #[
        DataFixture(
            CustomerGroup::class,
            ['code' => 'group1', 'is_excluded_website' => true],
            'group1'
        ),
        DataFixture(
            CustomerGroup::class,
            ['code' => 'group2'],
            'group2'
        ),
        DataFixture(
            RuleFixture::class,
            ['discount_amount' => 1, 'extension_attributes' => [], 'customer_group_ids' => [
                '$group1.id$',
                '$group2.id$',
            ]],
            'rule1'
        )
    ]
    /**
     * @depends testPluginConfigured
     */
    public function testReturnsGroupIfNotFilteredByWebsite(): void
    {
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $group2 = $this->fixtures->get('group2');

        // Filtering collection using customer group that is allowed for website
        $collection = $this->objectManager->create(Collection::class);
        $collection->addWebsiteGroupDateFilter(
            $storeManager->getWebsite()->getId(),
            $group2?->getData('group')->getId(),
        );

        self::assertSame(
            1,
            $collection->count(),
            'Collection should be not empty because Group2 is not excluded for website'
        );
        self::assertSame(
            1,
            $collection->getSize(),
            'Collection should be not empty because Group2 is not excluded for website'
        );

        // Filtering collection using customer group that is excluded for website
        $group1Excluded = $this->fixtures->get('group1');
        $collection = $this->objectManager->create(Collection::class);
        $collection->addWebsiteGroupDateFilter(
            $storeManager->getWebsite()->getId(),
            $group1Excluded?->getData('group')->getId(),
        );

        self::assertSame(
            0,
            $collection->count(),
            'Collection should be empty because Group1 is excluded for website'
        );
        self::assertSame(
            0,
            $collection->getSize(),
            'Collection should be empty because Group1 is excluded for website'
        );
    }

    #[
        DataFixture(
            CustomerGroup::class,
            ['code' => 'group1', 'is_excluded_website' => true],
            'group1'
        ),
        DataFixture(
            CustomerGroup::class,
            ['code' => 'group2'],
            'group2'
        ),
        DataFixture(
            RuleFixture::class,
            ['discount_amount' => 1, 'extension_attributes' => ['all_groups' => true], 'customer_group_ids' => [0]],
            'rule1'
        )
    ]
    /**
     * @depends testPluginConfigured
     */
    public function testRuleAllGroupsReturnsAllGroups(): void
    {
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $group2 = $this->fixtures->get('group2');

        // Filtering collection using customer group that is not assigned to the rule
        $collection = $this->objectManager->create(Collection::class);
        $collection->addWebsiteGroupDateFilter(
            $storeManager->getWebsite()->getId(),
            $group2?->getData('group')->getId(),
        );

        self::assertSame(1, $collection->count());
        self::assertSame(1, $collection->getSize());

        // Filtering collection using customer group that is not assigned to the rule and is excluded for website
        $group1Excluded = $this->fixtures->get('group1');
        $collection = $this->objectManager->create(Collection::class);
        $collection->addWebsiteGroupDateFilter(
            $storeManager->getWebsite()->getId(),
            $group1Excluded?->getData('group')->getId(),
        );

        self::assertSame(0, $collection->count());
        self::assertSame(0, $collection->getSize());
    }

    #[AppArea(Area::AREA_GLOBAL)]
    public function testPluginConfigured(): void
    {
        $pluginList = $this->objectManager->get(PluginList::class);

        $list = $pluginList->get(Collection::class, []);

        self::assertArrayHasKey(
            'PushON_SalesRuleExtendedGroups::applyAllGroups',
            $list,
            'Plugin not configured'
        );

        self::assertSame(
            ApplyAllGroupsFilter::class,
            $list['PushON_SalesRuleExtendedGroups::applyAllGroups']['instance'],
            'Plugin should be an instance of ' . ApplyAllGroupsFilter::class
        );
        self::assertInstanceOf(
            ApplyAllGroupsFilter::class,
            $this->objectManager->get(ApplyAllGroupsFilter::class)
        );
    }
}
