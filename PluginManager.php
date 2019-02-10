<?php
namespace Plugin\SamplePage;

use Eccube\Entity\PageLayout;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Repository\LayoutRepository;
use Eccube\Repository\PageLayoutRepository;
use Eccube\Repository\PageRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginManager extends AbstractPluginManager
{
    const ADD_PAGE             = ["url" => "sample"];
    const ADD_PAGE_NAME        = "サンプルページ";
    const ADD_PAGE_URL         = "sample";
    const ADD_PAGE_FILE_NAME   = "SamplePage/Resource/template/default/index";
    const ADD_PAGE_META_ROBOTS = "noindex";
    const ADD_PAGE_EDIT_TYPE   = 2;

    /**
     * プラグイン有効化時に走る
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function enable(array $meta, ContainerInterface $container) {
        $this->createPage($container);
    }

    /**
     * プラグイン無効化時・アンインストール時に走る
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function disable(array $meta, ContainerInterface $container) {
        $this->deletePage($container);
    }

    /**
     * ページ情報を挿入する dtb_page, dtb_page_layout
     * @param ContainerInterface $container
     */
    private function createPage(ContainerInterface $container) {
        // dtb_page に存在しないことを確認する
        $pageRepository = $container->get(PageRepository::class);
        $pageFindResult = $pageRepository->findOneBy($this::ADD_PAGE);
        if (is_null($pageFindResult) == false) return;

        // dtb_layout から下層ページ用レイアウトを取得する
        $layoutRepository = $container->get(LayoutRepository::class);
        $underLayout = $layoutRepository->findOneBy(["id" => 2]);

        // dtb_page_layout の次のSortNoを取得する
        $pageLayoutRepository = $container->get(PageLayoutRepository::class);
        $LastPageLayout = $pageLayoutRepository->findOneBy([], ['sort_no' => 'DESC']);
        $nextSortNo = $LastPageLayout->getSortNo() + 1;

        // EntityManager準備
        $em = $container->get('doctrine.orm.entity_manager');
        $em->beginTransaction();

        // INSERT INTO dtb_page
        $page = $pageRepository->newPage();
        $page->setName($this::ADD_PAGE_NAME)
            ->setUrl($this::ADD_PAGE_URL)
            ->setFileName($this::ADD_PAGE_FILE_NAME)
            ->setEditType($this::ADD_PAGE_EDIT_TYPE)
            ->setMetaRobots($this::ADD_PAGE_META_ROBOTS);
        $em->persist($page);
        $em->flush($page);

        // INSERT INTO dtb_page_layout
        $pageLayout = new PageLayout();
        $pageLayout->setLayout($underLayout)
            ->setLayoutId($underLayout->getId())
            ->setPageId($page->getId())
            ->setSortNo($nextSortNo)
            ->setPage($page);
        $em->persist($pageLayout);
        $em->flush($pageLayout);
        $em->commit();
    }

    /**
     * ページ情報を削除 dtb_page, dtb_page_layout
     * @param ContainerInterface $container
     */
    private function deletePage(ContainerInterface $container) {
        // dtb_page に存在することを確認する
        $pageRepository = $container->get(PageRepository::class);
        $page = $pageRepository->findOneBy($this::ADD_PAGE);
        if (is_null($page)) return;

        // EntityManager準備
        $em = $container->get('doctrine.orm.entity_manager');
        $em->beginTransaction();

        // DELETE FROM dtb_page WHERE インストール時にINSERTしたページ
        $em->remove($page);
        $em->flush($page);

        // DELETE FROM dtb_page_layout WHERE インストール時にINSERTしたページレイアウト
        $pageLayoutRepository = $container->get(PageLayoutRepository::class);
        $pageLayout = $pageLayoutRepository->findOneBy(["page_id" => $page->getId()]);
        if(is_null($pageLayout) === false){
            $em->remove($pageLayout);
            $em->flush($pageLayout);
        }
        $em->commit();
    }
}