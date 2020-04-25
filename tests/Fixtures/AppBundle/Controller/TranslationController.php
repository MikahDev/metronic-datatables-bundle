<?php

/*
 * Symfony DataTables Bundle
 * (c) MikahDev Internetbureau B.V. - https://mikahdev.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Fixtures\AppBundle\Controller;

use MikahDev\DataTablesBundle\Adapter\ArrayAdapter;
use MikahDev\DataTablesBundle\Column\TextColumn;
use MikahDev\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * TranslationController.
 */
class TranslationController extends AbstractController
{
    public function tableAction(Request $request, DataTableFactory $dataTableFactory)
    {
        $datatable = $dataTableFactory->create();
        $datatable
            ->setName('noCDN')
            ->setMethod(Request::METHOD_GET)
            ->setLanguageFromCDN(false)
            ->add('col3', TextColumn::class, ['label' => 'foo', 'field' => 'bar'])
            ->add('col4', TextColumn::class, ['label' => 'bar', 'field' => 'foo'])
            ->createAdapter(ArrayAdapter::class)
        ;

        if ($datatable->handleRequest($request)->isCallback()) {
            return $datatable->getResponse();
        }

        return $this->render('@App/table.html.twig', [
            'datatable' => $datatable,
        ]);
    }
}
