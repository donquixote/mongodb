<?php

/**
 * @file
 * Definition of Drupal\mongodb\Config\ConfigStorage.
 */

namespace Drupal\mongodb\Config;

use Drupal\mongodb\MongoCollectionFactory;
use Drupal\Core\Config\StorageException;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Zend\Stdlib\StringUtils;

class ConfigStorage implements StorageInterface {

  /**
   * The object wrapping the MongoDB database object.
   *
   * @var MongoCollectionFactory
   */
  protected $mongo;

  /**
   * Translation manager service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translation;

  /**
   * MongoDB collection name..
   *
   * @var string
   */
  protected $collection;

  /**
   * Constructs a new ConfigStorage controller.
   *
   * @param MongoCollectionFactory $mongo
   *   The object wrapping the MongoDB database object.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   Translation manager service.
   * @param string $name
   *   Name of config environment to use.
   * @param string $prefix
   *   Prefix to be used for collection name. Defaults to 'config'.
   */
  public function __construct(MongoCollectionFactory $mongo, TranslationInterface $translation, $name = CONFIG_ACTIVE_DIRECTORY, $prefix = 'config') {
    $this->mongo = $mongo;
    $this->translation = $translation;
    $this->collection = $prefix . '.' . $name;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::exists().
   */
  public function exists($name) {
    return $this->mongo->get($this->collection)->count(array('_id' => $name)) ? TRUE : FALSE;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::read().
   */
  public function read($name) {
    $result = $this->mongo->get($this->collection)->findOne(array('_id' => $name));
    if (empty($result)) {
      return FALSE;
    }

    unset($result['_id']);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names) {
    $data = $this->mongo->get($this->collection)->find(array('_id' => array('$in' => $names)));

    $list = array();
    foreach ($data as $item) {
      $list[$item['_id']] = $item;
      unset($list[$item['_id']]['_id']);
    }
    return $list;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::write().
   *
   * @throws \Drupal\Core\Config\StorageException
   */
  public function write($name, array $data) {
    try {
      $this->mongo->get($this->collection)->update(array('_id' => $name), array('$set' => $data), array('upsert' => TRUE));
    }
    catch (Exception $e) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::delete().
   */
  public function delete($name) {
    try {
      $result = $this->mongo->get($this->collection)->remove(array('_id' => $name));
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $result['n'] == 0 ? FALSE : TRUE;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::rename().
   */
  public function rename($name, $new_name) {
    try {
      $collection = $this->mongo->get($this->collection);
      $item = $collection->findOne(array('_id' => $name));
      if (empty($item)) {
        return FALSE;
      }
      $item['_id'] = $new_name;
      $result = $collection->insert($item);
      if (!empty($result['err'])) {
	return FALSE;
      }
      $collection->remove(array('_id' => $name));
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::encode().
   */
  public function encode($data) {
    // WTF is this part of general StorageInterface if it is only needed for
    // file-based backends?
    return $data;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::decode().
   */
  public function decode($data) {
    // WTF is this part of general StorageInterface if it is only needed for
    // file-based backends?
    return $data;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::listAll().
   */
  public function listAll($prefix = '') {
    $condition = array();
    if (!empty($prefix)) {
      $condition = array('_id' => new \MongoRegex('/^' . str_replace('.', '\.', $prefix) . '/'));
    }

    $names = array();
    $result = $this->mongo->get($this->collection)->find($condition, array('_id' => TRUE));
    foreach ($result as $item) {
      $names[] = $item['_id'];
    }

    return $names;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::deleteAll().
   */
  public function deleteAll($prefix = '') {
    $condition = array();
    if (!empty($prefix)) {
      $condition = array('_id' => new \MongoRegex('/^' . str_replace('.', '\.', $prefix) . '/'));
    }

    try {
      $this->mongo->get($this->collection)->remove($condition);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return TRUE;
  }
}
