<?php

/**
 * @file
 * Contains MongoDB_Path_ResolverInterface.
 */

namespace Drupal\mongodb_path;


/**
 * Interface for MongoDB path resolver implementations.
 */
interface ResolverInterface {
  const FLUSH_VAR = 'mongodb_path_flush';

  /**
   * Ensure the whitelist is initialized.
   *
   * Use the whitelist variable first, then rebuild if still empty.
   */
  public function ensureWhitelist();

  /**
   * Flush the path cache.
   *
   * Used by system_cron() to flush the path cache.
   *
   * @see system_cron()
   */
  public function flush();

  /**
   * Must the module trigger a flush on hook_flush_caches() ?
   *
   * @return bool
   *   True is module must request a flush, False, otherwise.
   */
  public function isFlushRequired();

  /**
   * Is the whitelist set but empty ?
   *
   * Must not be called if the whitelist has not been initialized.
   *
   * @return bool
   *   TRUE if the whitelist is initialized but empty, FALSE otherwise.
   */
  public function isWhitelistEmpty();

  /**
   * Is there any entry in the whitelist ?
   *
   * It may be NULL if not yet initialized, or be empty because rebuild found
   * nothing to add.
   *
   * @return bool
   *   TRUE is there is at least one entry, FALSE otherwise.
   */
  public function isWhitelistSet();

  /**
   * Return the current flush timestamp.
   *
   * @return int
   *   The timestamp of the latest flush, or 0 if flush is not required.
   */
  public function getFlushTimestamp();

  /**
   * Given a path alias, return the internal path it represents.
   *
   * @param string $path
   *   A Drupal path alias.
   * @param string|null $language
   *   An optional language code to look up the path in.
   *
   * @return string
   *   The internal path represented by the alias, or the original alias if no
   *   internal path was found.
   */
  public function getNormalPath($path, $language = NULL);

  /**
   * Given an internal Drupal path, return the alias set by the administrator.
   *
   * If no path is provided, the function will return the alias of the current
   * page.
   *
   * @param string|NULL $path
   *   An internal Drupal path.
   * @param string|null $path_language
   *   An optional language code to look up the path in.
   *
   * @return string
   *   An aliased path if one was found, or the original path if no alias was
   *   found.
   */
  public function getPathAlias($path = NULL, $path_language = NULL);

  /**
   * Return the list of path cache entries if they were not loaded from cache.
   *
   * @return string[]
   *   An array of the system paths on this page, if they were not all loaded
   *   from the cache path.
   */
  public function getRefreshedCachedPaths();

  /**
   * Lookup alias for a given path.
   *
   * @param string $path
   *   The path for which to look up an alias.
   * @param string $path_language
   *   The language for which an alias is looked up.
   *
   * @return string|bool
   *   The looked up alias, or FALSE if none can be found.
   */
  public function lookupPathAlias($path, $path_language);

  /**
   * Lookup source for a path which may be an alias.
   *
   * @param string $path
   *   The alias for which to look up a source.
   * @param string $path_language
   *   The language for which a source is looked up.
   *
   * @return string|bool
   *   The looked up source, or FALSE if none can be found.
   */
  public function lookupPathSource($path, $path_language);

  /**
   * Clear the static cache and the prefix white list.
   */
  public function lookupPathWipe();

  /**
   * Is there a possibility for this path to have a source for that language ?
   *
   * This can be used to perform a lookup only if it may possibly succeed: using
   * the memory cache, we can have information about a path not having a source,
   * in which case a query will not be necessary.
   *
   * @param string $path
   *   The path to check for a source.
   * @param string $path_language
   *   The language for which this path might be an alias for a source.
   *
   * @return bool
   *   TRUE if there is no "no_source" entry in the cache for the $path and
   *   $path_language.
   */
  public function mayHaveSource($path, $path_language);

}