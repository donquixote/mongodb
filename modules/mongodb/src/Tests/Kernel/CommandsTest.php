<?php

namespace Drupal\mongodb\Tests\Kernel;

use Drupal\mongodb\Commands\MongoDbCommands;
use Drupal\mongodb\MongoDb;

class CommandsTest extends MongoDbTestBase {

  public function testCommandsService() {
    $commands = $this->container->get(MongoDb::SERVICE_COMMANDS);
    $this->assertInstanceOf(MongoDbCommands::class, $commands, "Commands service is available");
    $this->assertTrue(method_exists($commands, 'find'));
    $this->assertTrue(method_exists($commands, 'settings'));
  }

  public function testCommandSettings() {
    /** @var \Drupal\Component\Serialization\SerializationInterface $yaml */
    $yaml = $this->container->get('serialization.yaml');
    $commands = $this->container->get(MongoDb::SERVICE_COMMANDS);
    $actualString = $commands->settings();
    $this->assertInternalType('string', $actualString);
    $actual = $yaml->decode($actualString);
    $expected = [MongoDb::MODULE => $this->getSettingsArray()];
    $this->assertEquals($actual, $expected);
  }

}
