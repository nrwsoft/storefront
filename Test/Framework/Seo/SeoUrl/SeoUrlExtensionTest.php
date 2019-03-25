<?php declare(strict_types=1);

namespace Shopware\Storefront\Test\Framework\Seo\SeoUrl;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Storefront\Framework\Seo\SeoUrl\SeoUrlCollection;
use Shopware\Storefront\Framework\Seo\SeoUrl\SeoUrlEntity;

class SeoUrlExtensionTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->productRepository = $this->getContainer()->get('product.repository');
    }

    public function testInsert(): void
    {
        $seoUrlId1 = Uuid::uuid4()->getHex();
        $seoUrlId2 = Uuid::uuid4()->getHex();

        $id = Uuid::uuid4()->getHex();
        $this->upsertProduct([
            'id' => $id,
            'name' => 'awesome product',
            'extensions' => [
                'seoUrls' => [
                    [
                        'id' => $seoUrlId1,
                        'salesChannelId' => Defaults::SALES_CHANNEL,
                        'pathInfo' => '/detail/' . $id,
                        'seoPathInfo' => 'awesome v2',
                        'isCanonical' => true,
                    ],
                    [
                        'id' => $seoUrlId2,
                        'salesChannelId' => Defaults::SALES_CHANNEL,
                        'pathInfo' => '/detail/' . $id,
                        'seoPathInfo' => 'awesome',
                        'isCanonical' => true,
                    ],
                ],
            ],
        ]);

        $criteria = new Criteria([$id]);
        $criteria->addAssociation('product.seoUrls');

        /** @var ProductEntity $first */
        $first = $this->productRepository->search($criteria, Context::createDefaultContext())->first();

        static::assertNotNull($first);

        /** @var SeoUrlCollection $seoUrls */
        $seoUrls = $first->getExtensionOfType('seoUrls', SeoUrlCollection::class);

        /** @var SeoUrlEntity|null $seoUrl */
        $seoUrl = $seoUrls->filterByProperty('id', $seoUrlId1)->first();
        static::assertNotNull($seoUrl);

        static::assertTrue($seoUrl->getIsModified());
        static::assertTrue($seoUrl->getIsCanonical());
        static::assertFalse($seoUrl->getIsDeleted());

        static::assertEquals('awesome v2', $seoUrl->getSeoPathInfo());
    }

    public function testUpdate(): void
    {
        $seoUrlId = Uuid::uuid4()->getHex();
        $id = Uuid::uuid4()->getHex();
        $this->upsertProduct(['id' => $id, 'name' => 'awesome product']);
        $this->upsertProduct([
            'id' => $id,
            'extensions' => [
                'seoUrls' => [
                    [
                        'id' => $seoUrlId,
                        'salesChannelId' => Defaults::SALES_CHANNEL,
                        'seoPathInfo' => 'awesome',
                        'isCanonical' => true,
                    ],
                ],
            ],
        ]);

        $criteria = new Criteria([$id]);
        $criteria->addAssociation('product.seoUrls');

        /** @var ProductEntity $first */
        $first = $this->productRepository->search($criteria, Context::createDefaultContext())->first();

        static::assertNotNull($first);

        /** @var SeoUrlCollection $seoUrls */
        $seoUrls = $first->getExtensionOfType('seoUrls', SeoUrlCollection::class);

        /** @var SeoUrlEntity|null $seoUrl */
        $seoUrl = $seoUrls->filterByProperty('id', $seoUrlId)->first();
        static::assertNotNull($seoUrl);

        static::assertTrue($seoUrl->getIsModified());
        static::assertTrue($seoUrl->getIsCanonical());
        static::assertFalse($seoUrl->getIsDeleted());

        static::assertEquals('/detail/' . $id, $seoUrl->getPathInfo());
    }

    private function upsertProduct($data): void
    {
        $defaults = [
            'manufacturer' => [
                'id' => Uuid::uuid4()->getHex(),
                'name' => 'amazing brand',
            ],
            'tax' => ['id' => Uuid::uuid4()->getHex(), 'taxRate' => 19, 'name' => 'tax'],
            'price' => ['gross' => 10, 'net' => 12, 'linked' => false],
            'stock' => 0,
        ];
        $data = array_merge($defaults, $data);
        $this->productRepository->upsert([$data], Context::createDefaultContext());
    }
}
