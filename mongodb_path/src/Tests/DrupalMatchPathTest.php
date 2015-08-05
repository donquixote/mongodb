<?php

/**
 * @file
 * Contains DrupalMathPathTestCase.
 *
 * This is a replica of the core test with the same name, wrapped with MongoDB
 * setup and teardown.
 */

namespace Drupal\mongodb_path\Tests;

/**
 * Unit tests for the drupal_match_path() function in path.inc.
 *
 * @see drupal_match_path().
 *
 * @group MongoDB: Path API
 */
class DrupalMatchPathTest extends \DrupalWebTestCase {

  use MongoDbPathTestTrait;

  /**
   * A random name for the site under test.
   *
   * @var string
   */
  protected $front;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->preserveMongoDbConfiguration();

    // Set up the database and testing environment.
    parent::setUp();
    $this->setUpTestServices($this->databasePrefix);

    // Set up a random site front page to test the '<front>' placeholder.
    $this->front = $this->randomName();
    variable_set('site_frontpage', $this->front);
    // Refresh our static variables from the database.
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->tearDownTestServices();
    parent::tearDown();
  }

  /**
   * Run through our test cases, making sure each one works as expected.
   */
  public function testDrupalMatchPath() {
    // Set up our test cases.
    $tests = $this->drupalMatchPathTests();
    foreach ($tests as $patterns => $cases) {
      foreach ($cases as $path => $expected_result) {
        $actual_result = drupal_match_path($path, $patterns);
        $this->assertIdentical($actual_result, $expected_result, format_string('Tried matching the path <code>@path</code> to the pattern <pre>@patterns</pre> - expected @expected, got @actual.', array(
          '@path' => $path,
          '@patterns' => $patterns,
          '@expected' => var_export($expected_result, TRUE),
          '@actual' => var_export($actual_result, TRUE),
          )
        ));
      }
    }
  }

  /**
   * Helper function for testDrupalMatchPath(): set up an array of test cases.
   *
   * @return array
   *   An array of test cases to cycle through.
   */
  private function drupalMatchPathTests() {
    return array(
      // Single absolute paths.
      'blog/1' => array(
        'blog/1' => TRUE,
        'blog/2' => FALSE,
        'test' => FALSE,
      ),
      // Single paths with wildcards.
      'blog/*' => array(
        'blog/1' => TRUE,
        'blog/2' => TRUE,
        'blog/3/edit' => TRUE,
        'blog/' => TRUE,
        'blog' => FALSE,
        'test' => FALSE,
      ),
      // Single paths with multiple wildcards.
      'node/*/revisions/*' => array(
        'node/1/revisions/3' => TRUE,
        'node/345/revisions/test' => TRUE,
        'node/23/edit' => FALSE,
        'test' => FALSE,
      ),
      // Single paths with '<front>'.
      '<front>' => array(
        $this->front => TRUE,
        "$this->front/" => FALSE,
        "$this->front/edit" => FALSE,
        'node' => FALSE,
        '' => FALSE,
      ),
      // Paths with both '<front>' and wildcards (should not work).
      '<front>/*' => array(
        $this->front => FALSE,
        "$this->front/" => FALSE,
        "$this->front/edit" => FALSE,
        'node/12' => FALSE,
        '' => FALSE,
      ),
      // Multiple paths with the \n delimiter.
      "node/*\nnode/*/edit" => array(
        'node/1' => TRUE,
        'node/view' => TRUE,
        'node/32/edit' => TRUE,
        'node/delete/edit' => TRUE,
        'node/50/delete' => TRUE,
        'test/example' => FALSE,
      ),
      // Multiple paths with the \r delimiter.
      "user/*\rblog/*" => array(
        'user/1' => TRUE,
        'blog/1' => TRUE,
        'user/1/blog/1' => TRUE,
        'user/blog' => TRUE,
        'test/example' => FALSE,
        'user' => FALSE,
        'blog' => FALSE,
      ),
      // Multiple paths with the \r\n delimiter.
      "test\r\n<front>" => array(
        'test' => TRUE,
        $this->front => TRUE,
        'example' => FALSE,
      ),
      // Test existing regular expressions (should be escaped).
      '[^/]+?/[0-9]' => array(
        'test/1' => FALSE,
        '[^/]+?/[0-9]' => TRUE,
      ),
    );
  }
}
