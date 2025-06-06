<?php
declare(strict_types=1);

namespace PushON\SalesRuleExtendedGroups\Test\Integration\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Model\ResourceModel\Rule;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class RuleTest extends TestCase
{
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    #[DbIsolation(true)]
    public function testAllGroupsSavedViaRepository(): void
    {
        $rule = $this->objectManager->create(RuleInterface::class);
        $rule
            ->setName('group1')
            ->setIsActive(true);

        $rule
            ->getExtensionAttributes()
            ->setAllGroups(true);

        $repository = $this->objectManager->create(RuleRepositoryInterface::class);
        $rule = $repository->save($rule);

        $ruleResource = $this->objectManager->get(Rule::class);

        // Reloading model
        $model = $this->objectManager->create(\Magento\SalesRule\Model\Rule::class);
        $ruleResource->load($model, $rule->getRuleId());

        self::assertEquals(1, $model->getData('all_groups'));
    }

    #[DbIsolation(true)]
    public function testAllGroupsLoadedViaRepository(): void
    {
        $rule = $this->objectManager->create(RuleInterface::class);
        $rule
            ->setName('rule1')
            ->setIsActive(true);

        $rule
            ->getExtensionAttributes()
            ->setAllGroups(true);

        $repository = $this->objectManager->create(RuleRepositoryInterface::class);
        $rule = $repository->save($rule);

        $rule2 = $this->objectManager->create(RuleInterface::class);
        $rule2
            ->setName('rule2')
            ->setIsActive(true);
        $repository->save($rule2);

        // Reloading single model via repository
        $repository = $this->objectManager->create(RuleRepositoryInterface::class);
        $rule = $repository->getById((int)$rule->getRuleId());

        self::assertTrue($rule->getExtensionAttributes()->getAllGroups());

        // Reloading using getList
        $repository = $this->objectManager->create(RuleRepositoryInterface::class);
        $searchCriteria = $this->objectManager->create(SearchCriteriaBuilder::class)->create();
        $map = [];
        $mapExpected = [
            'rule1' => true,
            'rule2' => false,
        ];

        foreach ($repository->getList($searchCriteria)->getItems() as $item) {
            $map[$item->getName()] = $item->getExtensionAttributes()->getAllGroups();
        }

        self::assertSame($mapExpected, $map);
    }

    #[DbIsolation(true)]
    public function testAllGroupsSavedViaResourceModel(): void
    {
        $rule = $this->objectManager->create(\Magento\SalesRule\Model\Rule::class);
        $rule
            ->setName('group1')
            ->setIsActive(1);

        $rule->setExtensionAttributes(['all_groups' => true]);

        $ruleResource = $this->objectManager->get(Rule::class);
        $ruleResource->save($rule);

        // Reloading model
        $model = $this->objectManager->create(\Magento\SalesRule\Model\Rule::class);
        $ruleResource->load($model, $rule->getRuleId());

        self::assertEquals(1, $model->getData('all_groups'));
    }
}
