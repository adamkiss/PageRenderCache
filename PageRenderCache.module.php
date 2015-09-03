<?php

/**
 * Page Render Cache
 *
 * Autocache page renders with MarkupCache, with simple Mustache-like replacement for dynamic items.
 *
 * @todo Optional regeneration on save
 *
 * Usage:
 *
 * @author  Adam Kiss <iam at adamkiss dot com>
 * @copyright 2015, Adam Kiss
 * @license ISC License
 */
class PageRenderCache extends WireData implements Module {

  public static function getModuleInfo() {
    return [
     'title' => 'Page Render+Cache',
     'version' => 100,
     'summary' => 'Zero-setup page rendering with caching and simples string replacements.',
     'href' => 'adamkiss.com',
     'singular' => true,
     'autoload' => true,
     'requires' => ['ProcessWire>=2.6.0', 'PHP>=5.4', 'MarkupCache'],
    ];
  }

  /**
   * Initial (autoload) function: add hooks
   * - $page(any)->renderCache($file, $tpl_opts, $replacements) - cache output and perform replacements
   * - $page(any)->save() - remove caches and generate new cache
   */
  public function init() {
    $this->addHookAfter('Page::renderCache', $this, 'hook_pageRenderCache');
    $this->pages->addHookAfter('save', $this, 'hook_pageSave');
  }

  /**
   * Most important function: Render page and cache the output
   *
   * @param  Page   $page     $page we are rendering
   * @param  string $filename template file name we use
   * @param  array  $options  options we pass through to $page->render()
   * @return string           output
   */
  private function render_cache (Page $page, $filename, $options) {
    $cache = $this->modules->get('MarkupCache');

    $lang = ($this->user->language) ? $this->user->language->name : '';

    $id = str_replace('/','.',implode('.', array_filter([ $page->id, $lang, $filename ])));

    if ($this->config->bypassPageRenderCache){
      $data = $page->render("$filename.php", $options);
    } else {
      $data = $cache->get($id);
      if (!$data){
        $data = $page->render("$filename.php", $options);
        $cache->save($data);
      }
    }

    return $data;
  }

  /**
   * Replace {{items}} with replacements in the $data
   * @param  string $data         output data
   * @param  array  $replacements associative array of $key=>$replacement pairs
   * @return string               output with replaced tags
   */
  private function replace($data, $replacements) {
    if (count($replacements)){
      return str_replace(
        array_map( function($k) { return '{{'.$k.'}}'; }, array_keys($replacements)),
        array_values($replacements),
        $data
      );

    } else {
      return $data;
    }
  }

  /**
   * $page->renderCache() Hook
   * @param  HookEvent $e
   */
  public function hook_pageRenderCache(HookEvent $e){
    $page = $e->object;
    $args = $e->arguments;

    $filename = count($args)&&!is_null($args[0]) ? $args[0] : $page->template->name;
    $template_opts = count($args)>1 ? $args[1] : [];
    $replacements  = count($args)>2 ? $args[2] : [];

    $output = $this->render_cache($page, $filename, $template_opts);
    $e->return = $this->replace($output, $replacements);
  }

  /**
   * $page->save() Hook
   * @param  HookEvent $e [description]
   */
  public function hook_pageSave(HookEvent $e){
    $page = $e->arguments[0];
    $path = $this->config->paths->cache . 'MarkupCache';
    foreach(glob("{$path}/{$page->id}.*") as $dir){
      wireRmdir($dir, true);
    }
  }

}