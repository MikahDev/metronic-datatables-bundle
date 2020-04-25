<?php

/*
 * Symfony DataTables Bundle
 * (c) MikahDev Internetbureau B.V. - https://mikahdev.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Unit\Adapter;

use Elastica\Response;
use Elastica\Transport\AbstractTransport;
use MikahDev\DataTablesBundle\Adapter\Elasticsearch\ElasticaAdapter;
use MikahDev\DataTablesBundle\Column\TextColumn;
use MikahDev\DataTablesBundle\DataTable;
use MikahDev\DataTablesBundle\DataTableState;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * ElasticaTest.
 *
 * @author Niels Keurentjes <niels.keurentjes@mikahdev.com>
 */
class ElasticaTest extends TestCase
{
    public function testElasticaAdapter()
    {
        // Set up expectations
        $transport = $this->getMockBuilder(AbstractTransport::class)
            ->setMethods(['exec'])
            ->getMock();
        $transport
            ->expects($this->exactly(2))
            ->method('exec')
            ->withConsecutive(
                [
                    $this->callback(function (\Elastica\Request $request) {
                        $this->assertSame('test-*/_search', $request->getPath());
                        $this->assertSame('GET', $request->getMethod());
                        $this->assertSame('{"query":{"multi_match":{"query":"foo","fields":["foo"]}},"from":20,"size":40,"sort":[{"bar":{"order":"desc"}}]}', json_encode($request->getData()));

                        return true;
                    }),
                ],
                [
                    $this->callback(function (\Elastica\Request $request) {
                        $this->assertSame('test-*/_search', $request->getPath());
                        $this->assertSame('GET', $request->getMethod());
                        $this->assertSame('{"query":{"multi_match":{"query":"foo","fields":["foo"]}},"from":20,"size":0,"sort":[{"bar":{"order":"desc"}}]}', json_encode($request->getData()));

                        return true;
                    }),
                ]
            )
            ->willReturn(new Response('{"took":10,"hits":{"total":2,"max_score":1.7144141,"hits":[{"foo":"baz","bar":"boz"},{"foo":"boz","bar":"baz"}]}}'))
        ;

        // Set up a dummy table
        $table = (new DataTable($this->createMock(EventDispatcher::class)))
            ->setName('foo')
            ->setMethod(Request::METHOD_GET)
            ->add('foo', TextColumn::class, ['field' => 'foo', 'globalSearchable' => true])
            ->add('bar', TextColumn::class, ['field' => 'bar', 'globalSearchable' => false])
            ->createAdapter(ElasticaAdapter::class, [
                'index' => 'test-*',
                'client' => ['transport' => $transport],
            ])
        ;

        // Prepare dummy request
        $request = new Request([
            '_dt' => 'foo',
            'order' => [[
                'column' => 1,
                'dir' => 'desc',
            ]],
            'start' => 20,
            'length' => 40,
            'search' => [
                'value' => 'foo',
            ],
        ]);

        $this->assertTrue($table->handleRequest($request)->isCallback());
        $response = json_decode($table->getResponse()->getContent());
        $this->assertEquals(2, $response->recordsTotal);
        $this->assertEquals(2, $response->recordsFiltered);
        $this->assertCount(2, $response->data);
    }

    /*
     * @expectedException \MikahDev\DataTablesBundle\Exception\MissingDependencyException
     * @expectedExceptionMessage Install ruflin/elastica to use the ElasticaAdapter
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
//    public function testMissingDependencyThrows()
//    {
//        foreach ($loaders = spl_autoload_functions() as $loader) {
//            spl_autoload_unregister($loader);
//        }
//        spl_autoload_register(function($class) use ($loaders) {
//            if ($class !== \Elastica\Client::class) {
//                foreach ($loaders as $loader) {
//                    call_user_func($loader, $class);
//                }
//            }
//        }, true, true);
//        (new ElasticaAdapter())->getData(new DataTableState(new DataTable()));
//    }
}
