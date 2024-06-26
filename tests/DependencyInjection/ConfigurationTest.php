<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Tests\DependencyInjection;

use Nelmio\ApiDocBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    private Processor $processor;

    protected function setUp(): void
    {
        $this->processor = new Processor();

        parent::setUp();
    }

    public function testDefaultArea(): void
    {
        $config = $this->processor->processConfiguration(new Configuration(), [['areas' => ['path_patterns' => ['/foo']]]]);

        self::assertSame(
            [
                'default' => [
                    'path_patterns' => ['/foo'],
                    'host_patterns' => [],
                    'name_patterns' => [],
                    'with_annotation' => false,
                    'disable_default_routes' => false,
                    'documentation' => [],
                ],
            ],
            $config['areas']
        );
    }

    public function testAreas(): void
    {
        $config = $this->processor->processConfiguration(new Configuration(), [['areas' => $areas = [
            'default' => [
                'path_patterns' => ['/foo'],
                'host_patterns' => [],
                'with_annotation' => false,
                'documentation' => [],
                'name_patterns' => [],
                'disable_default_routes' => false,
            ],
            'internal' => [
                'path_patterns' => ['/internal'],
                'host_patterns' => ['^swagger\.'],
                'with_annotation' => false,
                'documentation' => [],
                'name_patterns' => [],
                'disable_default_routes' => false,
            ],
            'commercial' => [
                'path_patterns' => ['/internal'],
                'host_patterns' => [],
                'with_annotation' => false,
                'documentation' => [],
                'name_patterns' => [],
                'disable_default_routes' => false,
            ],
        ]]]);

        self::assertSame($areas, $config['areas']);
    }

    public function testAlternativeNames(): void
    {
        $config = $this->processor->processConfiguration(new Configuration(), [[
            'models' => [
                'names' => [
                    [
                        'alias' => 'Foo1',
                        'type' => 'App\Foo',
                        'groups' => ['group'],
                    ],
                    [
                        'alias' => 'Foo2',
                        'type' => 'App\Foo',
                        'groups' => [],
                    ],
                    [
                        'alias' => 'Foo3',
                        'type' => 'App\Foo',
                    ],
                    [
                        'alias' => 'Foo4',
                        'type' => 'App\Foo',
                        'groups' => ['group'],
                        'areas' => ['internal'],
                    ],
                    [
                        'alias' => 'Foo1',
                        'type' => 'App\Foo',
                        'areas' => ['internal'],
                    ],
                    [
                        'alias' => 'Foo1',
                        'type' => 'App\Foo',
                        'groups' => ['group1', ['group2', 'parent' => 'child3']],
                    ],
                ],
            ],
        ]]);
        self::assertEquals([
            [
                'alias' => 'Foo1',
                'type' => 'App\Foo',
                'groups' => ['group'],
                'areas' => [],
            ],
            [
                'alias' => 'Foo2',
                'type' => 'App\Foo',
                'groups' => [],
                'areas' => [],
            ],
            [
                'alias' => 'Foo3',
                'type' => 'App\Foo',
                'groups' => null,
                'areas' => [],
            ],
            [
                'alias' => 'Foo4',
                'type' => 'App\\Foo',
                'groups' => ['group'],
                'areas' => ['internal'],
            ],
            [
                'alias' => 'Foo1',
                'type' => 'App\\Foo',
                'groups' => null,
                'areas' => ['internal'],
            ],
            [
                'alias' => 'Foo1',
                'type' => 'App\Foo',
                'groups' => ['group1', ['group2', 'parent' => 'child3']],
                'areas' => [],
            ],
        ], $config['models']['names']);
    }

    /**
     * @dataProvider provideInvalidConfiguration
     *
     * @param mixed[] $configuration
     */
    public function testInvalidConfiguration(array $configuration, string $expectedError): void
    {
        self::expectException(InvalidConfigurationException::class);
        self::expectExceptionMessage($expectedError);

        $this->processor->processConfiguration(new Configuration(), [$configuration]);
    }

    public static function provideInvalidConfiguration(): \Generator
    {
        yield 'invalid html_config.assets_mode' => [
            [
                'html_config' => [
                    'assets_mode' => 'invalid',
                ],
            ],
            'Invalid assets mode "invalid"',
        ];

        yield 'do not set cache.item_id' => [
            [
                'cache' => [
                    'pool' => null,
                    'item_id' => 'some-id',
                ],
            ],
            'Can not set cache.item_id if cache.pool is null',
        ];

        yield 'do not set cache.item_id, default pool' => [
            [
                'cache' => [
                    'item_id' => 'some-id',
                ],
            ],
            'Can not set cache.item_id if cache.pool is null',
        ];

        yield 'default area missing ' => [
            [
                'areas' => [
                    'some_not_default_area' => [],
                ],
            ],
            'You must specify a `default` area under `nelmio_api_doc.areas`.',
        ];

        yield 'invalid groups value for model ' => [
            [
                'models' => [
                    'names' => [
                        [
                            'alias' => 'Foo1',
                            'type' => 'App\Foo',
                            'groups' => 'invalid_string_value',
                        ],
                    ],
                ],
            ],
            'Model groups must be either `null` or an array.',
        ];
    }
}
