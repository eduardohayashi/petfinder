<?php

namespace Gpupo\Tests\Petfinder\Search;

use Gpupo\Tests\Petfinder\TestCaseAbstract;
use Gpupo\Petfinder\Search\Search;
use Gpupo\Petfinder\Search\Result\Collection;
use Gpupo\Petfinder\Search\Query\Query;
use Gpupo\Petfinder\Search\Query\Keywords;

class SearchTest extends TestCaseAbstract
{
    /**
     * @dataProvider dataProviderProdutosComMarcaNoNome
     */
    public function testResultadosContendoObjetosModelados($keyword)
    {
        $collection = new Collection;
        $results = Search::getInstance()->getResultsByKeyword($keyword, $collection);
        $this->assertInstanceOf('\Gpupo\Petfinder\Search\Result\CollectionInterface', $results);

        foreach ($results->toArray() as $item) {
            $this->assertInstanceOf('\Gpupo\Petfinder\Search\Result\ItemInterface', $item);
        }
    }

    public function testPesquisaPalavraChaveSimples()
    {
         $results = Search::getInstance()->search(
            'produtoIndex',
            null,
            array(array(
                'key'    => '*',
                 'values' => array(
                    'shampoo',
                 ),
                'strict' => false,
            )),
            null,
            20,
            0
        );

         $this->assertInternalType('array', $results);

         foreach ($results as $item) {
             $this->assertArrayHasKey('attrs', $item);
             $this->stringContainOneOrAlternatives(@json_encode($item),
                'shampoo', 'condicionador');
         }

         return $results;
    }

    /**
     *
     * @depends testPesquisaPalavraChaveSimples
     */
    public function testPossuiQuantidadeLimiteDeResultados(array $results)
    {

       $this->assertGreaterThan(10, count($results));
       $this->assertLessThanOrEqual(20, count($results));

       return $results;
    }

    /**
     * @depends testPossuiQuantidadeLimiteDeResultados
     */
    public function testResultadosPossuemAtributos(array $results)
    {
        foreach ($results as $item) {
            $this->assertArrayHasKey('attrs' , $item);
        }
    }

    public function testPesquisaPorMultiplasPalavras()
    {
        $results = Search::getInstance()->search(
            'produtoIndex',
            null,
            array(array(
                'key'    => '*',
                 'values' => array(
                    'shampoo',
                    'condicionador',
                 ),
                'strict' => false,
            )),
            null,
            20,
            0
        );

         $this->assertInternalType('array', $results);

         foreach ($results as $item) {
            $this->assertArrayHasKey('attrs', $item);
            $this->assertArrayContainsOneOrMore(
                array('shampoo','condicionador', 'cabelo', 'tratamento'),
                $item
            );
         }

         return $results;
    }

    public function testPesquisaPorParteDePalavra()
    {
        $results = Search::getInstance()->search(
            'produtoIndex',
            null,
            array(array(
                'key'    => '*',
                 'values' => array(
                    'sham',
                 ),
                'strict' => false,
            )),
            null,
            20,
            0
        );

         foreach ($results as $item) {
             $this->assertArrayHasKey('attrs', $item);
             $this->assertArrayContainsOneOrMore('shampoo', $item);
         }

         return $results;
    }

    public function testPesquisaComPalavrasForaDeOrdem()
    {
        $keywords = new Keywords;
        $keywords->addKeyword('shampoo');
        $keywords->addKeyword('condicionador');
        $query = new Query($keywords);
        $query->setIndex('produtoIndex');

        $total['mode_1'] = Search::getInstance()->findByQuery($query)->getTotal();

        //Mode 2
        $keywords = new Keywords;
        $keywords->readString('shampoo condicionador');
        $query = new Query($keywords);
        $query->setIndex('produtoIndex');

        $total['mode_2'] = Search::getInstance()->findByQuery($query)->getTotal();

        //Mode 3
        $keywords = new Keywords;
        $keywords->readString('condicionador shampoo');
        $query = new Query($keywords);
        $query->setIndex('produtoIndex');

        $total['mode_3'] = Search::getInstance()->findByQuery($query)->getTotal();

        $this->assertEquals($total['mode_1'], $total['mode_2']);
        $this->assertEquals($total['mode_1'], $total['mode_3']);
        $this->assertEquals($total['mode_2'], $total['mode_3']);
    }

    public function testAcessoAQuantidadeDeResultadosDisponiveis()
    {
        $results = Search::getInstance()->query(
            'produtoIndex',
            null,
            array(array(
                'key'    => '*',
                 'values' => array(
                    'shampoo',
                 ),
                'strict' => false,
            )),
            null,
            20,
            0
        );

        $this->assertGreaterThan(10, $results['total']);
        $this->assertGreaterThan(10, $results['total_found']);

        return $results;
    }

    /**
     * @depends testAcessoAQuantidadeDeResultadosDisponiveis
     */
    public function testAcessoAQuantidadeDeResultadosDisponiveisPorPalavra($results)
    {
        $this->assertArrayHasKey('words', $results);

        foreach ($results['words'] as $word) {
            $this->assertArrayHasKey('docs', $word);
            $this->assertArrayHasKey('hits', $word);
        }
    }

    /**
     * @cover \Gpupo\Petfinder\Search\Result\Collection
     */
    public function testAcessoAResultadosEmObjetosModelados()
    {
        $collection = Search::getInstance()->getCollection(
            'produtoIndex',
            null,
            array(array(
                'key'    => '*',
                 'values' => array(
                    'shampoo'
                 ),
                'strict' => false,
            )),
            null,
            20,
            0
        );

        $this->assertInstanceOf('\Gpupo\Petfinder\Search\Result\Collection', $collection);
        $this->assertGreaterThan(10, $collection->getTotal());
        $this->assertGreaterThan(10, $collection->getTotalFound());
        $this->assertInternalType('integer', $collection->getTotal());
        $this->assertInternalType('integer', $collection->getTotalFound());

        return $collection;
    }

    public function dataProviderProdutosComMarcaNoNome()
    {
        return array(
            array('herrera', array('carolina', '212', 'ch')),
            array('azzaro', array('chrome', 'edt', 'edp', 'visit')),
            array('lacoste',array('edt', 'edp')),
            array('rabanne', array('calandre', 'black xs', 'million')),
        );
    }

    public function testSuporteAMultiQueries()
    {
        $query = array();

        $query[] = array(
            'key' => '*',
            'values' => array(
                'shampoo',
            ),
            'strict' => false,
        );

        $query[] = array(
            'key' => '*',
            'values' => array(
                'perfume',
            ),
            'strict' => false,
        );

        $multipleResults = Search::getInstance()->query(
            'produtoIndex', null, $query, null, 2, 0
        );

        $this->assertCount(2, $multipleResults);

        foreach ($multipleResults as $results) {
            $this->assertArrayHasKey('total', $results);
            $this->assertArrayHasKey('matches', $results);
            foreach ($results['matches'] as $item) {
                $this->assertArrayHasKey('attrs', $item);
            }
        }
    }
}
