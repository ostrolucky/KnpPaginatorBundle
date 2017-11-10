<?php

namespace Knp\Bundle\PaginatorBundle\Tests\DependencyInjection\Compiler;

use Knp\Bundle\PaginatorBundle\Definition\PaginatorAware;
use Knp\Bundle\PaginatorBundle\DependencyInjection\Compiler\PaginatorAwarePass;
use Knp\Bundle\PaginatorBundle\Helper\Processor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class PaginatorAwarePassTest
 */
class PaginatorAwarePassTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    public $container;

    /**
     * @var PaginatorAwarePass
     */
    public $pass;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->container = $this->getMockBuilder(ContainerBuilder::class)->getMock();
        $this->pass      = new PaginatorAwarePass();
    }

    public function testCorrectPassProcess()
    {
        $tagged = ['tag.one' => ['paginator' => 'knp.paginator']];
        $classes = ['tag.one' => PaginatorAware::class];

        $definition = $this->setUpContainerMock('tag.one', $tagged, $classes);

        $tested = clone $definition;
        $tested->addMethodCall('setPaginator', [new Reference('knp.paginator')]);

        $this->container
            ->expects($this->once())
            ->method('setDefinition')
            ->with('tag.one', $tested)
        ;

        $this->pass->process($this->container);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Service "tag.one" must implement interface "Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface".
     */
    public function testExceptionWrongInterface()
    {
        $tagged = ['tag.one' => ['paginator' => 'knp.paginator']];
        $classes = ['tag.one' => Processor::class];

        $this->setUpContainerMock('tag.one', $tagged, $classes, true, true);
        $this->pass->process($this->container);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidDefinitionException
     * @expectedExceptionMessage Paginator service "INVALID" for tag "knp_paginator.injectable" on service "tag.one" could not be found.
     */
    public function testExceptionNoPaginator()
    {
        $tagged = ['tag.one' => ['paginator' => 'INVALID']];
        $classes = ['tag.one' => PaginatorAware::class];

        $this->setUpContainerMock('tag.one', $tagged, $classes, false);
        $this->pass->process($this->container);
    }

    private function setUpContainerMock($id, $services, $classes, $return = true, $exception = false)
    {
        $definition = new Definition($classes[$id]);

        $this->container
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(PaginatorAwarePass::PAGINATOR_AWARE_TAG)
            ->will($this->returnValue($services))
        ;

        $this->container
            ->expects($this->once())
            ->method('getDefinition')
            ->with($id)
            ->will($this->returnValue($definition))
        ;

        if (!$exception) {
            $this->container
                ->expects($this->once())
                ->method('has')
                ->with($services[$id]['paginator'])
                ->will($this->returnValue($return))
            ;
        }

        return $definition;
    }
}
