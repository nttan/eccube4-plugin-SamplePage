<?php

namespace Plugin\SamplePage\Controller;

use Eccube\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;

class SampleController extends AbstractController
{
    /**
     * @Route("sample", name="sample")
     * @Template("@SamplePage/default/index.twig")
     */
    public function index()
    {
        return [];
    }
}
